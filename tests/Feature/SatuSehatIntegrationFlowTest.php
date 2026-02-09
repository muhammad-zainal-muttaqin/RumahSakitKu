<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\SatuSehatLog;
use App\Models\User;
use App\Services\SatuSehat\SatuSehatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SatuSehatIntegrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $doctorUser;
    protected Patient $patient;
    protected Visit $visit;
    protected MedicalRecord $medicalRecord;
    protected Polyclinic $polyclinic;
    protected Employee $doctor;
    protected SatuSehatService $satuSehatService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        // Create polyclinic
        $this->polyclinic = Polyclinic::factory()->create([
            'name' => 'Poli Umum',
            'is_active' => true,
        ]);

        // Create doctor
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $this->polyclinic->id,
            'status' => 'aktif',
        ]);

        // Create users
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->doctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->doctor->id,
        ]);
        $this->doctorUser->assignRole('doctor');

        // Create patient
        $this->patient = Patient::factory()->create([
            'name' => 'Budi Santoso',
            'nik' => '3175091234567890',
            'birth_date' => '1990-05-15',
            'gender' => 'L',
        ]);

        // Create visit
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now(),
            'visit_type' => 'rawat_jalan',
        ]);

        // Create medical record
        $this->medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'visit_date' => now(),
        ]);

        // Clear cache
        Cache::flush();

        // Mock HTTP facade
        Http::preventStrayRequests();
    }

    /**
     * Test can generate IHS (Indonesia Health Services) number for patient.
     */
    public function test_can_generate_ihs_number_for_patient(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Patient' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'P123456789',
                'meta' => [
                    'versionId' => '1',
                ],
                'identifier' => [
                    [
                        'system' => 'https://fhir.kemkes.go.id/id/nik',
                        'value' => '3175091234567890',
                    ],
                ],
                'name' => [
                    [
                        'text' => 'Budi Santoso',
                    ],
                ],
            ], 201),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/satusehat/patients/{$this->patient->id}/generate-ihs");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->patient->refresh();
        $this->assertNotNull($this->patient->satusehat_ihs_number);
        $this->assertEquals('P123456789', $this->patient->satusehat_ihs_number);
    }

    /**
     * Test IHS generation creates SatuSehatLog entry.
     */
    public function test_ihs_generation_creates_satusehat_log_entry(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Patient' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'P123456789',
            ], 201),
        ]);

        $this->actingAs($this->adminUser)
            ->post("/admin/satusehat/patients/{$this->patient->id}/generate-ihs");

        $this->assertDatabaseHas('satu_sehat_logs', [
            'resource_type' => 'Patient',
            'local_type' => 'patient',
            'local_id' => $this->patient->id,
            'action' => 'POST',
            'status' => 'success',
        ]);
    }

    /**
     * Test can create encounter in Satu Sehat.
     */
    public function test_can_create_encounter_in_satu_sehat(): void
    {
        $this->patient->update(['satusehat_ihs_number' => 'P123456789']);

        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Encounter' => Http::response([
                'resourceType' => 'Encounter',
                'id' => 'E987654321',
                'status' => 'finished',
                'subject' => [
                    'reference' => 'Patient/P123456789',
                ],
                'period' => [
                    'start' => now()->toIso8601String(),
                    'end' => now()->addHour()->toIso8601String(),
                ],
            ], 201),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/visits/{$this->visit->id}/create-encounter");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->visit->refresh();
        $this->assertNotNull($this->visit->satusehat_encounter_id);
        $this->assertEquals('E987654321', $this->visit->satusehat_encounter_id);
    }

    /**
     * Test encounter creation requires IHS number.
     */
    public function test_encounter_creation_requires_ihs_number(): void
    {
        // Patient does not have IHS number
        $this->assertNull($this->patient->satusehat_ihs_number);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/visits/{$this->visit->id}/create-encounter");

        $response->assertSessionHasErrors();
        $response->assertSessionHas('error');
    }

    /**
     * Test can send observation to Satu Sehat.
     */
    public function test_can_send_observation_to_satu_sehat(): void
    {
        $this->patient->update(['satusehat_ihs_number' => 'P123456789']);
        $this->visit->update(['satusehat_encounter_id' => 'E987654321']);

        $assessment = Assessment::factory()->create([
            'medical_record_id' => $this->medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'vital_signs' => [
                'systolic' => 120,
                'diastolic' => 80,
                'heart_rate' => 80,
                'temperature' => 36.5,
            ],
        ]);

        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Observation' => Http::response([
                'resourceType' => 'Observation',
                'id' => 'O111222333',
                'status' => 'final',
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://loinc.org',
                            'code' => '8480-6',
                            'display' => 'Systolic blood pressure',
                        ],
                    ],
                ],
                'subject' => [
                    'reference' => 'Patient/P123456789',
                ],
                'encounter' => [
                    'reference' => 'Encounter/E987654321',
                ],
                'valueQuantity' => [
                    'value' => 120,
                    'unit' => 'mmHg',
                ],
            ], 201),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/assessments/{$assessment->id}/send-observation");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('satu_sehat_logs', [
            'resource_type' => 'Observation',
            'local_type' => 'assessment',
            'local_id' => $assessment->id,
            'status' => 'success',
        ]);
    }

    /**
     * Test can send multiple observations.
     */
    public function test_can_send_multiple_observations(): void
    {
        $this->patient->update(['satusehat_ihs_number' => 'P123456789']);
        $this->visit->update(['satusehat_encounter_id' => 'E987654321']);

        $assessment = Assessment::factory()->create([
            'medical_record_id' => $this->medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'vital_signs' => [
                'systolic' => 120,
                'diastolic' => 80,
                'heart_rate' => 80,
                'temperature' => 36.5,
                'respiratory_rate' => 20,
                'oxygen_saturation' => 98,
            ],
        ]);

        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Observation' => Http::sequence([
                Http::response(['resourceType' => 'Observation', 'id' => 'O1'], 201),
                Http::response(['resourceType' => 'Observation', 'id' => 'O2'], 201),
                Http::response(['resourceType' => 'Observation', 'id' => 'O3'], 201),
                Http::response(['resourceType' => 'Observation', 'id' => 'O4'], 201),
                Http::response(['resourceType' => 'Observation', 'id' => 'O5'], 201),
                Http::response(['resourceType' => 'Observation', 'id' => 'O6'], 201),
            ]),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/assessments/{$assessment->id}/send-all-observations");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify multiple logs created
        $this->assertEquals(6, SatuSehatLog::where('local_id', $assessment->id)->count());
    }

    /**
     * Test Satu Sehat API error handling.
     */
    public function test_satu_sehat_api_error_handling(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ], 200),
            '*/Patient' => Http::response([
                'resourceType' => 'OperationOutcome',
                'issue' => [
                    [
                        'severity' => 'error',
                        'code' => 'invalid',
                        'diagnostics' => 'Invalid patient data',
                    ],
                ],
            ], 400),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/satusehat/patients/{$this->patient->id}/generate-ihs");

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('satu_sehat_logs', [
            'local_type' => 'patient',
            'local_id' => $this->patient->id,
            'status' => 'failed',
        ]);
    }

    /**
     * Test Satu Sehat token caching.
     */
    public function test_satu_sehat_token_caching(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'cached-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        $service = new SatuSehatService();

        // First call should hit the API
        $token1 = $service->getAccessToken();
        $this->assertEquals('cached-token', $token1['access_token']);

        // Second call should use cached token
        $token2 = $service->getAccessToken();
        $this->assertEquals('cached-token', $token2['access_token']);

        // API should only be called once
        Http::assertSentCount(1);
    }

    /**
     * Test Satu Sehat token refresh on 401.
     */
    public function test_satu_sehat_token_refresh_on_401(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::sequence([
                Http::response(['access_token' => 'first-token', 'expires_in' => 3600], 200),
                Http::response(['access_token' => 'refreshed-token', 'expires_in' => 3600], 200),
            ]),
            '*/Patient/*' => Http::sequence([
                Http::response(['error' => 'Unauthorized'], 401),
                Http::response([
                    'resourceType' => 'Patient',
                    'id' => 'P123',
                ], 200),
            ]),
        ]);

        $service = new SatuSehatService();

        // First request fails with 401, should retry with new token
        $result = $service->request('Patient', 'GET', null, 'P123');

        $this->assertTrue($result['success']);
    }

    /**
     * Test FHIR resource validation.
     */
    public function test_fhir_resource_validation(): void
    {
        $service = new SatuSehatService();

        // Valid Patient resource
        $validPatient = [
            'resourceType' => 'Patient',
            'identifier' => [
                ['system' => 'https://fhir.kemkes.go.id/id/nik', 'value' => '3175091234567890'],
            ],
            'name' => [
                ['text' => 'Budi Santoso'],
            ],
        ];

        $validation = $service->validateResource('Patient', $validPatient);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);

        // Invalid Patient resource (missing name)
        $invalidPatient = [
            'resourceType' => 'Patient',
            'identifier' => [
                ['system' => 'https://fhir.kemkes.go.id/id/nik', 'value' => '3175091234567890'],
            ],
        ];

        $validation = $service->validateResource('Patient', $invalidPatient);
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /**
     * Test Encounter resource validation.
     */
    public function test_encounter_resource_validation(): void
    {
        $service = new SatuSehatService();

        // Valid Encounter
        $validEncounter = [
            'resourceType' => 'Encounter',
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
            ],
            'subject' => [
                'reference' => 'Patient/P123',
            ],
        ];

        $validation = $service->validateResource('Encounter', $validEncounter);
        $this->assertTrue($validation['valid']);

        // Invalid Encounter (missing status)
        $invalidEncounter = [
            'resourceType' => 'Encounter',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
            ],
            'subject' => [
                'reference' => 'Patient/P123',
            ],
        ];

        $validation = $service->validateResource('Encounter', $invalidEncounter);
        $this->assertFalse($validation['valid']);
        $this->assertContains('Encounter status is required', $validation['errors']);
    }

    /**
     * Test Observation resource validation.
     */
    public function test_observation_resource_validation(): void
    {
        $service = new SatuSehatService();

        // Valid Observation
        $validObservation = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => '8480-6',
                    ],
                ],
            ],
        ];

        $validation = $service->validateResource('Observation', $validObservation);
        $this->assertTrue($validation['valid']);

        // Invalid Observation (missing code)
        $invalidObservation = [
            'resourceType' => 'Observation',
            'status' => 'final',
        ];

        $validation = $service->validateResource('Observation', $invalidObservation);
        $this->assertFalse($validation['valid']);
        $this->assertContains('Observation code is required', $validation['errors']);
    }

    /**
     * Test can view Satu Sehat logs.
     */
    public function test_can_view_satu_sehat_logs(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'local_type' => 'patient',
            'local_id' => $this->patient->id,
            'action' => 'POST',
            'fhir_id' => 'P123456',
            'request_data' => ['name' => 'Test'],
            'response_data' => ['id' => 'P123456'],
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/satusehat/logs');

        $response->assertStatus(200);
        $response->assertSee('Patient');
        $response->assertSee('P123456');
    }

    /**
     * Test can filter Satu Sehat logs by resource type.
     */
    public function test_can_filter_satu_sehat_logs_by_resource_type(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'local_type' => 'patient',
            'local_id' => 1,
            'action' => 'POST',
            'status' => 'success',
        ]);

        SatuSehatLog::create([
            'resource_type' => 'Encounter',
            'local_type' => 'visit',
            'local_id' => 1,
            'action' => 'POST',
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/satusehat/logs?resource_type=Patient');

        $response->assertStatus(200);
        $response->assertSee('Patient');
    }

    /**
     * Test can resend failed Satu Sehat request.
     */
    public function test_can_resend_failed_satu_sehat_request(): void
    {
        $log = SatuSehatLog::create([
            'resource_type' => 'Observation',
            'local_type' => 'assessment',
            'local_id' => 1,
            'action' => 'POST',
            'request_data' => ['status' => 'final'],
            'status' => 'failed',
            'error_message' => 'Network error',
        ]);

        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/Observation' => Http::response([
                'resourceType' => 'Observation',
                'id' => 'O999',
            ], 201),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/satusehat/logs/{$log->id}/retry");

        $response->assertRedirect();

        $log->refresh();
        $this->assertEquals('success', $log->status);
        $this->assertEquals('O999', $log->fhir_id);
    }

    /**
     * Test complete Satu Sehat workflow.
     */
    public function test_complete_satu_sehat_workflow(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/Patient' => Http::response([
                'resourceType' => 'Patient',
                'id' => 'P123456789',
            ], 201),
            '*/Encounter' => Http::response([
                'resourceType' => 'Encounter',
                'id' => 'E987654321',
            ], 201),
            '*/Observation' => Http::response([
                'resourceType' => 'Observation',
                'id' => 'O111222333',
            ], 201),
        ]);

        // 1. Generate IHS for patient
        $ihsResponse = $this->actingAs($this->adminUser)
            ->post("/admin/satusehat/patients/{$this->patient->id}/generate-ihs");
        $ihsResponse->assertRedirect();

        $this->patient->refresh();
        $this->assertNotNull($this->patient->satusehat_ihs_number);

        // 2. Create encounter
        $encounterResponse = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/visits/{$this->visit->id}/create-encounter");
        $encounterResponse->assertRedirect();

        $this->visit->refresh();
        $this->assertNotNull($this->visit->satusehat_encounter_id);

        // 3. Create assessment with vital signs
        $assessment = Assessment::factory()->create([
            'medical_record_id' => $this->medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'vital_signs' => [
                'systolic' => 120,
                'diastolic' => 80,
                'heart_rate' => 80,
            ],
        ]);

        // 4. Send observations
        $obsResponse = $this->actingAs($this->doctorUser)
            ->post("/admin/satusehat/assessments/{$assessment->id}/send-observation");
        $obsResponse->assertRedirect();

        // 5. Verify logs
        $this->assertDatabaseHas('satu_sehat_logs', [
            'resource_type' => 'Patient',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('satu_sehat_logs', [
            'resource_type' => 'Encounter',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('satu_sehat_logs', [
            'resource_type' => 'Observation',
            'status' => 'success',
        ]);
    }

    /**
     * Test Satu Sehat service configuration.
     */
    public function test_satu_sehat_service_configuration(): void
    {
        config([
            'satusehat.mode' => 'development',
            'satusehat.development.auth_url' => 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2',
            'satusehat.development.base_url' => 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1',
            'satusehat.development.client_id' => 'test-client-id',
            'satusehat.development.client_secret' => 'test-client-secret',
            'satusehat.development.organization_id' => 'test-org-id',
        ]);

        $service = new SatuSehatService();

        $this->assertEquals('test-org-id', $service->getOrganizationId());
        $this->assertEquals('https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1', $service->getBaseUrl());
    }

    /**
     * Test Satu Sehat search functionality.
     */
    public function test_satu_sehat_search_functionality(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::response([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ], 200),
            '*/Patient*' => Http::response([
                'resourceType' => 'Bundle',
                'type' => 'searchset',
                'total' => 1,
                'entry' => [
                    [
                        'resource' => [
                            'resourceType' => 'Patient',
                            'id' => 'P123',
                            'name' => [
                                ['text' => 'Budi Santoso'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new SatuSehatService();
        $result = $service->search('Patient', ['name' => 'Budi']);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['total']);
    }

    /**
     * Test Satu Sehat timeout handling.
     */
    public function test_satu_sehat_timeout_handling(): void
    {
        Http::fake([
            '*/oauth2/access_token' => Http::timeout(),
        ]);

        $service = new SatuSehatService();

        $this->expectException(\Exception::class);
        $service->getAccessToken();
    }

    /**
     * Test Satu Sehat resource type validation.
     */
    public function test_satu_sehat_resource_type_validation(): void
    {
        $service = new SatuSehatService();

        // Test unsupported resource type
        $result = $service->validateResource('UnsupportedResource', [
            'resourceType' => 'UnsupportedResource',
        ]);

        $this->assertTrue($result['valid']); // No specific validation for unsupported types
    }
}
