<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Patient\Patient;
use App\Services\SatuSehat\SatuSehatPatientService;
use App\Services\SatuSehat\SatuSehatService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for SatuSehat Patient Service.
 *
 * Tests FHIR Patient resource creation, updates, and NIK/IHS number generation.
 */
class SatuSehatPatientServiceTest extends TestCase
{
    private SatuSehatPatientService $service;
    private $mockSatuSehat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
        $this->mockSatuSehat->allows('getOrganizationId')->andReturn('test-org-id');
        $this->service = new SatuSehatPatientService($this->mockSatuSehat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock Patient model.
     */
    private function createMockPatient(array $attributes = []): Patient
    {
        $patient = new Patient();
        $patient->id = $attributes['id'] ?? 1;
        $patient->nik = $attributes['nik'] ?? '1234567890123456';
        $patient->name = $attributes['name'] ?? 'John Doe';
        $patient->gender = $attributes['gender'] ?? 'male';
        $patient->birth_date = $attributes['birth_date'] ?? Carbon::parse('1990-01-15');
        $patient->address = array_key_exists('address', $attributes) ? $attributes['address'] : 'Jl. Test No. 123';
        $patient->phone = array_key_exists('phone', $attributes) ? $attributes['phone'] : '08123456789';
        $patient->email = array_key_exists('email', $attributes) ? $attributes['email'] : 'john@example.com';
        $patient->birth_place = array_key_exists('birth_place', $attributes) ? $attributes['birth_place'] : 'Jakarta';
        $patient->marital_status = array_key_exists('marital_status', $attributes) ? $attributes['marital_status'] : 'single';
        $patient->is_active = $attributes['is_active'] ?? true;

        return $patient;
    }

    /**
     * Test generateNIK with existing patient in SatuSehat.
     */
    public function test_generate_nik_with_existing_patient(): void
    {
        $patient = $this->createMockPatient();

        $this->mockSatuSehat->shouldReceive('search')
            ->with('Patient', ['identifier' => 'https://fhir.kemkes.go.id/id/nik|1234567890123456'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'entry' => [
                        [
                            'resource' => [
                                'id' => 'P123456',
                                'identifier' => [
                                    [
                                        'system' => 'https://fhir.kemkes.go.id/id/nik',
                                        'value' => '1234567890123456',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $result = $this->service->generateNIK($patient);

        $this->assertTrue($result['success']);
        $this->assertEquals('P123456', $result['ihs_number']);
        $this->assertNull($result['error']);
    }

    /**
     * Test generateNIK creates new patient when not found.
     */
    public function test_generate_nik_creates_new_patient_when_not_found(): void
    {
        $patient = $this->createMockPatient();

        $this->mockSatuSehat->shouldReceive('search')
            ->with('Patient', ['identifier' => 'https://fhir.kemkes.go.id/id/nik|1234567890123456'])
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
            ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Patient', Mockery::type('array'))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Patient', 'POST', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'P789012'],
            ]);

        $result = $this->service->generateNIK($patient);

        $this->assertTrue($result['success']);
        $this->assertEquals('P789012', $result['ihs_number']);
    }

    /**
     * Test generateNIK with failed creation.
     */
    public function test_generate_nik_with_failed_creation(): void
    {
        $patient = $this->createMockPatient();

        $this->mockSatuSehat->shouldReceive('search')
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
            ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->andReturn([
                'success' => false,
                'error' => 'Validation failed',
            ]);

        $result = $this->service->generateNIK($patient);

        $this->assertFalse($result['success']);
        $this->assertNull($result['ihs_number']);
        $this->assertEquals('Validation failed', $result['error']);
    }

    /**
     * Test generateNIK with exception.
     */
    public function test_generate_nik_with_exception(): void
    {
        $patient = $this->createMockPatient();

        $this->mockSatuSehat->shouldReceive('search')
            ->andThrow(new \Exception('Network error'));

        $result = $this->service->generateNIK($patient);

        $this->assertFalse($result['success']);
        $this->assertNull($result['ihs_number']);
        $this->assertStringContainsString('Network error', $result['error']);
    }

    /**
     * Test getPatientByNIK success.
     */
    public function test_get_patient_by_nik_success(): void
    {
        $this->mockSatuSehat->shouldReceive('search')
            ->with('Patient', ['identifier' => 'https://fhir.kemkes.go.id/id/nik|1234567890123456'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'entry' => [
                        ['resource' => ['id' => 'P123456', 'name' => [['text' => 'John Doe']]]],
                    ],
                ],
            ]);

        $result = $this->service->getPatientByNIK('1234567890123456');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('entry', $result['data']);
    }

    /**
     * Test createPatient success.
     */
    public function test_create_patient_success(): void
    {
        $patient = $this->createMockPatient([
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'gender' => 'male',
            'birth_date' => new \DateTime('1990-01-15'),
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'john@example.com',
            'birth_place' => 'Jakarta',
            'marital_status' => 'single',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Patient', Mockery::on(function ($data) {
                return $data['resourceType'] === 'Patient'
                    && $data['identifier'][0]['value'] === '1234567890123456'
                    && $data['name'][0]['text'] === 'John Doe';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Patient', 'POST', Mockery::type('array'))
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'P123456'],
            ]);

        $result = $this->service->createPatient($patient);

        $this->assertTrue($result['success']);
        $this->assertEquals('P123456', $result['data']['id']);
    }

    /**
     * Test createPatient with validation failure.
     */
    public function test_create_patient_validation_failure(): void
    {
        $patient = $this->createMockPatient();

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->andReturn([
                'valid' => false,
                'errors' => ['Patient identifier is required'],
            ]);

        $result = $this->service->createPatient($patient);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
    }

    /**
     * Test updatePatient success.
     */
    public function test_update_patient_success(): void
    {
        $patient = $this->createMockPatient([
            'name' => 'Updated Name',
        ]);

        $this->mockSatuSehat->shouldReceive('validateResource')
            ->with('Patient', Mockery::on(function ($data) {
                return $data['id'] === 'P123456'
                    && $data['name'][0]['text'] === 'Updated Name';
            }))
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat->shouldReceive('request')
            ->with('Patient', 'PUT', Mockery::type('array'), 'P123456')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'P123456'],
            ]);

        $result = $this->service->updatePatient('P123456', $patient);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPatient success.
     */
    public function test_get_patient_success(): void
    {
        $this->mockSatuSehat->shouldReceive('request')
            ->with('Patient', 'GET', null, 'P123456')
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'P123456',
                    'name' => [['text' => 'John Doe']],
                ],
            ]);

        $result = $this->service->getPatient('P123456');

        $this->assertTrue($result['success']);
        $this->assertEquals('P123456', $result['data']['id']);
    }

    /**
     * Test buildPatientResource with complete data.
     */
    public function test_build_patient_resource_with_complete_data(): void
    {
        $patient = $this->createMockPatient([
            'nik' => '1234567890123456',
            'name' => 'John Doe',
            'gender' => 'male',
            'birth_date' => new \DateTime('1990-01-15'),
            'address' => 'Jl. Test No. 123',
            'phone' => '08123456789',
            'email' => 'john@example.com',
            'birth_place' => 'Jakarta',
            'marital_status' => 'single',
            'is_active' => true,
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('test-org-id');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPatientResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $patient);

        $this->assertEquals('Patient', $result['resourceType']);
        $this->assertEquals('1234567890123456', $result['identifier'][0]['value']);
        $this->assertEquals('John Doe', $result['name'][0]['text']);
        $this->assertEquals('male', $result['gender']);
        $this->assertEquals('1990-01-15', $result['birthDate']);
        $this->assertEquals('Jl. Test No. 123', $result['address'][0]['text']);
        $this->assertEquals('08123456789', $result['telecom'][0]['value']);
        $this->assertEquals('john@example.com', $result['telecom'][1]['value']);
        $this->assertEquals('Jakarta', $result['extension'][0]['valueAddress']['city']);
        $this->assertEquals('S', $result['maritalStatus']['coding'][0]['code']);
        $this->assertEquals('Organization/test-org-id', $result['managingOrganization']['reference']);
        $this->assertTrue($result['active']);
    }

    /**
     * Test buildPatientResource with minimal data.
     */
    public function test_build_patient_resource_with_minimal_data(): void
    {
        $patient = $this->createMockPatient([
            'nik' => '1234567890123456',
            'name' => 'Jane Doe',
            'gender' => 'female',
            'birth_date' => new \DateTime('1995-05-20'),
            'address' => null,
            'phone' => null,
            'email' => null,
            'birth_place' => null,
            'marital_status' => null,
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('test-org-id');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPatientResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $patient);

        $this->assertArrayNotHasKey('address', $result);
        $this->assertArrayNotHasKey('telecom', $result);
        $this->assertArrayNotHasKey('extension', $result);
        $this->assertArrayNotHasKey('maritalStatus', $result);
    }

    /**
     * Data provider for gender mapping tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function genderMappingProvider(): array
    {
        return [
            'male_lowercase' => ['male', 'male'],
            'male_laki_laki' => ['laki-laki', 'male'],
            'male_l' => ['l', 'male'],
            'male_m' => ['m', 'male'],
            'female_lowercase' => ['female', 'female'],
            'female_perempuan' => ['perempuan', 'female'],
            'female_f' => ['f', 'female'],
            'female_p' => ['p', 'female'],
            'null_gender' => [null, 'unknown'],
            'empty_gender' => ['', 'unknown'],
            'unknown_gender' => ['other', 'unknown'],
        ];
    }

    /**
     * Test gender mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('genderMappingProvider')]
    public function test_map_gender(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapGender');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for marital status mapping tests.
     *
     * @return array<string, array{string|null, string}>
     */
    public static function maritalStatusMappingProvider(): array
    {
        return [
            'single' => ['single', 'S'],
            'belum_kawin' => ['belum kawin', 'S'],
            'bk' => ['bk', 'S'],
            'married' => ['married', 'M'],
            'kawin' => ['kawin', 'M'],
            'k' => ['k', 'M'],
            'divorced' => ['divorced', 'D'],
            'cerai' => ['cerai', 'D'],
            'c' => ['c', 'D'],
            'widowed' => ['widowed', 'W'],
            'janda' => ['janda', 'W'],
            'duda' => ['duda', 'W'],
            'j' => ['j', 'W'],
            'null_status' => [null, 'UNK'],
            'empty_status' => ['', 'UNK'],
            'unknown_status' => ['other', 'UNK'],
        ];
    }

    /**
     * Test marital status mapping.
     *
     * @param string|null $input
     * @param string $expected
     */
    #[DataProvider('maritalStatusMappingProvider')]
    public function test_map_marital_status(?string $input, string $expected): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapMaritalStatus');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test buildPatientResource with only phone (no email).
     */
    public function test_build_patient_resource_with_only_phone(): void
    {
        $patient = $this->createMockPatient([
            'phone' => '08123456789',
            'email' => null,
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('test-org-id');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPatientResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $patient);

        $this->assertCount(1, $result['telecom']);
        $this->assertEquals('phone', $result['telecom'][0]['system']);
    }

    /**
     * Test buildPatientResource with only email (no phone).
     */
    public function test_build_patient_resource_with_only_email(): void
    {
        $patient = $this->createMockPatient([
            'phone' => null,
            'email' => 'john@example.com',
        ]);

        $this->mockSatuSehat->shouldReceive('getOrganizationId')
            ->andReturn('test-org-id');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildPatientResource');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $patient);

        $this->assertCount(1, $result['telecom']);
        $this->assertEquals('email', $result['telecom'][0]['system']);
    }
}
