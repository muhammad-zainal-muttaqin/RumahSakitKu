<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use App\Models\Patient\Visit;
use Illuminate\Support\Facades\Log;

class SatuSehatEncounterService
{
    protected SatuSehatService $satuSehat;

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Create Encounter FHIR resource.
     *
     * @param Visit $visit
     * @param string $patientIhsNumber
     * @param string|null $locationId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createEncounter(Visit $visit, string $patientIhsNumber, ?string $locationId = null): array
    {
        $fhirEncounter = $this->buildEncounterResource($visit, $patientIhsNumber, $locationId);

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Encounter', $fhirEncounter);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $fhirEncounter['local_type'] = Visit::class;
        $fhirEncounter['local_id'] = $visit->id;

        return $this->satuSehat->request('Encounter', 'POST', $fhirEncounter);
    }

    /**
     * Update Encounter status.
     *
     * @param string $encounterId
     * @param string $status
     * @param array<string, mixed>|null $additionalData
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateEncounterStatus(string $encounterId, string $status, ?array $additionalData = null): array
    {
        $validStatuses = ['planned', 'arrived', 'triaged', 'in-progress', 'onleave', 'finished', 'cancelled'];

        if (!in_array($status, $validStatuses, true)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
            ];
        }

        // First get the current encounter
        $currentResult = $this->satuSehat->request('Encounter', 'GET', null, $encounterId);

        if (!$currentResult['success']) {
            return $currentResult;
        }

        $fhirEncounter = $currentResult['data'];
        $fhirEncounter['status'] = $status;

        // Add period end if status is finished
        if ($status === 'finished' && !isset($fhirEncounter['period']['end'])) {
            $fhirEncounter['period']['end'] = now()->toIso8601String();
        }

        // Merge additional data if provided
        if ($additionalData) {
            $fhirEncounter = array_merge($fhirEncounter, $additionalData);
        }

        return $this->satuSehat->request('Encounter', 'PUT', $fhirEncounter, $encounterId);
    }

    /**
     * Start encounter - set status to in-progress.
     *
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function startEncounter(string $encounterId): array
    {
        $result = $this->updateEncounterStatus($encounterId, 'in-progress', [
            'period' => [
                'start' => now()->toIso8601String(),
            ],
        ]);

        if ($result['success']) {
            Log::info('Encounter started', ['encounter_id' => $encounterId]);
        }

        return $result;
    }

    /**
     * Finish encounter - set status to finished.
     *
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function finishEncounter(string $encounterId): array
    {
        $result = $this->updateEncounterStatus($encounterId, 'finished', [
            'period' => [
                'end' => now()->toIso8601String(),
            ],
        ]);

        if ($result['success']) {
            Log::info('Encounter finished', ['encounter_id' => $encounterId]);
        }

        return $result;
    }

    /**
     * Get encounter by ID.
     *
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getEncounter(string $encounterId): array
    {
        return $this->satuSehat->request('Encounter', 'GET', null, $encounterId);
    }

    /**
     * Search encounters by patient IHS number.
     *
     * @param string $patientIhsNumber
     * @param array<string, mixed> $additionalParams
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchEncountersByPatient(string $patientIhsNumber, array $additionalParams = []): array
    {
        $params = array_merge([
            'patient' => $patientIhsNumber,
        ], $additionalParams);

        return $this->satuSehat->search('Encounter', $params);
    }

    /**
     * Build FHIR Encounter resource from local Visit model.
     *
     * @param Visit $visit
     * @param string $patientIhsNumber
     * @param string|null $locationId
     * @return array<string, mixed>
     */
    protected function buildEncounterResource(Visit $visit, string $patientIhsNumber, ?string $locationId = null): array
    {
        $resource = [
            'resourceType' => 'Encounter',
            'status' => $this->mapVisitStatus($visit->status),
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $this->mapVisitClass($visit->visit_type),
                'display' => $this->getVisitClassDisplay($visit->visit_type),
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
                'display' => $visit->patient->name ?? 'Patient',
            ],
            'period' => [
                'start' => $visit->check_in_at?->toIso8601String() ?? now()->toIso8601String(),
            ],
        ];

        // Add identifier
        $resource['identifier'] = [
            [
                'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $this->satuSehat->getOrganizationId(),
                'value' => $visit->visit_number,
            ],
        ];

        // Add location if available
        if ($locationId) {
            $resource['location'] = [
                [
                    'location' => [
                        'reference' => 'Location/' . $locationId,
                        'display' => $visit->polyclinic->name ?? 'Location',
                    ],
                    'status' => 'active',
                ],
            ];
        }

        // Add service provider (organization)
        $resource['serviceProvider'] = [
            'reference' => 'Organization/' . $this->satuSehat->getOrganizationId(),
        ];

        // Add participant (doctor) if available
        if ($visit->doctor) {
            $resource['participant'] = [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'ATND',
                                    'display' => 'attender',
                                ],
                            ],
                        ],
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/' . $visit->doctor->satusehat_practitioner_id ?? 'unknown',
                        'display' => $visit->doctor->name ?? 'Doctor',
                    ],
                ],
            ];
        }

        // Add reason code if complaint available
        if ($visit->complaint) {
            $resource['reasonCode'] = [
                [
                    'text' => $visit->complaint,
                ],
            ];
        }

        // Add priority if available
        if ($visit->priority) {
            $resource['priority'] = [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActPriority',
                        'code' => $this->mapPriority($visit->priority),
                    ],
                ],
            ];
        }

        // Add hospitalization for inpatient visits
        if ($visit->visit_type === 'inpatient') {
            $resource['hospitalization'] = [
                'admitSource' => [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/admit-source',
                            'code' => 'emd',
                            'display' => 'From emergency department',
                        ],
                    ],
                ],
            ];
        }

        return $resource;
    }

    /**
     * Map local visit status to FHIR encounter status.
     *
     * @param string|null $status
     * @return string
     */
    protected function mapVisitStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'registered', 'pendaftaran' => 'arrived',
            'waiting', 'menunggu' => 'triaged',
            'in-progress', 'dalam proses', 'pelayanan' => 'in-progress',
            'completed', 'selesai', 'done' => 'finished',
            'cancelled', 'dibatalkan' => 'cancelled',
            default => 'planned',
        };
    }

    /**
     * Map visit type to FHIR encounter class code.
     *
     * @param string|null $visitType
     * @return string
     */
    protected function mapVisitClass(?string $visitType): string
    {
        return match (strtolower($visitType ?? '')) {
            'outpatient', 'rawat jalan', 'rj' => 'AMB',
            'inpatient', 'rawat inap', 'ri' => 'IMP',
            'emergency', 'igd', 'gawat darurat' => 'EMER',
            'home', 'home care' => 'HH',
            'virtual', 'telemedicine' => 'VR',
            default => 'AMB',
        };
    }

    /**
     * Get display text for encounter class.
     *
     * @param string|null $visitType
     * @return string
     */
    protected function getVisitClassDisplay(?string $visitType): string
    {
        return match (strtolower($visitType ?? '')) {
            'outpatient', 'rawat jalan', 'rj' => 'ambulatory',
            'inpatient', 'rawat inap', 'ri' => 'inpatient encounter',
            'emergency', 'igd', 'gawat darurat' => 'emergency',
            'home', 'home care' => 'home health',
            'virtual', 'telemedicine' => 'virtual',
            default => 'ambulatory',
        };
    }

    /**
     * Map local priority to FHIR priority code.
     *
     * @param string|null $priority
     * @return string
     */
    protected function mapPriority(?string $priority): string
    {
        return match (strtolower($priority ?? '')) {
            'routine', 'rutin', 'r' => 'R',
            'urgent', 'darurat', 'u' => 'UR',
            'emergency', 'gawat', 'e' => 'EM',
            'asap' => 'A',
            default => 'R',
        };
    }
}
