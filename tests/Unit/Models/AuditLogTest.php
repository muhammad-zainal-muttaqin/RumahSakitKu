<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Patient\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $auditLog = new AuditLog();

        $expectedFillable = [
            'user_id',
            'user_type',
            'patient_id',
            'auditable_type',
            'auditable_id',
            'event',
            'old_values',
            'new_values',
            'ip_address',
            'user_agent',
            'url',
            'created_at',
        ];

        $this->assertEquals($expectedFillable, $auditLog->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $auditLog = new AuditLog();
        $casts = $auditLog->getCasts();

        $this->assertArrayHasKey('old_values', $casts);
        $this->assertArrayHasKey('new_values', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertFalse($auditLog->timestamps);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $auditLog = new AuditLog();
        $relation = $auditLog->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $auditLog = new AuditLog();
        $relation = $auditLog->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_auditable_morph_relationship(): void
    {
        $auditLog = new AuditLog();
        $relation = $auditLog->auditable();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    #[Test]
    public function it_has_by_user_scope(): void
    {
        $user = User::factory()->create();
        AuditLog::factory()->count(3)->create();
        AuditLog::factory()->count(2)->create(['user_id' => $user->id]);

        $results = AuditLog::byUser($user->id)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->user_id === $user->id));
    }

    #[Test]
    public function it_has_by_event_scope(): void
    {
        AuditLog::factory()->count(3)->create(['event' => 'created']);
        AuditLog::factory()->count(2)->create(['event' => 'updated']);

        $results = AuditLog::byEvent('created')->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($log) => $log->event === 'created'));
    }

    #[Test]
    public function it_has_by_model_type_scope(): void
    {
        AuditLog::factory()->count(3)->create(['auditable_type' => 'App\Models\Patient\Patient']);
        AuditLog::factory()->count(2)->create(['auditable_type' => 'App\Models\Patient\Visit']);

        $results = AuditLog::byModelType('App\Models\Patient\Patient')->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($log) => $log->auditable_type === 'App\Models\Patient\Patient'));
    }

    #[Test]
    public function it_has_date_range_scope(): void
    {
        AuditLog::factory()->count(3)->create(['created_at' => now()->subDays(5)]);
        AuditLog::factory()->count(2)->create(['created_at' => now()]);

        $results = AuditLog::dateRange(
            now()->subDays(2)->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        )->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_by_patient_scope(): void
    {
        $patient = Patient::factory()->create();
        AuditLog::factory()->count(3)->create();
        AuditLog::factory()->count(2)->create(['patient_id' => $patient->id]);

        $results = AuditLog::byPatient($patient->id)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->patient_id === $patient->id));
    }

    #[Test]
    public function it_returns_correct_event_color_attribute(): void
    {
        $created = new AuditLog(['event' => 'created']);
        $updated = new AuditLog(['event' => 'updated']);
        $deleted = new AuditLog(['event' => 'deleted']);
        $restored = new AuditLog(['event' => 'restored']);
        $forceDeleted = new AuditLog(['event' => 'force_deleted']);
        $default = new AuditLog(['event' => 'unknown']);

        $this->assertEquals('success', $created->event_color);
        $this->assertEquals('warning', $updated->event_color);
        $this->assertEquals('danger', $deleted->event_color);
        $this->assertEquals('info', $restored->event_color);
        $this->assertEquals('gray', $forceDeleted->event_color);
        $this->assertEquals('gray', $default->event_color);
    }

    #[Test]
    public function it_returns_correct_event_icon_attribute(): void
    {
        $created = new AuditLog(['event' => 'created']);
        $updated = new AuditLog(['event' => 'updated']);
        $deleted = new AuditLog(['event' => 'deleted']);
        $restored = new AuditLog(['event' => 'restored']);
        $forceDeleted = new AuditLog(['event' => 'force_deleted']);
        $default = new AuditLog(['event' => 'unknown']);

        $this->assertEquals('heroicon-o-plus-circle', $created->event_icon);
        $this->assertEquals('heroicon-o-pencil-square', $updated->event_icon);
        $this->assertEquals('heroicon-o-trash', $deleted->event_icon);
        $this->assertEquals('heroicon-o-arrow-uturn-left', $restored->event_icon);
        $this->assertEquals('heroicon-o-x-circle', $forceDeleted->event_icon);
        $this->assertEquals('heroicon-o-question-mark-circle', $default->event_icon);
    }

    #[Test]
    public function it_returns_correct_event_label_attribute(): void
    {
        $created = new AuditLog(['event' => 'created']);
        $updated = new AuditLog(['event' => 'updated']);
        $deleted = new AuditLog(['event' => 'deleted']);
        $restored = new AuditLog(['event' => 'restored']);
        $forceDeleted = new AuditLog(['event' => 'force_deleted']);

        $this->assertEquals('Dibuat', $created->event_label);
        $this->assertEquals('Diperbarui', $updated->event_label);
        $this->assertEquals('Dihapus', $deleted->event_label);
        $this->assertEquals('Dipulihkan', $restored->event_label);
        $this->assertEquals('Dihapus Permanen', $forceDeleted->event_label);
    }

    #[Test]
    public function it_returns_model_type_label_attribute(): void
    {
        $patientLog = new AuditLog(['auditable_type' => 'App\Models\Patient\Patient']);
        $visitLog = new AuditLog(['auditable_type' => 'App\Models\Patient\Visit']);
        $medicalRecordLog = new AuditLog(['auditable_type' => 'App\Models\Clinical\MedicalRecord']);
        $prescriptionLog = new AuditLog(['auditable_type' => 'App\Models\Clinical\Prescription']);
        $labOrderLog = new AuditLog(['auditable_type' => 'App\Models\Clinical\LaboratoryOrder']);
        $radOrderLog = new AuditLog(['auditable_type' => 'App\Models\Clinical\RadiologyOrder']);
        $unknownLog = new AuditLog(['auditable_type' => 'App\Models\Unknown\Model']);

        $this->assertEquals('Pasien', $patientLog->model_type_label);
        $this->assertEquals('Kunjungan', $visitLog->model_type_label);
        $this->assertEquals('Rekam Medis', $medicalRecordLog->model_type_label);
        $this->assertEquals('Resep', $prescriptionLog->model_type_label);
        $this->assertEquals('Order Laboratorium', $labOrderLog->model_type_label);
        $this->assertEquals('Order Radiologi', $radOrderLog->model_type_label);
        $this->assertEquals('Model', $unknownLog->model_type_label);
    }

    #[Test]
    public function it_returns_changes_summary_for_created_event(): void
    {
        $auditLog = new AuditLog(['event' => 'created']);

        $this->assertEquals('Record baru dibuat', $auditLog->changes_summary);
    }

    #[Test]
    public function it_returns_changes_summary_for_deleted_event(): void
    {
        $auditLog = new AuditLog(['event' => 'deleted']);

        $this->assertEquals('Record dihapus', $auditLog->changes_summary);
    }

    #[Test]
    public function it_returns_changes_summary_for_restored_event(): void
    {
        $auditLog = new AuditLog(['event' => 'restored']);

        $this->assertEquals('Record dipulihkan', $auditLog->changes_summary);
    }

    #[Test]
    public function it_returns_changes_summary_for_updated_event_with_single_field(): void
    {
        $auditLog = new AuditLog([
            'event' => 'updated',
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);

        $this->assertEquals("Field 'name' diubah", $auditLog->changes_summary);
    }

    #[Test]
    public function it_returns_changes_summary_for_updated_event_with_multiple_fields(): void
    {
        $auditLog = new AuditLog([
            'event' => 'updated',
            'old_values' => ['name' => 'Old', 'email' => 'old@test.com', 'phone' => '123'],
            'new_values' => ['name' => 'New', 'email' => 'new@test.com', 'phone' => '456'],
        ]);

        $summary = $auditLog->changes_summary;
        $this->assertStringContainsString('3 field diubah', $summary);
        $this->assertStringContainsString('name', $summary);
        $this->assertStringContainsString('email', $summary);
        $this->assertStringContainsString('phone', $summary);
    }

    #[Test]
    public function it_returns_changes_summary_with_ellipsis_for_many_fields(): void
    {
        $auditLog = new AuditLog([
            'event' => 'updated',
            'old_values' => ['f1' => 'v1', 'f2' => 'v2', 'f3' => 'v3', 'f4' => 'v4', 'f5' => 'v5'],
            'new_values' => ['f1' => 'v1', 'f2' => 'v2', 'f3' => 'v3', 'f4' => 'v4', 'f5' => 'v5'],
        ]);

        $summary = $auditLog->changes_summary;
        $this->assertStringContainsString('5 field diubah', $summary);
        $this->assertStringContainsString('...', $summary);
    }

    #[Test]
    public function it_returns_null_changes_summary_when_no_values(): void
    {
        $auditLog = new AuditLog([
            'event' => 'updated',
            'old_values' => null,
            'new_values' => null,
        ]);

        $this->assertNull($auditLog->changes_summary);
    }

    #[Test]
    public function it_can_create_for_created_event(): void
    {
        $log = AuditLog::factory()->created()->create();

        $this->assertEquals('created', $log->event);
        $this->assertEquals('Dibuat', $log->event_label);
        $this->assertEquals('success', $log->event_color);
    }

    #[Test]
    public function it_can_create_for_updated_event(): void
    {
        $log = AuditLog::factory()->updated()->create();

        $this->assertEquals('updated', $log->event);
        $this->assertEquals('Diperbarui', $log->event_label);
        $this->assertEquals('warning', $log->event_color);
    }

    #[Test]
    public function it_can_create_for_deleted_event(): void
    {
        $log = AuditLog::factory()->deleted()->create();

        $this->assertEquals('deleted', $log->event);
        $this->assertEquals('Dihapus', $log->event_label);
        $this->assertEquals('danger', $log->event_color);
    }

    #[Test]
    public function it_can_create_with_patient(): void
    {
        $patient = Patient::factory()->create();
        $log = AuditLog::factory()->forPatient($patient->id)->create();

        $this->assertEquals($patient->id, $log->patient_id);
    }

    #[Test]
    public function it_can_create_for_model(): void
    {
        $patient = Patient::factory()->create();
        $log = AuditLog::factory()->forModel($patient)->create();

        $this->assertEquals(get_class($patient), $log->auditable_type);
        $this->assertEquals($patient->id, $log->auditable_id);
    }
}
