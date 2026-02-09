<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        // Create users
        $this->adminUser = User::factory()->create([
            'is_active' => true,
            'email' => 'admin@example.com',
        ]);
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create([
            'is_active' => true,
            'email' => 'user@example.com',
        ]);
        $this->regularUser->assignRole('user');
    }

    #[Test]
    public function it_creates_audit_log_on_patient_creation(): void
    {
        $patientData = [
            'name' => 'John Doe',
            'nik' => '1234567890123456',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Jl. Example No. 123',
            'phone' => '08123456789',
        ];

        $this->actingAs($this->regularUser)
            ->post('/admin/patients', $patientData);

        $patient = Patient::where('nik', '1234567890123456')->first();
        $this->assertNotNull($patient);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->regularUser->id,
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'event' => 'created',
        ]);

        $auditLog = AuditLog::where('auditable_id', $patient->id)->first();
        $this->assertNull($auditLog->old_values);
        $this->assertNotNull($auditLog->new_values);
        $this->assertArrayHasKey('name', $auditLog->new_values);
        $this->assertEquals('John Doe', $auditLog->new_values['name']);
    }

    #[Test]
    public function it_creates_audit_log_on_patient_update(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'John Doe',
            'address' => 'Jl. Old Address',
        ]);

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", [
                'name' => 'John Doe Updated',
                'address' => 'Jl. New Address',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->regularUser->id,
            'auditable_type' => Patient::class,
            'auditable_id' => $patient->id,
            'event' => 'updated',
        ]);

        $auditLog = AuditLog::where('auditable_id', $patient->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($auditLog->old_values);
        $this->assertNotNull($auditLog->new_values);
        $this->assertEquals('John Doe', $auditLog->old_values['name']);
        $this->assertEquals('John Doe Updated', $auditLog->new_values['name']);
    }

    #[Test]
    public function it_creates_audit_log_on_patient_deletion(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'John Doe',
        ]);

        $patientId = $patient->id;

        $this->actingAs($this->adminUser)
            ->delete("/admin/patients/{$patientId}");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->adminUser->id,
            'auditable_type' => Patient::class,
            'auditable_id' => $patientId,
            'event' => 'deleted',
        ]);
    }

    #[Test]
    public function it_records_correct_user_id_in_audit_log(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", [
                'name' => 'Updated Name',
            ]);

        $auditLog = AuditLog::where('auditable_id', $patient->id)->first();
        $this->assertEquals($this->regularUser->id, $auditLog->user_id);
        $this->assertEquals(User::class, $auditLog->user_type);
    }

    #[Test]
    public function it_records_ip_address_in_audit_log(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->put("/admin/patients/{$patient->id}", [
                'name' => 'Updated Name',
            ]);

        $auditLog = AuditLog::where('auditable_id', $patient->id)->first();
        $this->assertNotNull($auditLog->ip_address);
    }

    #[Test]
    public function it_records_user_agent_in_audit_log(): void
    {
        $patient = Patient::factory()->create();

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $this->actingAs($this->regularUser)
            ->withHeaders(['User-Agent' => $userAgent])
            ->put("/admin/patients/{$patient->id}", [
                'name' => 'Updated Name',
            ]);

        $auditLog = AuditLog::where('auditable_id', $patient->id)->first();
        $this->assertNotNull($auditLog->user_agent);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_user(): void
    {
        // Create patient and modify by different users
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient1->id}", ['name' => 'User Update']);

        $this->actingAs($this->adminUser)
            ->put("/admin/patients/{$patient2->id}", ['name' => 'Admin Update']);

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/audit-logs?user_id={$this->regularUser->id}");

        $response->assertStatus(200);

        // Filter using scope
        $logs = AuditLog::byUser($this->regularUser->id)->get();
        $this->assertCount(1, $logs);
        $this->assertEquals($this->regularUser->id, $logs->first()->user_id);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_action(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        // Create action
        $this->actingAs($this->regularUser)
            ->post('/admin/patients', [
                'name' => 'New Patient',
                'nik' => '1111111111111111',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
            ]);

        // Update action
        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient1->id}", ['name' => 'Updated']);

        // Delete action
        $this->actingAs($this->regularUser)
            ->delete("/admin/patients/{$patient2->id}");

        // Filter by event
        $createdLogs = AuditLog::byEvent('created')->get();
        $updatedLogs = AuditLog::byEvent('updated')->get();
        $deletedLogs = AuditLog::byEvent('deleted')->get();

        $this->assertCount(1, $createdLogs);
        $this->assertCount(1, $updatedLogs);
        $this->assertCount(1, $deletedLogs);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_model_type(): void
    {
        $patient = Patient::factory()->create();
        $employee = Employee::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", ['name' => 'Updated Patient']);

        $this->actingAs($this->adminUser)
            ->put("/admin/employees/{$employee->id}", ['name' => 'Updated Employee']);

        // Filter by model type
        $patientLogs = AuditLog::byModelType(Patient::class)->get();
        $employeeLogs = AuditLog::byModelType(Employee::class)->get();

        $this->assertCount(1, $patientLogs);
        $this->assertCount(1, $employeeLogs);
        $this->assertEquals(Patient::class, $patientLogs->first()->auditable_type);
        $this->assertEquals(Employee::class, $employeeLogs->first()->auditable_type);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_date_range(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", ['name' => 'Updated']);

        $logs = AuditLog::dateRange(
            now()->subDay()->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        )->get();

        $this->assertCount(1, $logs);

        $logsOutsideRange = AuditLog::dateRange(
            now()->subDays(5)->format('Y-m-d'),
            now()->subDays(3)->format('Y-m-d')
        )->get();

        $this->assertCount(0, $logsOutsideRange);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_patient(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $visit = Visit::factory()->create([
            'patient_id' => $patient1->id,
        ]);

        $this->actingAs($this->regularUser)
            ->put("/admin/visits/{$visit->id}", ['complaint' => 'Updated Complaint']);

        // Filter by patient
        $logs = AuditLog::byPatient($patient1->id)->get();
        $this->assertCount(1, $logs);
        $this->assertEquals($patient1->id, $logs->first()->patient_id);
    }

    #[Test]
    public function it_provides_event_color_attribute(): void
    {
        $createdLog = AuditLog::factory()->create(['event' => 'created']);
        $updatedLog = AuditLog::factory()->create(['event' => 'updated']);
        $deletedLog = AuditLog::factory()->create(['event' => 'deleted']);

        $this->assertEquals('success', $createdLog->event_color);
        $this->assertEquals('warning', $updatedLog->event_color);
        $this->assertEquals('danger', $deletedLog->event_color);
    }

    #[Test]
    public function it_provides_event_label_attribute(): void
    {
        $createdLog = AuditLog::factory()->create(['event' => 'created']);
        $updatedLog = AuditLog::factory()->create(['event' => 'updated']);

        $this->assertEquals('Dibuat', $createdLog->event_label);
        $this->assertEquals('Diperbarui', $updatedLog->event_label);
    }

    #[Test]
    public function it_provides_model_type_label_attribute(): void
    {
        $patientLog = AuditLog::factory()->create([
            'auditable_type' => Patient::class,
        ]);

        $this->assertEquals('Pasien', $patientLog->model_type_label);
    }

    #[Test]
    public function it_provides_changes_summary_attribute(): void
    {
        $createdLog = AuditLog::factory()->create([
            'event' => 'created',
            'old_values' => null,
            'new_values' => ['name' => 'Test'],
        ]);

        $updatedLog = AuditLog::factory()->create([
            'event' => 'updated',
            'old_values' => ['name' => 'Old', 'address' => 'Old Addr'],
            'new_values' => ['name' => 'New', 'address' => 'New Addr'],
        ]);

        $this->assertEquals('Record baru dibuat', $createdLog->changes_summary);
        $this->assertStringContainsString('field diubah', $updatedLog->changes_summary);
    }

    #[Test]
    public function it_records_url_in_audit_log(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", ['name' => 'Updated']);

        $auditLog = AuditLog::where('auditable_id', $patient->id)->first();
        $this->assertNotNull($auditLog->url);
        $this->assertStringContainsString('admin/patients', $auditLog->url);
    }

    #[Test]
    public function it_can_view_audit_log_details(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", ['name' => 'Updated']);

        $auditLog = AuditLog::first();

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/audit-logs/{$auditLog->id}");

        $response->assertStatus(200);
        $response->assertSee('Updated');
    }

    #[Test]
    public function it_tracks_multiple_field_changes_in_single_update(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Original Name',
            'address' => 'Original Address',
            'phone' => '0811111111',
        ]);

        $this->actingAs($this->regularUser)
            ->put("/admin/patients/{$patient->id}", [
                'name' => 'New Name',
                'address' => 'New Address',
                'phone' => '0822222222',
            ]);

        $auditLog = AuditLog::where('event', 'updated')->first();

        $this->assertCount(3, $auditLog->old_values);
        $this->assertCount(3, $auditLog->new_values);
    }
}
