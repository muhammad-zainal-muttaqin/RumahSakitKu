<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use App\Models\Clinical\Assessment;
use Illuminate\Support\Facades\Log;

class SatuSehatObservationService
{
    protected SatuSehatService $satuSehat;

    // LOINC codes for vital signs
    protected const LOINC_BLOOD_PRESSURE = '85354-9';
    protected const LOINC_SYSTOLIC = '8480-6';
    protected const LOINC_DIASTOLIC = '8462-4';
    protected const LOINC_HEART_RATE = '8867-4';
    protected const LOINC_RESPIRATORY_RATE = '9279-1';
    protected const LOINC_BODY_TEMPERATURE = '8310-5';
    protected const LOINC_OXYGEN_SATURATION = '2708-6';
    protected const LOINC_BODY_WEIGHT = '29463-7';
    protected const LOINC_BODY_HEIGHT = '8302-2';
    protected const LOINC_BMI = '39156-5';

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Create Observation resources for all vital signs (TTV).
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, observations: array, errors: array}
     */
    public function createVitalSigns(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $observations = [];
        $errors = [];

        $vitalSigns = $assessment->vital_signs ?? [];

        // Blood Pressure
        if (!empty($vitalSigns['blood_pressure'])) {
            $result = $this->createBloodPressure($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Blood Pressure: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Heart Rate
        if (!empty($vitalSigns['heart_rate'])) {
            $result = $this->createHeartRate($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Heart Rate: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Respiratory Rate
        if (!empty($vitalSigns['respiratory_rate'])) {
            $result = $this->createRespiratoryRate($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Respiratory Rate: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Temperature
        if (!empty($vitalSigns['temperature'])) {
            $result = $this->createTemperature($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Temperature: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Oxygen Saturation
        if (!empty($vitalSigns['oxygen_saturation'])) {
            $result = $this->createOxygenSaturation($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Oxygen Saturation: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Weight
        if (!empty($vitalSigns['weight_kg'])) {
            $result = $this->createWeight($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Weight: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // Height
        if (!empty($vitalSigns['height_cm'])) {
            $result = $this->createHeight($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'Height: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        // BMI (calculated if weight and height available)
        if (!empty($vitalSigns['weight_kg']) && !empty($vitalSigns['height_cm'])) {
            $result = $this->createBMI($assessment, $patientIhsNumber, $encounterId);
            if ($result['success']) {
                $observations[] = $result['data'];
            } else {
                $errors[] = 'BMI: ' . ($result['error'] ?? 'Unknown error');
            }
        }

        Log::info('Vital signs created', [
            'assessment_id' => $assessment->id,
            'total_observations' => count($observations),
            'errors' => $errors,
        ]);

        return [
            'success' => empty($errors) || count($observations) > 0,
            'observations' => $observations,
            'errors' => $errors,
        ];
    }

    /**
     * Create Blood Pressure observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createBloodPressure(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $bp = $vitalSigns['blood_pressure'] ?? '';

        // Parse BP format "120/80"
        if (!preg_match('/(\d+)\/(\d+)/', $bp, $matches)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invalid blood pressure format',
            ];
        }

        $systolic = (int) $matches[1];
        $diastolic = (int) $matches[2];

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_BLOOD_PRESSURE,
                        'display' => 'Blood pressure panel with all children optional',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'component' => [
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => self::LOINC_SYSTOLIC,
                                'display' => 'Systolic blood pressure',
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => $systolic,
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]',
                    ],
                ],
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => self::LOINC_DIASTOLIC,
                                'display' => 'Diastolic blood pressure',
                            ],
                        ],
                    ],
                    'valueQuantity' => [
                        'value' => $diastolic,
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]',
                    ],
                ],
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Heart Rate observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createHeartRate(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $heartRate = $vitalSigns['heart_rate'] ?? null;

        if (!$heartRate) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Heart rate not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_HEART_RATE,
                        'display' => 'Heart rate',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $heartRate,
                'unit' => 'beats/minute',
                'system' => 'http://unitsofmeasure.org',
                'code' => '/min',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Respiratory Rate observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createRespiratoryRate(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $respiratoryRate = $vitalSigns['respiratory_rate'] ?? null;

        if (!$respiratoryRate) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Respiratory rate not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_RESPIRATORY_RATE,
                        'display' => 'Respiratory rate',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $respiratoryRate,
                'unit' => 'breaths/minute',
                'system' => 'http://unitsofmeasure.org',
                'code' => '/min',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Temperature observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createTemperature(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $temperature = $vitalSigns['temperature'] ?? null;

        if (!$temperature) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Temperature not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_BODY_TEMPERATURE,
                        'display' => 'Body temperature',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $temperature,
                'unit' => 'Celsius',
                'system' => 'http://unitsofmeasure.org',
                'code' => 'Cel',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Oxygen Saturation observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createOxygenSaturation(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $spo2 = $vitalSigns['oxygen_saturation'] ?? null;

        if (!$spo2) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Oxygen saturation not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_OXYGEN_SATURATION,
                        'display' => 'Oxygen saturation in Arterial blood',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $spo2,
                'unit' => '%',
                'system' => 'http://unitsofmeasure.org',
                'code' => '%',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Weight observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    protected function createWeight(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $weight = $vitalSigns['weight_kg'] ?? null;

        if (!$weight) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Weight not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_BODY_WEIGHT,
                        'display' => 'Body weight',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $weight,
                'unit' => 'kg',
                'system' => 'http://unitsofmeasure.org',
                'code' => 'kg',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create Height observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    protected function createHeight(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $height = $vitalSigns['height_cm'] ?? null;

        if (!$height) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Height not available',
            ];
        }

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_BODY_HEIGHT,
                        'display' => 'Body height',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => (float) $height,
                'unit' => 'cm',
                'system' => 'http://unitsofmeasure.org',
                'code' => 'cm',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Create BMI observation.
     *
     * @param Assessment $assessment
     * @param string $patientIhsNumber
     * @param string $encounterId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    protected function createBMI(Assessment $assessment, string $patientIhsNumber, string $encounterId): array
    {
        $vitalSigns = $assessment->vital_signs ?? [];
        $weight = $vitalSigns['weight_kg'] ?? null;
        $height = $vitalSigns['height_cm'] ?? null;

        if (!$weight || !$height) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Weight and height required for BMI calculation',
            ];
        }

        $heightM = $height / 100;
        $bmi = round($weight / ($heightM * $heightM), 2);

        $resource = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => self::LOINC_BMI,
                        'display' => 'Body mass index (BMI) [Ratio]',
                    ],
                ],
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientIhsNumber,
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId,
            ],
            'effectiveDateTime' => $assessment->assessment_date?->toIso8601String() ?? now()->toIso8601String(),
            'valueQuantity' => [
                'value' => $bmi,
                'unit' => 'kg/m2',
                'system' => 'http://unitsofmeasure.org',
                'code' => 'kg/m2',
            ],
            'local_type' => Assessment::class,
            'local_id' => $assessment->id,
        ];

        return $this->sendObservation($resource);
    }

    /**
     * Send observation to SatuSehat.
     *
     * @param array<string, mixed> $resource
     * @return array{success: bool, data: array|null, error: string|null}
     */
    protected function sendObservation(array $resource): array
    {
        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Observation', $resource);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        return $this->satuSehat->request('Observation', 'POST', $resource);
    }

    /**
     * Get observation by ID.
     *
     * @param string $observationId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getObservation(string $observationId): array
    {
        return $this->satuSehat->request('Observation', 'GET', null, $observationId);
    }

    /**
     * Search observations by patient and code.
     *
     * @param string $patientIhsNumber
     * @param string $loincCode
     * @param array<string, mixed> $additionalParams
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchObservations(string $patientIhsNumber, string $loincCode, array $additionalParams = []): array
    {
        $params = array_merge([
            'patient' => $patientIhsNumber,
            'code' => 'http://loinc.org|' . $loincCode,
        ], $additionalParams);

        return $this->satuSehat->search('Observation', $params);
    }
}
