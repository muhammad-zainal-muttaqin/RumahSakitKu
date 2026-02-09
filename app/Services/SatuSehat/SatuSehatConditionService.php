<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use App\Models\Clinical\MedicalRecord;
use Illuminate\Support\Facades\Log;

class SatuSehatConditionService
{
    protected SatuSehatService $satuSehat;

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Create Condition FHIR resource for diagnosis.
     *
     * @param MedicalRecord $medicalRecord
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @param string $conditionType 'primary' or 'secondary'
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createDiagnosis(MedicalRecord $medicalRecord, string $patientIhsNumber, string $encounterId, string $conditionType = 'primary'): array
    {
        $fhirCondition = $this->buildConditionResource($medicalRecord, $patientIhsNumber, $encounterId, $conditionType);

        if (!$fhirCondition) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'No diagnosis data available for condition type: ' . $conditionType,
            ];
        }

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Condition', $fhirCondition);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $fhirCondition['local_type'] = MedicalRecord::class;
        $fhirCondition['local_id'] = $medicalRecord->id;

        return $this->satuSehat->request('Condition', 'POST', $fhirCondition);
    }

    /**
     * Create multiple conditions for primary and secondary diagnoses.
     *
     * @param MedicalRecord $medicalRecord
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, conditions: array, errors: array}
     */
    public function createAllDiagnoses(MedicalRecord $medicalRecord, string $patientIhsNumber, string $encounterId): array
    {
        $conditions = [];
        $errors = [];

        // Create primary diagnosis
        if ($medicalRecord->diagnosis_primary || $medicalRecord->icd10_code) {
            $result = $this->createDiagnosis($medicalRecord, $patientIhsNumber, $encounterId, 'primary');
            if ($result['success']) {
                $conditions[] = $result['data'];
            } else {
                $errors[] = 'Primary diagnosis: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Create secondary diagnosis if available
        if ($medicalRecord->diagnosis_secondary) {
            $result = $this->createDiagnosis($medicalRecord, $patientIhsNumber, $encounterId, 'secondary');
            if ($result['success']) {
                $conditions[] = $result['data'];
            } else {
                $errors[] = 'Secondary diagnosis: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        Log::info('Diagnoses created in SatuSehat', [
            'medical_record_id' => $medicalRecord->id,
            'total_conditions' => count($conditions),
            'errors' => $errors,
        ]);

        return [
            'success' => empty($errors) || count($conditions) > 0,
            'conditions' => $conditions,
            'errors' => $errors,
        ];
    }

    /**
     * Update Condition FHIR resource.
     *
     * @param string $conditionId
     * @param MedicalRecord $medicalRecord
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @param string $conditionType
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateCondition(string $conditionId, MedicalRecord $medicalRecord, string $patientIhsNumber, string $encounterId, string $conditionType = 'primary'): array
    {
        $fhirCondition = $this->buildConditionResource($medicalRecord, $patientIhsNumber, $encounterId, $conditionType);

        if (!$fhirCondition) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'No diagnosis data available',
            ];
        }

        $fhirCondition['id'] = $conditionId;

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Condition', $fhirCondition);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $fhirCondition['local_type'] = MedicalRecord::class;
        $fhirCondition['local_id'] = $medicalRecord->id;

        return $this->satuSehat->request('Condition', 'PUT', $fhirCondition, $conditionId);
    }

    /**
     * Search diagnosis by ICD-10 code.
     *
     * @param string $icd10Code
     * @param string|null $patientIhsNumber
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchDiagnosis(string $icd10Code, ?string $patientIhsNumber = null): array
    {
        $params = [
            'code' => 'http://hl7.org/fhir/sid/icd-10|' . $icd10Code,
        ];

        if ($patientIhsNumber) {
            $params['patient'] = $patientIhsNumber;
        }

        return $this->satuSehat->search('Condition', $params);
    }

    /**
     * Get condition by ID.
     *
     * @param string $conditionId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getCondition(string $conditionId): array
    {
        return $this->satuSehat->request('Condition', 'GET', null, $conditionId);
    }

    /**
     * Search conditions by patient.
     *
     * @param string $patientIhsNumber
     * @param array<string, mixed> $additionalParams
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchConditionsByPatient(string $patientIhsNumber, array $additionalParams = []): array
    {
        $params = array_merge([
            'patient' => $patientIhsNumber,
        ], $additionalParams);

        return $this->satuSehat->search('Condition', $params);
    }

    /**
     * Build FHIR Condition resource from local MedicalRecord model.
     *
     * @param MedicalRecord $medicalRecord
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @param string $conditionType
     * @return array<string, mixed>|null
     */
    protected function buildConditionResource(MedicalRecord $medicalRecord, string $patientIhsNumber, string $encounterId, string $conditionType): ?array
    {
        $diagnosisText = $conditionType === 'primary'
            ? $medicalRecord->diagnosis_primary
            : $medicalRecord->diagnosis_secondary;

        $icd10Code = $conditionType === 'primary'
            ? $medicalRecord->icd10_code
            : null;

        $icd10Description = $conditionType === 'primary'
            ? $medicalRecord->icd10_description
            : null;

        if (!$diagnosisText && !$icd10Code) {
            return null;
        }

        $resource = [
            'resourceType' => 'Condition',
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => 'active',
                        'display' => 'Active',
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code' => 'confirmed',
                        'display' => 'Confirmed',
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis',
                        ],
                    ],
                ],
            ],
            'code' => [
                'text' => $diagnosisText ?? $icd10Description ?? 'Unknown diagnosis',
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
                'display' => $medicalRecord->patient->name ?? 'Patient',
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'recordedDate' => $medicalRecord->visit_date?->toIso8601String() ?? now()->toIso8601String(),
        ];

        // Add ICD-10 coding if available
        if ($icd10Code) {
            $resource['code']['coding'] = [
                [
                    'system' => 'http://hl7.org/fhir/sid/icd-10',
                    'code' => $icd10Code,
                    'display' => $icd10Description ?? $diagnosisText,
                ],
            ];
        }

        // Add onset date if available
        if ($medicalRecord->visit_date) {
            $resource['onsetDateTime'] = $medicalRecord->visit_date->toIso8601String();
        }

        // Add asserter (doctor) if available
        if ($medicalRecord->visit?->doctor) {
            $resource['asserter'] = [
                'reference' => 'Practitioner/' . ($medicalRecord->visit->doctor->satusehat_practitioner_id ?? 'unknown'),
                'display' => $medicalRecord->visit->doctor->name ?? 'Doctor',
            ];
        }

        // Add notes if available
        if ($medicalRecord->notes) {
            $resource['note'] = [
                [
                    'text' => $medicalRecord->notes,
                ],
            ];
        }

        return $resource;
    }

    /**
     * Create Condition for chronic disease or problem list entry.
     *
     * @param array<string, mixed> $conditionData
     * @param string $patientIhsNumber
     * @param string|null $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createChronicCondition(array $conditionData, string $patientIhsNumber, ?string $encounterId = null): array
    {
        $resource = [
            'resourceType' => 'Condition',
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => $conditionData['clinical_status'] ?? 'active',
                        'display' => ucfirst($conditionData['clinical_status'] ?? 'active'),
                    ],
                ],
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code' => $conditionData['verification_status'] ?? 'confirmed',
                        'display' => ucfirst($conditionData['verification_status'] ?? 'confirmed'),
                    ],
                ],
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => $conditionData['category'] ?? 'problem-list-item',
                            'display' => $this->getCategoryDisplay($conditionData['category'] ?? 'problem-list-item'),
                        ],
                    ],
                ],
            ],
            'code' => [
                'text' => $conditionData['description'] ?? 'Unknown condition',
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'recordedDate' => $conditionData['recorded_date'] ?? now()->toIso8601String(),
        ];

        // Add ICD-10 coding if available
        if (!empty($conditionData['icd10_code'])) {
            $resource['code']['coding'] = [
                [
                    'system' => 'http://hl7.org/fhir/sid/icd-10',
                    'code' => $conditionData['icd10_code'],
                    'display' => $conditionData['icd10_description'] ?? $conditionData['description'],
                ],
            ];
        }

        // Add encounter reference if provided
        if ($encounterId) {
            $resource['encounter'] = [
                'reference' => 'Encounter/' . $encounterId,
            ];
        }

        // Add onset date if available
        if (!empty($conditionData['onset_date'])) {
            $resource['onsetDateTime'] = $conditionData['onset_date'];
        }

        // Add abatement date if condition is resolved
        if (!empty($conditionData['abatement_date'])) {
            $resource['abatementDateTime'] = $conditionData['abatement_date'];
        }

        // Add severity if available
        if (!empty($conditionData['severity'])) {
            $resource['severity'] = [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => $this->mapSeverityCode($conditionData['severity']),
                        'display' => $conditionData['severity'],
                    ],
                ],
            ];
        }

        // Add notes if available
        if (!empty($conditionData['notes'])) {
            $resource['note'] = [
                [
                    'text' => $conditionData['notes'],
                ],
            ];
        }

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Condition', $resource);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        return $this->satuSehat->request('Condition', 'POST', $resource);
    }

    /**
     * Get category display text.
     *
     * @param string $category
     * @return string
     */
    protected function getCategoryDisplay(string $category): string
    {
        return match ($category) {
            'problem-list-item' => 'Problem List Item',
            'encounter-diagnosis' => 'Encounter Diagnosis',
            'chronic-disease' => 'Chronic Disease',
            'acute-disease' => 'Acute Disease',
            default => 'Problem List Item',
        };
    }

    /**
     * Map severity text to SNOMED CT code.
     *
     * @param string $severity
     * @return string
     */
    protected function mapSeverityCode(string $severity): string
    {
        return match (strtolower($severity)) {
            'mild', 'ringan' => '255604002',
            'moderate', 'sedang' => '6736007',
            'severe', 'berat' => '24484000',
            default => '6736007',
        };
    }
}
