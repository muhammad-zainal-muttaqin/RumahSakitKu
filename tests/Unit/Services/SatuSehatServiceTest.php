<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SatuSehatLog;
use App\Services\SatuSehat\SatuSehatService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for SatuSehat Service.
 *
 * Tests OAuth token retrieval, header generation, FHIR requests,
 * and resource validation.
 */
class SatuSehatServiceTest extends TestCase
{
    private SatuSehatService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config(['satusehat.mode' => 'development']);
        config(['satusehat.development.auth_url' => 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1']);
        config(['satusehat.development.base_url' => 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1']);
        config(['satusehat.development.client_id' => 'test-client-id']);
        config(['satusehat.development.client_secret' => 'test-client-secret']);
        config(['satusehat.development.organization_id' => 'test-org-id']);
        config(['satusehat.timeout' => 60]);
        config(['satusehat.retry_times' => 3]);
        config(['satusehat.retry_sleep' => 1000]);
        config(['satusehat.cache_token' => true]);
        config(['satusehat.token_cache_key' => 'satusehat_access_token']);
        config(['satusehat.token_expires_in' => 3500]);

        // Create the service and mock the logging methods
        $this->service = $this->createServiceWithMockedLogging();
    }

    /**
     * Create a SatuSehatService with mocked logging methods.
     */
    private function createServiceWithMockedLogging(): SatuSehatService
    {
        $service = new SatuSehatService();

        // Create a mock log object that satisfies the type hint
        $mockLog = new class extends SatuSehatLog {
            public function update(array $attributes = [], array $options = [])
            {
                return $this;
            }
        };
        $mockLog->id = 1;

        // Use reflection to replace the logRequest method behavior
        $reflection = new \ReflectionClass($service);

        // Create a test subclass that overrides logRequest
        return new class($service, $mockLog) extends SatuSehatService {
            private $mockLog;

            public function __construct($originalService, $mockLog)
            {
                $this->mockLog = $mockLog;
                // Call parent constructor to initialize all properties
                parent::__construct();
            }

            protected function logRequest(string $resourceType, ?string $localType, ?int $localId, string $action, ?array $requestData = null): SatuSehatLog
            {
                return $this->mockLog;
            }
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    /**
     * Test service initialization with correct configuration.
     */
    public function test_service_initializes_with_correct_configuration(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $mode = $reflection->getProperty('mode');
        $mode->setAccessible(true);
        $this->assertEquals('development', $mode->getValue($this->service));

        $clientId = $reflection->getProperty('clientId');
        $clientId->setAccessible(true);
        $this->assertEquals('test-client-id', $clientId->getValue($this->service));

        $organizationId = $reflection->getProperty('organizationId');
        $organizationId->setAccessible(true);
        $this->assertEquals('test-org-id', $organizationId->getValue($this->service));
    }

    /**
     * Test getAccessToken success.
     */
    public function test_get_access_token_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $result = $this->service->getAccessToken();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals('test-access-token', $result['access_token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    /**
     * Test getAccessToken uses cached token.
     */
    public function test_get_access_token_uses_cached_token(): void
    {
        Cache::put('satusehat_access_token', 'cached-token', 3500);
        Cache::put('satusehat_access_token_expires', 3600, 3500);

        $result = $this->service->getAccessToken();

        $this->assertEquals('cached-token', $result['access_token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    /**
     * Test getAccessToken with failed response.
     */
    public function test_get_access_token_failure(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token request failed');

        $this->service->getAccessToken();
    }

    /**
     * Test getAccessToken with request exception.
     */
    public function test_get_access_token_request_exception(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $this->expectException(\Exception::class);

        $this->service->getAccessToken();
    }

    /**
     * Test refreshToken clears cache and gets new token.
     */
    public function test_refresh_token_clears_cache_and_gets_new_token(): void
    {
        Cache::put('satusehat_access_token', 'old-token', 3500);
        Cache::put('satusehat_access_token_expires', 3600, 3500);

        Http::fake([
            '*' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $result = $this->service->refreshToken();

        $this->assertEquals('new-access-token', $result['access_token']);
        // After refresh, the new token should be in cache
        $this->assertEquals('new-access-token', Cache::get('satusehat_access_token'));
    }

    /**
     * Test getHeaders returns correct authorization header.
     */
    public function test_get_headers_returns_authorization_header(): void
    {
        Http::fake([
            '*' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $headers = $this->service->getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertEquals('Bearer test-token', $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    /**
     * Test request method with POST.
     */
    public function test_request_post_success(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'patient-id-123',
            ], 201),
        ]);

        $result = $this->service->request('Patient', 'POST', [
            'resourceType' => 'Patient',
            'name' => [['text' => 'John Doe']],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(201, $result['status']);
        $this->assertArrayHasKey('id', $result['data']);
    }

    /**
     * Test request method with GET.
     */
    public function test_request_get_success(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient/patient-id-123' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'patient-id-123',
                'name' => [['text' => 'John Doe']],
            ], 200),
        ]);

        $result = $this->service->request('Patient', 'GET', null, 'patient-id-123');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertEquals('patient-id-123', $result['data']['id']);
    }

    /**
     * Test request method with PUT.
     */
    public function test_request_put_success(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient/patient-id-123' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'patient-id-123',
            ], 200),
        ]);

        $result = $this->service->request('Patient', 'PUT', [
            'resourceType' => 'Patient',
            'id' => 'patient-id-123',
            'name' => [['text' => 'Updated Name']],
        ], 'patient-id-123');

        $this->assertTrue($result['success']);
    }

    /**
     * Test request method with DELETE.
     */
    public function test_request_delete_success(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient/patient-id-123' => Http::response(null, 204),
        ]);

        $result = $this->service->request('Patient', 'DELETE', null, 'patient-id-123');

        $this->assertTrue($result['success']);
        $this->assertEquals(204, $result['status']);
    }

    /**
     * Test request method with unsupported HTTP method.
     */
    public function test_request_unsupported_method(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP method: OPTIONS');

        $this->service->request('Patient', 'OPTIONS', ['data' => 'test']);
    }

    /**
     * Test request method with error response.
     */
    public function test_request_error_response(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient' => Http::response([
                'resourceType' => 'OperationOutcome',
                'issue' => [
                    [
                        'severity' => 'error',
                        'diagnostics' => 'Invalid resource data',
                    ],
                ],
            ], 400),
        ]);

        $result = $this->service->request('Patient', 'POST', ['invalid' => 'data']);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['status']);
        $this->assertStringContainsString('Invalid resource data', $result['error']);
    }

    /**
     * Test search method success.
     */
    public function test_search_success(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient*' => Http::response([
                'resourceType' => 'Bundle',
                'type' => 'searchset',
                'total' => 1,
                'entry' => [
                    [
                        'resource' => [
                            'resourceType' => 'Patient',
                            'id' => 'patient-id-123',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->search('Patient', ['name' => 'John']);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertEquals('Bundle', $result['data']['resourceType']);
    }

    /**
     * Test search method with error.
     */
    public function test_search_error(): void
    {
        Cache::put('satusehat_access_token', 'test-token', 3500);
        Cache::put('satusehat_access_token_expires', 3600, 3500);

        Http::fake([
            '*/fhir-r4/v1/Patient*' => Http::response('Internal Server Error', 500),
        ]);

        $result = $this->service->search('Patient', ['name' => 'John']);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['status']);
    }

    /**
     * Data provider for resource validation tests.
     *
     * @return array<string, array{string, array<string, mixed>, bool, array<int, string>}>
     */
    public static function resourceValidationProvider(): array
    {
        return [
            'valid_patient' => [
                'Patient',
                [
                    'resourceType' => 'Patient',
                    'identifier' => [['system' => 'http://hl7.org/fhir/sid/us-ssn', 'value' => '123']],
                    'name' => [['text' => 'John Doe']],
                ],
                true,
                [],
            ],
            'patient_missing_identifier' => [
                'Patient',
                [
                    'resourceType' => 'Patient',
                    'name' => [['text' => 'John Doe']],
                ],
                false,
                ['Patient identifier is required'],
            ],
            'patient_missing_name' => [
                'Patient',
                [
                    'resourceType' => 'Patient',
                    'identifier' => [['value' => '123']],
                ],
                false,
                ['Patient name is required'],
            ],
            'patient_wrong_resource_type' => [
                'Patient',
                [
                    'resourceType' => 'Encounter',
                    'identifier' => [['value' => '123']],
                    'name' => [['text' => 'John Doe']],
                ],
                false,
                ['Resource type must be Patient'],
            ],
            'valid_encounter' => [
                'Encounter',
                [
                    'resourceType' => 'Encounter',
                    'status' => 'in-progress',
                    'class' => ['code' => 'AMB'],
                    'subject' => ['reference' => 'Patient/123'],
                ],
                true,
                [],
            ],
            'encounter_missing_status' => [
                'Encounter',
                [
                    'resourceType' => 'Encounter',
                    'class' => ['code' => 'AMB'],
                    'subject' => ['reference' => 'Patient/123'],
                ],
                false,
                ['Encounter status is required'],
            ],
            'encounter_missing_class' => [
                'Encounter',
                [
                    'resourceType' => 'Encounter',
                    'status' => 'in-progress',
                    'subject' => ['reference' => 'Patient/123'],
                ],
                false,
                ['Encounter class is required'],
            ],
            'encounter_missing_subject' => [
                'Encounter',
                [
                    'resourceType' => 'Encounter',
                    'status' => 'in-progress',
                    'class' => ['code' => 'AMB'],
                ],
                false,
                ['Encounter subject (patient) is required'],
            ],
            'valid_observation' => [
                'Observation',
                [
                    'resourceType' => 'Observation',
                    'status' => 'final',
                    'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => '8480-6']]],
                ],
                true,
                [],
            ],
            'observation_missing_status' => [
                'Observation',
                [
                    'resourceType' => 'Observation',
                    'code' => ['coding' => [['code' => '8480-6']]],
                ],
                false,
                ['Observation status is required'],
            ],
            'observation_missing_code' => [
                'Observation',
                [
                    'resourceType' => 'Observation',
                    'status' => 'final',
                ],
                false,
                ['Observation code is required'],
            ],
            'valid_condition' => [
                'Condition',
                [
                    'resourceType' => 'Condition',
                    'code' => ['coding' => [['code' => 'A00']]],
                    'subject' => ['reference' => 'Patient/123'],
                ],
                true,
                [],
            ],
            'condition_missing_code' => [
                'Condition',
                [
                    'resourceType' => 'Condition',
                    'subject' => ['reference' => 'Patient/123'],
                ],
                false,
                ['Condition code is required'],
            ],
            'condition_missing_subject' => [
                'Condition',
                [
                    'resourceType' => 'Condition',
                    'code' => ['coding' => [['code' => 'A00']]],
                ],
                false,
                ['Condition subject (patient) is required'],
            ],
            'valid_medication' => [
                'Medication',
                [
                    'resourceType' => 'Medication',
                    'code' => ['coding' => [['code' => 'M001']]],
                ],
                true,
                [],
            ],
            'medication_missing_code' => [
                'Medication',
                [
                    'resourceType' => 'Medication',
                ],
                false,
                ['Medication code is required'],
            ],
            'valid_medication_request' => [
                'MedicationRequest',
                [
                    'resourceType' => 'MedicationRequest',
                    'status' => 'active',
                    'intent' => 'order',
                    'medicationCodeableConcept' => ['text' => 'Paracetamol'],
                ],
                true,
                [],
            ],
            'medication_request_missing_status' => [
                'MedicationRequest',
                [
                    'resourceType' => 'MedicationRequest',
                    'intent' => 'order',
                    'medicationCodeableConcept' => ['text' => 'Paracetamol'],
                ],
                false,
                ['MedicationRequest status is required'],
            ],
            'medication_request_missing_intent' => [
                'MedicationRequest',
                [
                    'resourceType' => 'MedicationRequest',
                    'status' => 'active',
                    'medicationCodeableConcept' => ['text' => 'Paracetamol'],
                ],
                false,
                ['MedicationRequest intent is required'],
            ],
            'medication_request_missing_medication' => [
                'MedicationRequest',
                [
                    'resourceType' => 'MedicationRequest',
                    'status' => 'active',
                    'intent' => 'order',
                ],
                false,
                ['MedicationRequest medication is required'],
            ],
        ];
    }

    /**
     * Test resource validation.
     *
     * @param string $resourceType
     * @param array<string, mixed> $data
     * @param bool $expectedValid
     * @param array<int, string> $expectedErrors
     */
    #[DataProvider('resourceValidationProvider')]
    public function test_validate_resource(string $resourceType, array $data, bool $expectedValid, array $expectedErrors): void
    {
        $result = $this->service->validateResource($resourceType, $data);

        $this->assertEquals($expectedValid, $result['valid']);

        foreach ($expectedErrors as $error) {
            $this->assertContains($error, $result['errors']);
        }
    }

    /**
     * Test validateResource with missing resourceType.
     */
    public function test_validate_resource_missing_resource_type(): void
    {
        $result = $this->service->validateResource('Patient', ['name' => [['text' => 'John']]]);

        $this->assertFalse($result['valid']);
        $this->assertContains('Resource type is required', $result['errors']);
    }

    /**
     * Test getOrganizationId method.
     */
    public function test_get_organization_id(): void
    {
        $result = $this->service->getOrganizationId();

        $this->assertEquals('test-org-id', $result);
    }

    /**
     * Test getBaseUrl method.
     */
    public function test_get_base_url(): void
    {
        $result = $this->service->getBaseUrl();

        $this->assertStringContainsString('fhir-r4', $result);
    }

    /**
     * Test extractErrorMessage with issue array.
     */
    public function test_extract_error_message_with_issue_array(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractErrorMessage');
        $method->setAccessible(true);

        $errorData = [
            'issue' => [
                ['diagnostics' => 'Error 1'],
                ['details' => ['text' => 'Error 2']],
                ['unknown' => 'field'],
            ],
        ];

        $result = $method->invoke($this->service, $errorData);

        $this->assertStringContainsString('Error 1', $result);
        $this->assertStringContainsString('Error 2', $result);
        $this->assertStringContainsString('Unknown error', $result);
    }

    /**
     * Test extractErrorMessage with message field.
     */
    public function test_extract_error_message_with_message_field(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('extractErrorMessage');
        $method->setAccessible(true);

        $errorData = ['message' => 'Simple error message'];

        $result = $method->invoke($this->service, $errorData);

        $this->assertEquals('Simple error message', $result);
    }

    /**
     * Test buildUrl method.
     */
    public function test_build_url(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        // Without resource ID
        $url = $method->invoke($this->service, 'Patient', null);
        $this->assertStringEndsWith('/Patient', $url);

        // With resource ID
        $url = $method->invoke($this->service, 'Patient', 'patient-id-123');
        $this->assertStringEndsWith('/Patient/patient-id-123', $url);
    }

    /**
     * Test request with exception.
     */
    public function test_request_with_exception(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient' => function () {
                throw new \Exception('Unexpected error');
            },
        ]);

        $result = $this->service->request('Patient', 'POST', ['data' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['status']);
    }

    /**
     * Test search with exception.
     */
    public function test_search_with_exception(): void
    {
        Http::fake([
            '*/oauth2/v1/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/fhir-r4/v1/Patient*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $result = $this->service->search('Patient', ['name' => 'John']);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['status']);
    }

    /**
     * Test cache disabled.
     */
    public function test_cache_disabled(): void
    {
        config(['satusehat.cache_token' => false]);

        $service = new SatuSehatService();

        Http::fake([
            '*' => Http::response([
                'access_token' => 'new-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $result = $service->getAccessToken();

        $this->assertEquals('new-token', $result['access_token']);
    }
}
