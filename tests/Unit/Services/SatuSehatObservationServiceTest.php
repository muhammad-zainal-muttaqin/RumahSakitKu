<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Clinical\Assessment;
use App\Services\SatuSehat\SatuSehatObservationService;
use App\Services\SatuSehat\SatuSehatService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * Test class for SatuSehat Observation Service.
 *
 * Tests FHIR Observation resource creation with LOINC codes for vital signs.
 */
class SatuSehatObservationServiceTest extends TestCase
{
    private SatuSehatObservationService $service;
    private $mockSatuSehat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
        $this->mockSatuSehat->shouldReceive('validateResource')
            ->byDefault()
            ->andReturn(['valid' => true, 'errors' => []]);
        $this->mockSatuSehat->shouldReceive('request')
            ->byDefault()
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);
        $this->service = new SatuSehatObservationService($this->mockSatuSehat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Assessment model.
     */
    private function createMockAssessment(array $vitalSigns = [], array $attributes = []): Assessment
    {
        $assessment = new Assessment();
        $assessment->id = $attributes['id'] ?? 1;
        $assessment->vital_signs = $vitalSigns;
        $assessment->assessment_date = $attributes['assessment_date'] ?? Carbon::parse('2024-01-15 08:30:00');

        return $assessment;
    }

    /**
     * Test LOINC constants are defined correctly.
     */
    public function test_loinc_constants(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $this->assertEquals('85354-9', $reflection->getConstant('LOINC_BLOOD_PRESSURE'));
        $this->assertEquals('8480-6', $reflection->getConstant('LOINC_SYSTOLIC'));
        $this->assertEquals('8462-4', $reflection->getConstant('LOINC_DIASTOLIC'));
        $this->assertEquals('8867-4', $reflection->getConstant('LOINC_HEART_RATE'));
        $this->assertEquals('9279-1', $reflection->getConstant('LOINC_RESPIRATORY_RATE'));
        $this->assertEquals('8310-5', $reflection->getConstant('LOINC_BODY_TEMPERATURE'));
        $this->assertEquals('2708-6', $reflection->getConstant('LOINC_OXYGEN_SATURATION'));
        $this->assertEquals('29463-7', $reflection->getConstant('LOINC_BODY_WEIGHT'));
        $this->assertEquals('8302-2', $reflection->getConstant('LOINC_BODY_HEIGHT'));
        $this->assertEquals('39156-5', $reflection->getConstant('LOINC_BMI'));
    }

    /**
     * Test createVitalSigns with all vital signs.
     */
    public function test_create_vital_signs_with_all_data(): void
    {
        $vitalSigns = [
            'blood_pressure' => '120/80',
            'heart_rate' => 80,
            'respiratory_rate' => 20,
            'temperature' => 36.5,
            'oxygen_saturation' => 98,
            'weight_kg' => 70,
            'height_cm' => 170,
        ];

        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturnUsing(function ($resource, $method, $data) {
                return [
                    'success' => true,
                    'data' => ['id' => 'OBS-' . uniqid()],
                ];
            });

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(8, $result['observations']); // BP, HR, RR, Temp, SpO2, Weight, Height, BMI
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test createVitalSigns with partial data.
     */
    public function test_create_vital_signs_with_partial_data(): void
    {
        $vitalSigns = [
            'heart_rate' => 80,
            'temperature' => 36.5,
        ];

        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'OBS001'],
            ]);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['observations']);
    }

    /**
     * Test createVitalSigns with no vital signs.
     */
    public function test_create_vital_signs_with_no_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(0, $result['observations']);
        $this->assertCount(0, $result['errors']);
    }

    /**
     * Test createVitalSigns with some failed observations.
     */
    public function test_create_vital_signs_with_some_failures(): void
    {
        $vitalSigns = [
            'heart_rate' => 80,
            'respiratory_rate' => 20,
        ];

        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturnUsing(function ($resource, $method, $data) {
                if (isset($data['code']['coding'][0]['code']) && $data['code']['coding'][0]['code'] === '8867-4') {
                    return ['success' => true, 'data' => ['id' => 'OBS001']];
                }
                return ['success' => false, 'error' => 'Failed to create observation'];
            });

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']); // Partial success
        $this->assertCount(1, $result['observations']);
        $this->assertCount(1, $result['errors']);
    }

    /**
     * Test createBloodPressure success.
     */
    public function test_create_blood_pressure_success(): void
    {
        $vitalSigns = ['blood_pressure' => '120/80'];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '85354-9'
                    && count($data['component']) === 2
                    && $data['component'][0]['code']['coding'][0]['code'] === '8480-6'
                    && $data['component'][0]['valueQuantity']['value'] === 120
                    && $data['component'][1]['code']['coding'][0]['code'] === '8462-4'
                    && $data['component'][1]['valueQuantity']['value'] === 80;
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createBloodPressure($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertEquals('OBS001', $result['data']['id']);
    }

    /**
     * Test createBloodPressure with invalid format.
     */
    public function test_create_blood_pressure_invalid_format(): void
    {
        $vitalSigns = ['blood_pressure' => 'invalid'];
        $assessment = $this->createMockAssessment($vitalSigns);

        $result = $this->service->createBloodPressure($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid blood pressure format', $result['error']);
    }

    /**
     * Test createBloodPressure with missing data.
     */
    public function test_create_blood_pressure_missing_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createBloodPressure($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
    }

    /**
     * Test createHeartRate success.
     */
    public function test_create_heart_rate_success(): void
    {
        $vitalSigns = ['heart_rate' => 80];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '8867-4'
                    && $data['valueQuantity']['value'] === 80.0
                    && $data['valueQuantity']['unit'] === 'beats/minute'
                    && $data['valueQuantity']['code'] === '/min';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createHeartRate($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createHeartRate with missing data.
     */
    public function test_create_heart_rate_missing_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createHeartRate($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertEquals('Heart rate not available', $result['error']);
    }

    /**
     * Test createRespiratoryRate success.
     */
    public function test_create_respiratory_rate_success(): void
    {
        $vitalSigns = ['respiratory_rate' => 20];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '9279-1'
                    && $data['valueQuantity']['value'] === 20.0
                    && $data['valueQuantity']['unit'] === 'breaths/minute';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createRespiratoryRate($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createRespiratoryRate with missing data.
     */
    public function test_create_respiratory_rate_missing_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createRespiratoryRate($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertEquals('Respiratory rate not available', $result['error']);
    }

    /**
     * Test createTemperature success.
     */
    public function test_create_temperature_success(): void
    {
        $vitalSigns = ['temperature' => 36.5];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '8310-5'
                    && $data['valueQuantity']['value'] === 36.5
                    && $data['valueQuantity']['unit'] === 'Celsius'
                    && $data['valueQuantity']['code'] === 'Cel';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createTemperature($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createTemperature with missing data.
     */
    public function test_create_temperature_missing_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createTemperature($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertEquals('Temperature not available', $result['error']);
    }

    /**
     * Test createOxygenSaturation success.
     */
    public function test_create_oxygen_saturation_success(): void
    {
        $vitalSigns = ['oxygen_saturation' => 98];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '2708-6'
                    && $data['valueQuantity']['value'] === 98.0
                    && $data['valueQuantity']['unit'] === '%';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createOxygenSaturation($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createOxygenSaturation with missing data.
     */
    public function test_create_oxygen_saturation_missing_data(): void
    {
        $assessment = $this->createMockAssessment([]);

        $result = $this->service->createOxygenSaturation($assessment, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertEquals('Oxygen saturation not available', $result['error']);
    }

    /**
     * Test createWeight success.
     */
    public function test_create_weight_success(): void
    {
        $vitalSigns = ['weight_kg' => 70];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '29463-7'
                    && $data['valueQuantity']['value'] === 70.0
                    && $data['valueQuantity']['unit'] === 'kg';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createHeight success.
     */
    public function test_create_height_success(): void
    {
        $vitalSigns = ['height_cm' => 170];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '8302-2'
                    && $data['valueQuantity']['value'] === 170.0
                    && $data['valueQuantity']['unit'] === 'cm';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createBMI success.
     */
    public function test_create_bmi_success(): void
    {
        $vitalSigns = [
            'weight_kg' => 70,
            'height_cm' => 170,
        ];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Observation', Mockery::on(function ($data) {
                return $data['code']['coding'][0]['code'] === '39156-5'
                    && $data['valueQuantity']['unit'] === 'kg/m2';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'OBS001']]);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        // BMI = 70 / (1.7 * 1.7) = 24.22
        $this->assertCount(3, $result['observations']); // Weight, Height, BMI
    }

    /**
     * Test createBMI with missing weight.
     */
    public function test_create_bmi_missing_weight(): void
    {
        $vitalSigns = ['height_cm' => 170];
        $assessment = $this->createMockAssessment($vitalSigns);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['observations']); // Only Height
    }

    /**
     * Test createBMI with missing height.
     */
    public function test_create_bmi_missing_height(): void
    {
        $vitalSigns = ['weight_kg' => 70];
        $assessment = $this->createMockAssessment($vitalSigns);

        $result = $this->service->createVitalSigns($assessment, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['observations']); // Only Weight
    }

    /**
     * Test getObservation success.
     */
    public function test_get_observation_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Observation', 'GET', null, 'OBS001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'OBS001',
                    'code' => ['coding' => [['code' => '8867-4']]],
                ],
            ]);

        $result = $this->service->getObservation('OBS001');

        $this->assertTrue($result['success']);
        $this->assertEquals('OBS001', $result['data']['id']);
    }

    /**
     * Test searchObservations success.
     */
    public function test_search_observations_success(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Observation', [
                'patient' => 'P123456',
                'code' => 'http://loinc.org|8867-4',
            ])
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Bundle',
                    'entry' => [
                        ['resource' => ['id' => 'OBS001']],
                    ],
                ],
            ]);

        $result = $this->service->searchObservations('P123456', '8867-4');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['entry']);
    }

    /**
     * Test searchObservations with additional params.
     */
    public function test_search_observations_with_additional_params(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Observation', [
                'patient' => 'P123456',
                'code' => 'http://loinc.org|8867-4',
                'date' => 'ge2024-01-01',
            ])
            ->andReturn([
                'success' => true,
                'data' => ['resourceType' => 'Bundle', 'entry' => []],
            ]);

        $result = $this->service->searchObservations('P123456', '8867-4', ['date' => 'ge2024-01-01']);

        $this->assertTrue($result['success']);
    }

    /**
     * Test observation resource structure.
     */
    public function test_observation_resource_structure(): void
    {
        $vitalSigns = ['heart_rate' => 80];
        $assessment = $this->createMockAssessment($vitalSigns);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $capturedData = null;
        $this->mockSatuSehat->shouldReceive('request')
            ->andReturnUsing(function ($resource, $method, $data) use (&$capturedData) {
                $capturedData = $data;
                return ['success' => true, 'data' => ['id' => 'OBS001']];
            });

        $this->service->createHeartRate($assessment, 'P123456', 'ENC001');

        $this->assertNotNull($capturedData);
        $this->assertEquals('Observation', $capturedData['resourceType']);
        $this->assertEquals('final', $capturedData['status']);
        $this->assertEquals('vital-signs', $capturedData['category'][0]['coding'][0]['code']);
        $this->assertEquals('Patient/P123456', $capturedData['subject']['reference']);
        $this->assertEquals('Encounter/ENC001', $capturedData['encounter']['reference']);
        $this->assertArrayHasKey('effectiveDateTime', $capturedData);
        $this->assertEquals(Assessment::class, $capturedData['local_type']);
        $this->assertEquals(1, $capturedData['local_id']);
    }
}
