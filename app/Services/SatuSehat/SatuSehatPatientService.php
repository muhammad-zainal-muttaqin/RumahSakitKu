<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use Exception;
use App\Models\Patient\Patient;
use Illuminate\Support\Facades\Log;

class SatuSehatPatientService
{
    protected SatuSehatService $satuSehat;

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Generate IHS (Indonesia Health Services) number for patient.
     * This searches for existing patient by NIK and returns the IHS number,
     * or creates a new patient if not found.
     *
     * @param Patient $patient
     * @return array{success: bool, ihs_number: string|null, error: string|null}
     */
    public function generateNIK(Patient $patient): array
    {
        try {
            // First, search for existing patient by NIK
            $searchResult = $this->getPatientByNIK($patient->nik);

            if ($searchResult['success'] && !empty($searchResult['data']['entry'])) {
                $entry = $searchResult['data']['entry'][0];
                $ihsNumber = $entry['resource']['id'];

                Log::info('Patient found in SatuSehat', [
                    'patient_id' => $patient->id,
                    'nik' => $patient->nik,
                    'ihs_number' => $ihsNumber,
                ]);

                return [
                    'success' => true,
                    'ihs_number' => $ihsNumber,
                    'error' => null,
                ];
            }

            // Patient not found, create new patient
            $createResult = $this->createPatient($patient);

            if ($createResult['success']) {
                $ihsNumber = $createResult['data']['id'];

                Log::info('Patient created in SatuSehat', [
                    'patient_id' => $patient->id,
                    'nik' => $patient->nik,
                    'ihs_number' => $ihsNumber,
                ]);

                return [
                    'success' => true,
                    'ihs_number' => $ihsNumber,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'ihs_number' => null,
                'error' => $createResult['error'] ?? 'Failed to create patient',
            ];
        } catch (Exception $e) {
            Log::error('Error generating IHS number', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'ihs_number' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Search patient by NIK.
     *
     * @param string $nik
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getPatientByNIK(string $nik): array
    {
        return $this->satuSehat->search('Patient', [
            'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik,
        ]);
    }

    /**
     * Create Patient FHIR resource.
     *
     * @param Patient $patient
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createPatient(Patient $patient): array
    {
        $fhirPatient = $this->buildPatientResource($patient);

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Patient', $fhirPatient);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $fhirPatient['local_type'] = Patient::class;
        $fhirPatient['local_id'] = $patient->id;

        return $this->satuSehat->request('Patient', 'POST', $fhirPatient);
    }

    /**
     * Update Patient FHIR resource.
     *
     * @param string $ihsNumber
     * @param Patient $patient
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updatePatient(string $ihsNumber, Patient $patient): array
    {
        $fhirPatient = $this->buildPatientResource($patient);
        $fhirPatient['id'] = $ihsNumber;

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Patient', $fhirPatient);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $fhirPatient['local_type'] = Patient::class;
        $fhirPatient['local_id'] = $patient->id;

        return $this->satuSehat->request('Patient', 'PUT', $fhirPatient, $ihsNumber);
    }

    /**
     * Get patient by IHS number.
     *
     * @param string $ihsNumber
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getPatient(string $ihsNumber): array
    {
        return $this->satuSehat->request('Patient', 'GET', null, $ihsNumber);
    }

    /**
     * Build FHIR Patient resource from local Patient model.
     *
     * @param Patient $patient
     * @return array<string, mixed>
     */
    protected function buildPatientResource(Patient $patient): array
    {
        $resource = [
            'resourceType' => 'Patient',
            'identifier' => [
                [
                    'use' => 'official',
                    'system' => 'https://fhir.kemkes.go.id/id/nik',
                    'value' => $patient->nik,
                ],
            ],
            'name' => [
                [
                    'use' => 'official',
                    'text' => $patient->name,
                ],
            ],
            'gender' => $this->mapGender($patient->gender),
            'birthDate' => $patient->birth_date?->format('Y-m-d'),
        ];

        // Add address if available
        if ($patient->address) {
            $resource['address'] = [
                [
                    'use' => 'home',
                    'text' => $patient->address,
                    'country' => 'ID',
                ],
            ];
        }

        // Add telecom if available
        $telecom = [];
        if ($patient->phone) {
            $telecom[] = [
                'system' => 'phone',
                'value' => $patient->phone,
                'use' => 'mobile',
            ];
        }
        if ($patient->email) {
            $telecom[] = [
                'system' => 'email',
                'value' => $patient->email,
                'use' => 'home',
            ];
        }
        if (!empty($telecom)) {
            $resource['telecom'] = $telecom;
        }

        // Add birth place extension if available
        if ($patient->birth_place) {
            $resource['extension'] = [
                [
                    'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/birthPlace',
                    'valueAddress' => [
                        'city' => $patient->birth_place,
                        'country' => 'ID',
                    ],
                ],
            ];
        }

        // Add marital status if available
        if ($patient->marital_status) {
            $resource['maritalStatus'] = [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                        'code' => $this->mapMaritalStatus($patient->marital_status),
                    ],
                ],
            ];
        }

        // Add managing organization
        $resource['managingOrganization'] = [
            'reference' => 'Organization/' . $this->satuSehat->getOrganizationId(),
        ];

        // Add active status
        $resource['active'] = $patient->is_active;

        return $resource;
    }

    /**
     * Map local gender to FHIR gender.
     *
     * @param string|null $gender
     * @return string
     */
    protected function mapGender(?string $gender): string
    {
        return match (strtolower($gender ?? '')) {
            'male', 'laki-laki', 'l', 'm' => 'male',
            'female', 'perempuan', 'f', 'p' => 'female',
            default => 'unknown',
        };
    }

    /**
     * Map local marital status to FHIR marital status code.
     *
     * @param string|null $status
     * @return string
     */
    protected function mapMaritalStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'single', 'belum kawin', 'bk' => 'S',
            'married', 'kawin', 'k' => 'M',
            'divorced', 'cerai', 'c' => 'D',
            'widowed', 'janda', 'duda', 'j' => 'W',
            default => 'UNK',
        };
    }
}
