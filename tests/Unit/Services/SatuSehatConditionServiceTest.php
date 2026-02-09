<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Clinical\MedicalRecord;
use App\Services\SatuSehat\SatuSehatConditionService;
use App\Services\SatuSehat\SatuSehatService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for SatuSehat Condition Service.
 *
 * Tests FHIR Condition resource creation with ICD-10 codes for diagnoses.
 */
class SatuSehatConditionServiceTest extends TestCase
{
    private SatuSehatConditionService $service;
    private $mockSatuSehat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
        $this->service = new SatuSehatConditionService($this->mockSatuSehat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock MedicalRecord model.
     */
    private function createMockMedicalRecord(array $attributes = []): MedicalRecord
    {
        $record = new MedicalRecord();
        $record->id = $attributes['id'] ?? 1;
        $record->diagnosis_primary = array_key_exists('diagnosis_primary', $attributes) ? $attributes['diagnosis_primary'] : 'Cholera';
        $record->diagnosis_secondary = array_key_exists('diagnosis_secondary', $attributes) ? $attributes['diagnosis_secondary'] : null;
        $record->icd10_code = array_key_exists('icd10_code', $attributes) ? $attributes['icd10_code'] : 'A00';
        $record->icd10_description = array_key_exists('icd10_description', $attributes) ? $attributes['icd10_description'] : 'Cholera due to Vibrio cholerae';
        $record->visit_date = array_key_exists('visit_date', $attributes)
            ? $attributes['visit_date']
            : Carbon::parse('2024-01-15');
        $record->notes = array_key_exists('notes', $attributes) ? $attributes['notes'] : 'Patient showing improvement';

        // Mock relationships
        $patient = (object) ['name' => $attributes['patient_name'] ?? 'John Doe'];
        $record->setRelation('patient', $patient);

        $visit = null;
        if (isset($attributes['doctor_name'])) {
            $doctor = (object) [
                'name' => $attributes['doctor_name'],
                'satusehat_practitioner_id' => $attributes['practitioner_id'] ?? 'PRAC001',
            ];
            $visit = (object) ['doctor' => $doctor];
        }
        $record->setRelation('visit', $visit);

        return $record;
    }

    /**
     * Test createDiagnosis with primary diagnosis.
     */
    public function test_create_diagnosis_primary_success(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'icd10_code' => 'A00',
            'icd10_description' => 'Cholera due to Vibrio cholerae',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Condition', Mockery::on(function ($data) {
                return $data['resourceType'] === 'Condition'
                    && $data['code']['coding'][0]['code'] === 'A00'
                    && $data['code']['coding'][0]['system'] === 'http://hl7.org/fhir/sid/icd-10'
                    && $data['category'][0]['coding'][0]['code'] === 'encounter-diagnosis'
                    && $data['clinicalStatus']['coding'][0]['code'] === 'active'
                    && $data['verificationStatus']['coding'][0]['code'] === 'confirmed';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Condition', 'POST', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND001'],
            ]);

        $result = $this->service->createDiagnosis($medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertTrue($result['success']);
        $this->assertEquals('COND001', $result['data']['id']);
    }

    /**
     * Test createDiagnosis with secondary diagnosis.
     */
    public function test_create_diagnosis_secondary_success(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_secondary' => 'Dehydration',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Condition', Mockery::on(function ($data) {
                return $data['code']['text'] === 'Dehydration'
                    && !isset($data['code']['coding']); // No ICD-10 for secondary
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND002'],
            ]);

        $result = $this->service->createDiagnosis($medicalRecord, 'P123456', 'ENC001', 'secondary');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createDiagnosis with no diagnosis data.
     */
    public function test_create_diagnosis_no_data(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => null,
            'icd10_code' => null,
        ]);

        $result = $this->service->createDiagnosis($medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No diagnosis data available', $result['error']);
    }

    /**
     * Test createDiagnosis with validation failure.
     */
    public function test_create_diagnosis_validation_failure(): void
    {
        $medicalRecord = $this->createMockMedicalRecord();

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn([
                'valid' => false,
                'errors' => ['Condition code is required'],
            ]);

        $result = $this->service->createDiagnosis($medicalRecord, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
    }

    /**
     * Test createAllDiagnoses with both primary and secondary.
     */
    public function test_create_all_diagnoses_success(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'diagnosis_secondary' => 'Dehydration',
            'icd10_code' => 'A00',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturnUsing(function ($resource, $method, $data) {
                return [
                    'success' => true,
                    'data' => ['id' => 'COND' . uniqid()],
                ];
            });

        $result = $this->service->createAllDiagnoses($medicalRecord, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['conditions']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test createAllDiagnoses with only primary diagnosis.
     */
    public function test_create_all_diagnoses_primary_only(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'diagnosis_secondary' => null,
            'icd10_code' => 'A00',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND001'],
            ]);

        $result = $this->service->createAllDiagnoses($medicalRecord, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['conditions']);
    }

    /**
     * Test createAllDiagnoses with no diagnoses.
     */
    public function test_create_all_diagnoses_none(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => null,
            'diagnosis_secondary' => null,
            'icd10_code' => null,
        ]);

        $result = $this->service->createAllDiagnoses($medicalRecord, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
        $this->assertCount(0, $result['conditions']);
        $this->assertCount(0, $result['errors']);
    }

    /**
     * Test createAllDiagnoses with partial failure.
     */
    public function test_create_all_diagnoses_partial_failure(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'diagnosis_secondary' => 'Dehydration',
            'icd10_code' => 'A00',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturnUsing(function ($resource, $method, $data) {
                // Check if it's primary or secondary by looking at the code
                if (isset($data['code']['coding'])) {
                    return ['success' => true, 'data' => ['id' => 'COND001']];
                }
                return ['success' => false, 'error' => 'Failed to create secondary diagnosis'];
            });

        $result = $this->service->createAllDiagnoses($medicalRecord, 'P123456', 'ENC001');

        $this->assertTrue($result['success']); // Partial success
        $this->assertCount(1, $result['conditions']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Secondary diagnosis', $result['errors'][0]);
    }

    /**
     * Test updateCondition success.
     */
    public function test_update_condition_success(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Updated Diagnosis',
            'icd10_code' => 'A01',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Condition', Mockery::on(function ($data) {
                return $data['id'] === 'COND001'
                    && $data['code']['coding'][0]['code'] === 'A01';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Condition', 'PUT', Mockery::type('array'), 'COND001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND001'],
            ]);

        $result = $this->service->updateCondition('COND001', $medicalRecord, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateCondition with no data.
     */
    public function test_update_condition_no_data(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => null,
            'icd10_code' => null,
        ]);

        $result = $this->service->updateCondition('COND001', $medicalRecord, 'P123456', 'ENC001');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No diagnosis data available', $result['error']);
    }

    /**
     * Test searchDiagnosis success.
     */
    public function test_search_diagnosis_success(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Condition', [
                'code' => 'http://hl7.org/fhir/sid/icd-10|A00',
            ])
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Bundle',
                    'entry' => [
                        ['resource' => ['id' => 'COND001']],
                    ],
                ],
            ]);

        $result = $this->service->searchDiagnosis('A00');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['entry']);
    }

    /**
     * Test searchDiagnosis with patient filter.
     */
    public function test_search_diagnosis_with_patient(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Condition', [
                'code' => 'http://hl7.org/fhir/sid/icd-10|A00',
                'patient' => 'P123456',
            ])
            ->andReturn([
                'success' => true,
                'data' => ['resourceType' => 'Bundle', 'entry' => []],
            ]);

        $result = $this->service->searchDiagnosis('A00', 'P123456');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getCondition success.
     */
    public function test_get_condition_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Condition', 'GET', null, 'COND001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'COND001',
                    'code' => ['coding' => [['code' => 'A00']]],
                ],
            ]);

        $result = $this->service->getCondition('COND001');

        $this->assertTrue($result['success']);
        $this->assertEquals('COND001', $result['data']['id']);
    }

    /**
     * Test searchConditionsByPatient success.
     */
    public function test_search_conditions_by_patient_success(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Condition', ['patient' => 'P123456'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Bundle',
                    'entry' => [
                        ['resource' => ['id' => 'COND001']],
                        ['resource' => ['id' => 'COND002']],
                    ],
                ],
            ]);

        $result = $this->service->searchConditionsByPatient('P123456');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']['entry']);
    }

    /**
     * Test searchConditionsByPatient with additional params.
     */
    public function test_search_conditions_by_patient_with_params(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Condition', [
                'patient' => 'P123456',
                'clinical-status' => 'active',
            ])
            ->andReturn([
                'success' => true,
                'data' => ['resourceType' => 'Bundle', 'entry' => []],
            ]);

        $result = $this->service->searchConditionsByPatient('P123456', ['clinical-status' => 'active']);

        $this->assertTrue($result['success']);
    }

    /**
     * Test createChronicCondition success.
     */
    public function test_create_chronic_condition_success(): void
    {
        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Condition', Mockery::on(function ($data) {
                return $data['resourceType'] === 'Condition'
                    && $data['clinicalStatus']['coding'][0]['code'] === 'active'
                    && $data['verificationStatus']['coding'][0]['code'] === 'confirmed'
                    && $data['category'][0]['coding'][0]['code'] === 'problem-list-item'
                    && $data['code']['coding'][0]['code'] === 'E11'
                    && $data['code']['coding'][0]['system'] === 'http://hl7.org/fhir/sid/icd-10'
                    && $data['onsetDateTime'] === '2020-01-01'
                    && $data['abatementDateTime'] === '2024-01-15';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Condition', 'POST', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND001'],
            ]);

        $conditionData = [
            'clinical_status' => 'active',
            'verification_status' => 'confirmed',
            'category' => 'problem-list-item',
            'icd10_code' => 'E11',
            'icd10_description' => 'Type 2 diabetes mellitus',
            'description' => 'Diabetes Mellitus Type 2',
            'onset_date' => '2020-01-01',
            'abatement_date' => '2024-01-15',
            'severity' => 'moderate',
            'notes' => 'Patient on medication',
        ];

        $result = $this->service->createChronicCondition($conditionData, 'P123456', 'ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createChronicCondition with minimal data.
     */
    public function test_create_chronic_condition_minimal(): void
    {
        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'COND001'],
            ]);

        $conditionData = [
            'description' => 'Hypertension',
        ];

        $result = $this->service->createChronicCondition($conditionData, 'P123456');

        $this->assertTrue($result['success']);
    }

    /**
     * Test createChronicCondition with validation failure.
     */
    public function test_create_chronic_condition_validation_failure(): void
    {
        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn([
                'valid' => false,
                'errors' => ['Condition code is required'],
            ]);

        $conditionData = ['description' => 'Test'];

        $result = $this->service->createChronicCondition($conditionData, 'P123456');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
    }

    /**
     * Data provider for category display tests.
     *
     * @return array<string, array{string, string}>
     */
    public static function categoryDisplayProvider(): array
    {
        return [
            'problem_list_item' => ['problem-list-item', 'Problem List Item'],
            'encounter_diagnosis' => ['encounter-diagnosis', 'Encounter Diagnosis'],
            'chronic_disease' => ['chronic-disease', 'Chronic Disease'],
            'acute_disease' => ['acute-disease', 'Acute Disease'],
            'unknown' => ['unknown', 'Problem List Item'],
        ];
    }

    /**
     * Test category display mapping.
     *
     * @param string $input
     * @param string $expected
     */
    #[DataProvider('categoryDisplayProvider')]
    public function test_get_category_display(string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCategoryDisplay');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for severity mapping tests.
     *
     * @return array<string, array{string, string}>
     */
    public static function severityMappingProvider(): array
    {
        return [
            'mild' => ['mild', '255604002'],
            'ringan' => ['ringan', '255604002'],
            'moderate' => ['moderate', '6736007'],
            'sedang' => ['sedang', '6736007'],
            'severe' => ['severe', '24484000'],
            'berat' => ['berat', '24484000'],
            'unknown' => ['unknown', '6736007'],
        ];
    }

    /**
     * Test severity mapping.
     *
     * @param string $input
     * @param string $expected
     */
    #[DataProvider('severityMappingProvider')]
    public function test_map_severity_code(string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapSeverityCode');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildConditionResource with complete primary diagnosis.
     */
    public function test_build_condition_resource_primary_complete(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'icd10_code' => 'A00',
            'icd10_description' => 'Cholera due to Vibrio cholerae',
            'doctor_name' => 'Dr. Test',
            'practitioner_id' => 'PRAC001',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertNotNull($result);
        $this->assertEquals('Condition', $result['resourceType']);
        $this->assertEquals('active', $result['clinicalStatus']['coding'][0]['code']);
        $this->assertEquals('confirmed', $result['verificationStatus']['coding'][0]['code']);
        $this->assertEquals('encounter-diagnosis', $result['category'][0]['coding'][0]['code']);
        $this->assertEquals('A00', $result['code']['coding'][0]['code']);
        $this->assertEquals('http://hl7.org/fhir/sid/icd-10', $result['code']['coding'][0]['system']);
        $this->assertEquals('Cholera due to Vibrio cholerae', $result['code']['coding'][0]['display']);
        $this->assertEquals('Cholera', $result['code']['text']);
        $this->assertEquals('Patient/P123456', $result['subject']['reference']);
        $this->assertEquals('Encounter/ENC001', $result['encounter']['reference']);
        $this->assertArrayHasKey('recordedDate', $result);
        $this->assertArrayHasKey('onsetDateTime', $result);
        $this->assertEquals('Practitioner/PRAC001', $result['asserter']['reference']);
        $this->assertEquals('Patient showing improvement', $result['note'][0]['text']);
    }

    /**
     * Test buildConditionResource with secondary diagnosis (no ICD-10).
     */
    public function test_build_condition_resource_secondary_no_icd(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'diagnosis_secondary' => 'Dehydration',
            'icd10_code' => 'A00',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'secondary');

        $this->assertNotNull($result);
        $this->assertEquals('Dehydration', $result['code']['text']);
        $this->assertArrayNotHasKey('coding', $result['code']); // No ICD-10 for secondary
    }

    /**
     * Test buildConditionResource without doctor.
     */
    public function test_build_condition_resource_without_doctor(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'icd10_code' => 'A00',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertArrayNotHasKey('asserter', $result);
    }

    /**
     * Test buildConditionResource without notes.
     */
    public function test_build_condition_resource_without_notes(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'icd10_code' => 'A00',
            'notes' => null,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertArrayNotHasKey('note', $result);
    }

    /**
     * Test buildConditionResource without visit date.
     */
    public function test_build_condition_resource_without_visit_date(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => 'Cholera',
            'icd10_code' => 'A00',
            'visit_date' => null,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertArrayNotHasKey('onsetDateTime', $result);
    }

    /**
     * Test buildConditionResource returns null when no diagnosis.
     */
    public function test_build_condition_resource_returns_null(): void
    {
        $medicalRecord = $this->createMockMedicalRecord([
            'diagnosis_primary' => null,
            'diagnosis_secondary' => null,
            'icd10_code' => null,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildConditionResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $medicalRecord, 'P123456', 'ENC001', 'primary');

        $this->assertNull($result);
    }
}
