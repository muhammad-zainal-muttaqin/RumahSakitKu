<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use Illuminate\Support\Facades\Log;

class SatuSehatMedicationService
{
    protected SatuSehatService $satuSehat;

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Create Medication FHIR resource.
     *
     * @param array<string, mixed> $medicine Medicine data from local database
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createMedication(array $medicine): array
    {
        $fhirMedication = $this->buildMedicationResource($medicine);

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Medication', $fhirMedication);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        return $this->satuSehat->request('Medication', 'POST', $fhirMedication);
    }

    /**
     * Update Medication FHIR resource.
     *
     * @param string $medicationId
     * @param array<string, mixed> $medicine
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateMedication(string $medicationId, array $medicine): array
    {
        $fhirMedication = $this->buildMedicationResource($medicine);
        $fhirMedication['id'] = $medicationId;

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Medication', $fhirMedication);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        return $this->satuSehat->request('Medication', 'PUT', $fhirMedication, $medicationId);
    }

    /**
     * Get medication by ID.
     *
     * @param string $medicationId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getMedication(string $medicationId): array
    {
        return $this->satuSehat->request('Medication', 'GET', null, $medicationId);
    }

    /**
     * Search medications by code or name.
     *
     * @param string $searchTerm
     * @param string $searchType 'code' or 'name'
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchMedications(string $searchTerm, string $searchType = 'name'): array
    {
        $params = match ($searchType) {
            'code' => ['code' => $searchTerm],
            default => ['name' => $searchTerm],
        };

        return $this->satuSehat->search('Medication', $params);
    }

    /**
     * Create MedicationRequest FHIR resource.
     *
     * @param array<string, mixed> $prescription Prescription data from local database
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @param string|null $medicationId SatuSehat Medication ID (if already created)
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createMedicationRequest(array $prescription, string $patientIhsNumber, string $encounterId, ?string $medicationId = null): array
    {
        $fhirMedicationRequest = $this->buildMedicationRequestResource($prescription, $patientIhsNumber, $encounterId, $medicationId);

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('MedicationRequest', $fhirMedicationRequest);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        return $this->satuSehat->request('MedicationRequest', 'POST', $fhirMedicationRequest);
    }

    /**
     * Update MedicationRequest status.
     *
     * @param string $medicationRequestId
     * @param string $status
     * @param array<string, mixed>|null $additionalData
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateMedicationRequestStatus(string $medicationRequestId, string $status, ?array $additionalData = null): array
    {
        $validStatuses = ['draft', 'active', 'on-hold', 'revoked', 'completed', 'entered-in-error', 'stopped'];

        if (!in_array($status, $validStatuses, true)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
            ];
        }

        // First get the current medication request
        $currentResult = $this->satuSehat->request('MedicationRequest', 'GET', null, $medicationRequestId);

        if (!$currentResult['success']) {
            return $currentResult;
        }

        $fhirMedicationRequest = $currentResult['data'];
        $fhirMedicationRequest['status'] = $status;

        // Merge additional data if provided
        if ($additionalData) {
            $fhirMedicationRequest = array_merge($fhirMedicationRequest, $additionalData);
        }

        return $this->satuSehat->request('MedicationRequest', 'PUT', $fhirMedicationRequest, $medicationRequestId);
    }

    /**
     * Get medication request by ID.
     *
     * @param string $medicationRequestId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getMedicationRequest(string $medicationRequestId): array
    {
        return $this->satuSehat->request('MedicationRequest', 'GET', null, $medicationRequestId);
    }

    /**
     * Search medication requests by patient.
     *
     * @param string $patientIhsNumber
     * @param string|null $status
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchMedicationRequestsByPatient(string $patientIhsNumber, ?string $status = null): array
    {
        $params = ['patient' => $patientIhsNumber];

        if ($status) {
            $params['status'] = $status;
        }

        return $this->satuSehat->search('MedicationRequest', $params);
    }

    /**
     * Build FHIR Medication resource.
     *
     * @param array<string, mixed> $medicine
     * @return array<string, mixed>
     */
    protected function buildMedicationResource(array $medicine): array
    {
        $resource = [
            'resourceType' => 'Medication',
            'code' => [
                'text' => $medicine['name'] ?? 'Unknown Medication',
            ],
            'status' => 'active',
        ];

        // Add KFA (Katalog Farmasi dan Alat Kesehatan) code if available
        if (!empty($medicine['kfa_code'])) {
            $resource['code']['coding'] = [
                [
                    'system' => 'https://fhir.kemkes.go.id/id/kfa',
                    'code' => $medicine['kfa_code'],
                    'display' => $medicine['name'],
                ],
            ];
        }

        // Add manufacturer if available
        if (!empty($medicine['manufacturer'])) {
            $resource['manufacturer'] = [
                'display' => $medicine['manufacturer'],
            ];
        }

        // Add form (dosage form) if available
        if (!empty($medicine['form'])) {
            $resource['form'] = [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm',
                        'code' => $this->mapMedicationForm($medicine['form']),
                        'display' => $medicine['form'],
                    ],
                ],
            ];
        }

        // Add ingredient if available
        if (!empty($medicine['ingredients']) && is_array($medicine['ingredients'])) {
            $resource['ingredient'] = [];
            foreach ($medicine['ingredients'] as $ingredient) {
                $ingredientData = [
                    'itemCodeableConcept' => [
                        'text' => $ingredient['name'] ?? 'Unknown ingredient',
                    ],
                ];

                if (!empty($ingredient['strength'])) {
                    $ingredientData['strength'] = [
                        'numerator' => [
                            'value' => (float) $ingredient['strength'],
                            'unit' => $ingredient['unit'] ?? 'mg',
                        ],
                        'denominator' => [
                            'value' => 1,
                            'unit' => $medicine['form'] ?? 'tablet',
                        ],
                    ];
                }

                $resource['ingredient'][] = $ingredientData;
            }
        }

        // Add batch information if available
        if (!empty($medicine['batch'])) {
            $resource['batch'] = [
                'lotNumber' => $medicine['batch']['lot_number'] ?? null,
                'expirationDate' => $medicine['batch']['expiration_date'] ?? null,
            ];
        }

        return $resource;
    }

    /**
     * Build FHIR MedicationRequest resource.
     *
     * @param array<string, mixed> $prescription
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @param string|null $medicationId
     * @return array<string, mixed>
     */
    protected function buildMedicationRequestResource(array $prescription, string $patientIhsNumber, string $encounterId, ?string $medicationId = null): array
    {
        $resource = [
            'resourceType' => 'MedicationRequest',
            'status' => $this->mapPrescriptionStatus($prescription['status'] ?? 'active'),
            'intent' => 'order',
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
                'display' => $prescription['patient_name'] ?? 'Patient',
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'authoredOn' => $prescription['prescribed_at'] ?? now()->toIso8601String(),
        ];

        // Add medication reference or codeable concept
        if ($medicationId) {
            $resource['medicationReference'] = [
                'reference' => 'Medication/' . $medicationId,
                'display' => $prescription['medicine_name'] ?? 'Medication',
            ];
        } else {
            $resource['medicationCodeableConcept'] = [
                'text' => $prescription['medicine_name'] ?? 'Unknown Medication',
            ];

            if (!empty($prescription['kfa_code'])) {
                $resource['medicationCodeableConcept']['coding'] = [
                    [
                        'system' => 'https://fhir.kemkes.go.id/id/kfa',
                        'code' => $prescription['kfa_code'],
                        'display' => $prescription['medicine_name'],
                    ],
                ];
            }
        }

        // Add requester (doctor)
        if (!empty($prescription['doctor_id'])) {
            $resource['requester'] = [
                'reference' => 'Practitioner/' . $prescription['doctor_id'],
                'display' => $prescription['doctor_name'] ?? 'Doctor',
            ];
        }

        // Add dosage instruction
        $dosageInstruction = [];

        if (!empty($prescription['dosage_instructions'])) {
            foreach ($prescription['dosage_instructions'] as $instruction) {
                $dosage = [
                    'text' => $instruction['text'] ?? '',
                ];

                // Add route if available
                if (!empty($instruction['route'])) {
                    $dosage['route'] = [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/v3-RouteOfAdministration',
                                'code' => $this->mapRouteCode($instruction['route']),
                                'display' => $instruction['route'],
                            ],
                        ],
                    ];
                }

                // Add timing if available
                if (!empty($instruction['frequency']) || !empty($instruction['period'])) {
                    $dosage['timing'] = [
                        'repeat' => [
                            'frequency' => (int) ($instruction['frequency'] ?? 1),
                            'period' => (int) ($instruction['period'] ?? 1),
                            'periodUnit' => $instruction['period_unit'] ?? 'd',
                        ],
                    ];
                }

                // Add dose quantity if available
                if (!empty($instruction['dose_quantity'])) {
                    $dosage['doseAndRate'] = [
                        [
                            'doseQuantity' => [
                                'value' => (float) $instruction['dose_quantity'],
                                'unit' => $instruction['dose_unit'] ?? 'tablet',
                            ],
                        ],
                    ];
                }

                $dosageInstruction[] = $dosage;
            }
        }

        if (!empty($dosageInstruction)) {
            $resource['dosageInstruction'] = $dosageInstruction;
        }

        // Add dispense request if available
        if (!empty($prescription['dispense'])) {
            $resource['dispenseRequest'] = [
                'quantity' => [
                    'value' => (float) ($prescription['dispense']['quantity'] ?? 0),
                    'unit' => $prescription['dispense']['unit'] ?? 'tablet',
                ],
            ];

            if (!empty($prescription['dispense']['validity_period'])) {
                $resource['dispenseRequest']['validityPeriod'] = [
                    'start' => $prescription['dispense']['validity_period']['start'] ?? now()->toIso8601String(),
                    'end' => $prescription['dispense']['validity_period']['end'] ?? now()->addDays(7)->toIso8601String(),
                ];
            }

            if (!empty($prescription['dispense']['number_of_repeats'])) {
                $resource['dispenseRequest']['numberOfRepeatsAllowed'] = (int) $prescription['dispense']['number_of_repeats'];
            }
        }

        // Add substitution
        $resource['substitution'] = [
            'allowedBoolean' => $prescription['allow_substitution'] ?? false,
        ];

        // Add reason if available
        if (!empty($prescription['reason'])) {
            $resource['reasonCode'] = [
                [
                    'text' => $prescription['reason'],
                ],
            ];
        }

        // Add note if available
        if (!empty($prescription['notes'])) {
            $resource['note'] = [
                [
                    'text' => $prescription['notes'],
                ],
            ];
        }

        return $resource;
    }

    /**
     * Map medication form to standard code.
     *
     * @param string|null $form
     * @return string
     */
    protected function mapMedicationForm(?string $form): string
    {
        return match (strtolower($form ?? '')) {
            'tablet', 'kaplet', 'pil' => 'TAB',
            'capsule', 'kapsul' => 'CAP',
            'syrup', 'sirup', 'elixir' => 'SYRUP',
            'injection', 'injeksi', 'ampul' => 'INJ',
            'ointment', 'salep', 'unguentum' => 'OINT',
            'cream', 'krim' => 'CREAM',
            'gel' => 'GEL',
            'drops', 'tetes' => 'DROPS',
            'inhaler' => 'INHALANT',
            'suppository', 'suppositoria' => 'SUPP',
            'powder', 'serbuk' => 'PWD',
            'solution', 'larutan' => 'SOL',
            'suspension', 'suspensi' => 'SUSP',
            'emulsion', 'emulsi' => 'EMUL',
            'lotion' => 'LOTION',
            'spray' => 'SPRY',
            'patch' => 'PATCH',
            default => 'UNK',
        };
    }

    /**
     * Map prescription status to FHIR status.
     *
     * @param string|null $status
     * @return string
     */
    protected function mapPrescriptionStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'draft' => 'draft',
            'active', 'new', 'pending' => 'active',
            'on-hold', 'hold' => 'on-hold',
            'revoked', 'cancelled' => 'revoked',
            'completed', 'done', 'finished' => 'completed',
            'entered-in-error', 'error' => 'entered-in-error',
            'stopped', 'discontinued' => 'stopped',
            default => 'active',
        };
    }

    /**
     * Map route of administration to standard code.
     *
     * @param string|null $route
     * @return string
     */
    protected function mapRouteCode(?string $route): string
    {
        return match (strtolower($route ?? '')) {
            'oral', 'per oral', 'po', 'diminum' => 'PO',
            'intravenous', 'iv', 'suntik pembuluh darah' => 'IV',
            'intramuscular', 'im', 'suntik otot' => 'IM',
            'subcutaneous', 'sc', 'subcut', 'suntik bawah kulit' => 'SC',
            'topical', 'topikal', 'oles' => 'TOP',
            'inhalation', 'inhale', 'hirup' => 'INH',
            'rectal', 'rektal' => 'REC',
            'vaginal' => 'VAG',
            'ophthalmic', 'eye', 'mata' => 'OP',
            'otic', 'ear', 'telinga' => 'OT',
            'nasal', 'nose', 'hidung' => 'NAS',
            'buccal' => 'BUCC',
            'sublingual', 'sl' => 'SL',
            'transdermal' => 'TD',
            default => 'UNK',
        };
    }

    /**
     * Create batch medication requests.
     *
     * @param array<int, array<string, mixed>> $prescriptions
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, results: array, errors: array}
     */
    public function createBatchMedicationRequests(array $prescriptions, string $patientIhsNumber, string $encounterId): array
    {
        $results = [];
        $errors = [];

        foreach ($prescriptions as $index => $prescription) {
            $result = $this->createMedicationRequest($prescription, $patientIhsNumber, $encounterId);

            if ($result['success']) {
                $results[] = $result['data'];
            } else {
                $errors[] = [
                    'index' => $index,
                    'medicine' => $prescription['medicine_name'] ?? 'Unknown',
                    'error' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        Log::info('Batch medication requests created', [
            'total' => count($prescriptions),
            'successful' => count($results),
            'failed' => count($errors),
        ]);

        return [
            'success' => empty($errors) || count($results) > 0,
            'results' => $results,
            'errors' => $errors,
        ];
    }
}
