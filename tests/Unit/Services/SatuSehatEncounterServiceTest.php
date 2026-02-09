<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Patient\Visit;
use App\Services\SatuSehat\SatuSehatEncounterService;
use App\Services\SatuSehat\SatuSehatService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for SatuSehat Encounter Service.
 *
 * Tests FHIR Encounter resource creation, status transitions, and visit mapping.
 */
class SatuSehatEncounterServiceTest extends TestCase
{
    private SatuSehatEncounterService $service;
    private $mockSatuSehat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
        $this->mockSatuSehat->allows('getOrganizationId')->andReturn('ORG001');
        $this->service = new SatuSehatEncounterService($this->mockSatuSehat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Visit model.
     */
    private function createMockVisit(array $attributes = []): Visit
    {
        $visit = new Visit();
        $visit->id = $attributes['id'] ?? 1;
        $visit->visit_number = $attributes['visit_number'] ?? 'VIS001';
        $visit->visit_status = $attributes['status'] ?? 'registered';
        $visit->visit_type = $attributes['visit_type'] ?? 'outpatient';
        $visit->check_in_at = $attributes['check_in_at'] ?? Carbon::parse('2024-01-15 08:00:00');
        $visit->complaint = array_key_exists('complaint', $attributes) ? $attributes['complaint'] : 'Sakit kepala';
        $visit->priority = array_key_exists('priority', $attributes) ? $attributes['priority'] : 'routine';

        // Mock relationships
        $patient = (object) ['name' => $attributes['patient_name'] ?? 'John Doe'];
        $visit->setRelation('patient', $patient);

        $polyclinic = (object) ['name' => $attributes['polyclinic_name'] ?? 'Poli Umum'];
        $visit->setRelation('polyclinic', $polyclinic);

        $doctor = null;
        if (isset($attributes['doctor_name'])) {
            $doctor = (object) [
                'name' => $attributes['doctor_name'],
                'satusehat_practitioner_id' => $attributes['practitioner_id'] ?? 'PRAC001',
            ];
        }
        $visit->setRelation('doctor', $doctor);

        return $visit;
    }

    /**
     * Test createEncounter success.
     */
    public function test_create_encounter_success(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS001',
            'status' => 'registered',
            'visit_type' => 'outpatient',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Encounter', Mockery::on(function ($data) {
                return $data['resourceType'] === 'Encounter'
                    && $data['status'] === 'arrived'
                    && $data['class']['code'] === 'AMB';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'POST', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001'],
            ]);

        $result = $this->service->createEncounter($visit, 'P123456', 'LOC001');

        $this->assertTrue($result['success']);
        $this->assertEquals('ENC001', $result['data']['id']);
    }

    /**
     * Test createEncounter with validation failure.
     */
    public function test_create_encounter_validation_failure(): void
    {
        $visit = $this->createMockVisit();

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn([
                'valid' => false,
                'errors' => ['Encounter status is required'],
            ]);

        $result = $this->service->createEncounter($visit, 'P123456');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
    }

    /**
     * Test createEncounter without location.
     */
    public function test_create_encounter_without_location(): void
    {
        $visit = $this->createMockVisit();

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'POST', Mockery::on(function ($data) {
                return !isset($data['location']);
            }))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001'],
            ]);

        $result = $this->service->createEncounter($visit, 'P123456');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateEncounterStatus success.
     */
    public function test_update_encounter_status_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'arrived',
                    'class' => ['code' => 'AMB'],
                    'subject' => ['reference' => 'Patient/P123456'],
                ],
            ]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'PUT', Mockery::on(function ($data) {
                return $data['status'] === 'in-progress';
            }), 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001', 'status' => 'in-progress'],
            ]);

        $result = $this->service->updateEncounterStatus('ENC001', 'in-progress');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateEncounterStatus with invalid status.
     */
    public function test_update_encounter_status_with_invalid_status(): void
    {
        $result = $this->service->updateEncounterStatus('ENC001', 'invalid-status');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid status', $result['error']);
    }

    /**
     * Test updateEncounterStatus with failed GET request.
     */
    public function test_update_encounter_status_with_failed_get(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => false,
                'error' => 'Encounter not found',
            ]);

        $result = $this->service->updateEncounterStatus('ENC001', 'in-progress');

        $this->assertFalse($result['success']);
        $this->assertEquals('Encounter not found', $result['error']);
    }

    /**
     * Test updateEncounterStatus to finished adds period end.
     */
    public function test_update_encounter_status_to_finished_adds_period_end(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'in-progress',
                    'period' => ['start' => '2024-01-15T08:00:00+07:00'],
                ],
            ]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'PUT', Mockery::on(function ($data) {
                return $data['status'] === 'finished'
                    && isset($data['period']['end']);
            }), 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001', 'status' => 'finished'],
            ]);

        $result = $this->service->updateEncounterStatus('ENC001', 'finished');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateEncounterStatus with additional data.
     */
    public function test_update_encounter_status_with_additional_data(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'arrived',
                ],
            ]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'PUT', Mockery::on(function ($data) {
                return isset($data['extension'])
                    && $data['extension'][0]['url'] === 'http://example.org/custom';
            }), 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001'],
            ]);

        $additionalData = [
            'extension' => [
                [
                    'url' => 'http://example.org/custom',
                    'valueString' => 'custom value',
                ],
            ],
        ];

        $result = $this->service->updateEncounterStatus('ENC001', 'in-progress', $additionalData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test startEncounter success.
     */
    public function test_start_encounter_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->ordered()
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'arrived',
                ],
            ]);

        $this->mockSatuSehat->shouldReceive('request')
            ->ordered()
            ->with('Encounter', 'PUT', Mockery::on(function ($data) {
                return $data['status'] === 'in-progress'
                    && isset($data['period']['start']);
            }), 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001', 'status' => 'in-progress'],
            ]);

        $result = $this->service->startEncounter('ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test finishEncounter success.
     */
    public function test_finish_encounter_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->ordered()
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'in-progress',
                    'period' => ['start' => '2024-01-15T08:00:00+07:00'],
                ],
            ]);

        $this->mockSatuSehat->shouldReceive('request')
            ->ordered()
            ->with('Encounter', 'PUT', Mockery::on(function ($data) {
                return $data['status'] === 'finished'
                    && isset($data['period']['end']);
            }), 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'ENC001', 'status' => 'finished'],
            ]);

        $result = $this->service->finishEncounter('ENC001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getEncounter success.
     */
    public function test_get_encounter_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Encounter', 'GET', null, 'ENC001')
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Encounter',
                    'id' => 'ENC001',
                    'status' => 'in-progress',
                ],
            ]);

        $result = $this->service->getEncounter('ENC001');

        $this->assertTrue($result['success']);
        $this->assertEquals('ENC001', $result['data']['id']);
    }

    /**
     * Test searchEncountersByPatient success.
     */
    public function test_search_encounters_by_patient_success(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Encounter', ['patient' => 'P123456'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'resourceType' => 'Bundle',
                    'entry' => [
                        ['resource' => ['id' => 'ENC001']],
                        ['resource' => ['id' => 'ENC002']],
                    ],
                ],
            ]);

        $result = $this->service->searchEncountersByPatient('P123456');

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']['entry']);
    }

    /**
     * Test searchEncountersByPatient with additional params.
     */
    public function test_search_encounters_by_patient_with_additional_params(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Encounter', [
                'patient' => 'P123456',
                'date' => 'ge2024-01-01',
                'status' => 'finished',
            ])
            ->andReturn([
                'success' => true,
                'data' => ['resourceType' => 'Bundle', 'entry' => []],
            ]);

        $result = $this->service->searchEncountersByPatient('P123456', [
            'date' => 'ge2024-01-01',
            'status' => 'finished',
        ]);

        $this->assertTrue($result['success']);
    }

    /**
     * Data provider for visit status mapping tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function visitStatusMappingProvider(): array
    {
        return [
            'registered' => ['registered', 'arrived'],
            'pendaftaran' => ['pendaftaran', 'arrived'],
            'waiting' => ['waiting', 'triaged'],
            'menunggu' => ['menunggu', 'triaged'],
            'in_progress' => ['in-progress', 'in-progress'],
            'dalam_proses' => ['dalam proses', 'in-progress'],
            'pelayanan' => ['pelayanan', 'in-progress'],
            'completed' => ['completed', 'finished'],
            'selesai' => ['selesai', 'finished'],
            'done' => ['done', 'finished'],
            'cancelled' => ['cancelled', 'cancelled'],
            'dibatalkan' => ['dibatalkan', 'cancelled'],
            'null_status' => [null, 'planned'],
            'empty_status' => ['', 'planned'],
            'unknown_status' => ['unknown', 'planned'],
        ];
    }

    /**
     * Test visit status mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('visitStatusMappingProvider')]
    public function test_map_visit_status(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapVisitStatus');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for visit class mapping tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function visitClassMappingProvider(): array
    {
        return [
            'outpatient' => ['outpatient', 'AMB'],
            'rawat_jalan' => ['rawat jalan', 'AMB'],
            'rj' => ['rj', 'AMB'],
            'inpatient' => ['inpatient', 'IMP'],
            'rawat_inap' => ['rawat inap', 'IMP'],
            'ri' => ['ri', 'IMP'],
            'emergency' => ['emergency', 'EMER'],
            'igd' => ['igd', 'EMER'],
            'gawat_darurat' => ['gawat darurat', 'EMER'],
            'home' => ['home', 'HH'],
            'home_care' => ['home care', 'HH'],
            'virtual' => ['virtual', 'VR'],
            'telemedicine' => ['telemedicine', 'VR'],
            'null_type' => [null, 'AMB'],
            'empty_type' => ['', 'AMB'],
            'unknown_type' => ['unknown', 'AMB'],
        ];
    }

    /**
     * Test visit class mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('visitClassMappingProvider')]
    public function test_map_visit_class(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapVisitClass');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for visit class display tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function visitClassDisplayProvider(): array
    {
        return [
            'outpatient' => ['outpatient', 'ambulatory'],
            'rawat_jalan' => ['rawat jalan', 'ambulatory'],
            'rj' => ['rj', 'ambulatory'],
            'inpatient' => ['inpatient', 'inpatient encounter'],
            'rawat_inap' => ['rawat inap', 'inpatient encounter'],
            'ri' => ['ri', 'inpatient encounter'],
            'emergency' => ['emergency', 'emergency'],
            'igd' => ['igd', 'emergency'],
            'gawat_darurat' => ['gawat darurat', 'emergency'],
            'home' => ['home', 'home health'],
            'home_care' => ['home care', 'home health'],
            'virtual' => ['virtual', 'virtual'],
            'telemedicine' => ['telemedicine', 'virtual'],
            'null_type' => [null, 'ambulatory'],
            'empty_type' => ['', 'ambulatory'],
            'unknown_type' => ['unknown', 'ambulatory'],
        ];
    }

    /**
     * Test visit class display mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('visitClassDisplayProvider')]
    public function test_get_visit_class_display(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getVisitClassDisplay');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for priority mapping tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function priorityMappingProvider(): array
    {
        return [
            'routine' => ['routine', 'R'],
            'rutin' => ['rutin', 'R'],
            'r' => ['r', 'R'],
            'urgent' => ['urgent', 'UR'],
            'darurat' => ['darurat', 'UR'],
            'u' => ['u', 'UR'],
            'emergency' => ['emergency', 'EM'],
            'gawat' => ['gawat', 'EM'],
            'e' => ['e', 'EM'],
            'asap' => ['asap', 'A'],
            'null_priority' => [null, 'R'],
            'empty_priority' => ['', 'R'],
            'unknown_priority' => ['unknown', 'R'],
        ];
    }

    /**
     * Test priority mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('priorityMappingProvider')]
    public function test_map_priority(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapPriority');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildEncounterResource with outpatient visit.
     */
    public function test_build_encounter_resource_outpatient(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS001',
            'status' => 'registered',
            'visit_type' => 'outpatient',
            'complaint' => 'Sakit kepala',
            'priority' => 'routine',
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('ORG001');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEncounterResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $visit, 'P123456', 'LOC001');

        $this->assertEquals('Encounter', $result['resourceType']);
        $this->assertEquals('arrived', $result['status']);
        $this->assertEquals('AMB', $result['class']['code']);
        $this->assertEquals('ambulatory', $result['class']['display']);
        $this->assertEquals('Patient/P123456', $result['subject']['reference']);
        $this->assertEquals('VIS001', $result['identifier'][0]['value']);
        $this->assertEquals('Organization/ORG001', $result['serviceProvider']['reference']);
        $this->assertEquals('Location/LOC001', $result['location'][0]['location']['reference']);
        $this->assertEquals('Sakit kepala', $result['reasonCode'][0]['text']);
        $this->assertEquals('R', $result['priority']['coding'][0]['code']);
        $this->assertArrayNotHasKey('hospitalization', $result);
    }

    /**
     * Test buildEncounterResource with inpatient visit.
     */
    public function test_build_encounter_resource_inpatient(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS002',
            'status' => 'in-progress',
            'visit_type' => 'inpatient',
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('ORG001');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEncounterResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $visit, 'P123456', null);

        $this->assertEquals('IMP', $result['class']['code']);
        $this->assertEquals('inpatient encounter', $result['class']['display']);
        $this->assertArrayHasKey('hospitalization', $result);
        $this->assertEquals('emd', $result['hospitalization']['admitSource']['coding'][0]['code']);
        $this->assertArrayNotHasKey('location', $result);
    }

    /**
     * Test buildEncounterResource with emergency visit.
     */
    public function test_build_encounter_resource_emergency(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS003',
            'status' => 'registered',
            'visit_type' => 'emergency',
            'priority' => 'emergency',
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('ORG001');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEncounterResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $visit, 'P123456', null);

        $this->assertEquals('EMER', $result['class']['code']);
        $this->assertEquals('emergency', $result['class']['display']);
        $this->assertEquals('EM', $result['priority']['coding'][0]['code']);
    }

    /**
     * Test buildEncounterResource with doctor.
     */
    public function test_build_encounter_resource_with_doctor(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS001',
            'doctor_name' => 'Dr. Test',
            'practitioner_id' => 'PRAC001',
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('ORG001');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEncounterResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $visit, 'P123456', null);

        $this->assertArrayHasKey('participant', $result);
        $this->assertEquals('ATND', $result['participant'][0]['type'][0]['coding'][0]['code']);
        $this->assertEquals('Practitioner/PRAC001', $result['participant'][0]['individual']['reference']);
        $this->assertEquals('Dr. Test', $result['participant'][0]['individual']['display']);
    }

    /**
     * Test buildEncounterResource without optional fields.
     */
    public function test_build_encounter_resource_without_optional_fields(): void
    {
        $visit = $this->createMockVisit([
            'visit_number' => 'VIS001',
            'complaint' => null,
            'priority' => null,
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('ORG001');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildEncounterResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $visit, 'P123456', null);

        $this->assertArrayNotHasKey('reasonCode', $result);
        $this->assertArrayNotHasKey('priority', $result);
        $this->assertArrayNotHasKey('participant', $result);
        $this->assertArrayNotHasKey('location', $result);
    }
}
