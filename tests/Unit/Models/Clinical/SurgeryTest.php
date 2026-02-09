<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Surgery;
use App\Models\Clinical\SurgeryImplant;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SurgeryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $surgery = new Surgery();

        $expectedFillable = [
            'surgery_number',
            'visit_id',
            'patient_id',
            'scheduled_date',
            'start_time',
            'estimated_end_time',
            'actual_start',
            'actual_end',
            'operating_room',
            'surgeon_id',
            'assistant_surgeon_id',
            'anesthesiologist_id',
            'anesthesia_type',
            'nurse_id',
            'circulating_nurse_id',
            'pre_diagnosis',
            'post_diagnosis',
            'procedure_name',
            'procedure_code',
            'surgery_type',
            'status',
            'safety_checklist_sign_in',
            'safety_checklist_sign_in_at',
            'safety_checklist_time_out',
            'safety_checklist_time_out_at',
            'safety_checklist_sign_out',
            'safety_checklist_sign_out_at',
            'procedure_notes',
            'findings',
            'complications',
            'specimens',
            'is_postponed',
            'postponed_reason',
            'postponed_at',
            'cancelled_at',
            'cancelled_by',
            'cancellation_reason',
            'notes',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $surgery->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $surgery = new Surgery();
        $casts = $surgery->getCasts();

        $this->assertArrayHasKey('scheduled_date', $casts);
        $this->assertArrayHasKey('start_time', $casts);
        $this->assertArrayHasKey('estimated_end_time', $casts);
        $this->assertArrayHasKey('actual_start', $casts);
        $this->assertArrayHasKey('actual_end', $casts);
        $this->assertArrayHasKey('safety_checklist_sign_in', $casts);
        $this->assertArrayHasKey('safety_checklist_sign_in_at', $casts);
        $this->assertArrayHasKey('safety_checklist_time_out', $casts);
        $this->assertArrayHasKey('safety_checklist_time_out_at', $casts);
        $this->assertArrayHasKey('safety_checklist_sign_out', $casts);
        $this->assertArrayHasKey('safety_checklist_sign_out_at', $casts);
        $this->assertArrayHasKey('is_postponed', $casts);
        $this->assertArrayHasKey('postponed_at', $casts);
        $this->assertArrayHasKey('cancelled_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_surgeon(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->surgeon();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('surgeon_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_assistant_surgeon(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->assistantSurgeon();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('assistant_surgeon_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_anesthesiologist(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->anesthesiologist();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('anesthesiologist_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_nurse(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->nurse();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('nurse_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_circulating_nurse(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->circulatingNurse();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('circulating_nurse_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_implants(): void
    {
        $surgery = new Surgery();
        $relation = $surgery->implants();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('surgery_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(SurgeryImplant::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_implants(): void
    {
        $surgery = Surgery::factory()->create();
        SurgeryImplant::factory()->count(3)->create(['surgery_id' => $surgery->id]);

        $this->assertInstanceOf(Collection::class, $surgery->implants);
        $this->assertCount(3, $surgery->implants);
        $this->assertTrue($surgery->implants->every(fn ($implant) => $implant instanceof SurgeryImplant));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        Surgery::factory()->create(['scheduled_date' => today()]);
        Surgery::factory()->create(['scheduled_date' => today()->subDay()]);

        $results = Surgery::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_scheduled_scope(): void
    {
        Surgery::factory()->count(2)->create(['status' => 'scheduled']);
        Surgery::factory()->count(3)->create(['status' => 'completed']);

        $results = Surgery::scheduled()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->status === 'scheduled'));
    }

    #[Test]
    public function it_has_in_progress_scope(): void
    {
        Surgery::factory()->count(2)->create(['status' => 'in_progress']);
        Surgery::factory()->count(3)->create(['status' => 'scheduled']);

        $results = Surgery::inProgress()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->status === 'in_progress'));
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        Surgery::factory()->count(2)->create(['status' => 'completed']);
        Surgery::factory()->count(3)->create(['status' => 'scheduled']);

        $results = Surgery::completed()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->status === 'completed'));
    }

    #[Test]
    public function it_has_cancelled_scope(): void
    {
        Surgery::factory()->count(2)->create(['status' => 'cancelled']);
        Surgery::factory()->count(3)->create(['status' => 'scheduled']);

        $results = Surgery::cancelled()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->status === 'cancelled'));
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        Surgery::factory()->count(2)->create(['surgery_type' => 'elektif']);
        Surgery::factory()->count(3)->create(['surgery_type' => 'emergency']);

        $results = Surgery::byType('elektif')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->surgery_type === 'elektif'));
    }

    #[Test]
    public function it_has_by_surgeon_scope(): void
    {
        $surgeon = Employee::factory()->create();
        Surgery::factory()->count(3)->create();
        Surgery::factory()->count(2)->create(['surgeon_id' => $surgeon->id]);

        $results = Surgery::bySurgeon($surgeon->id)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->surgeon_id === $surgeon->id));
    }

    #[Test]
    public function it_has_in_room_scope(): void
    {
        Surgery::factory()->count(2)->create(['operating_room' => 'OK1']);
        Surgery::factory()->count(3)->create(['operating_room' => 'OK2']);

        $results = Surgery::inRoom('OK1')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($surgery) => $surgery->operating_room === 'OK1'));
    }

    #[Test]
    public function it_has_cito_scope(): void
    {
        Surgery::factory()->count(2)->create(['surgery_type' => 'cito']);
        Surgery::factory()->count(2)->create(['surgery_type' => 'emergency']);
        Surgery::factory()->count(3)->create(['surgery_type' => 'elektif']);

        $results = Surgery::cito()->get();

        $this->assertCount(4, $results);
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $date = '2026-02-08';
        Surgery::factory()->create(['scheduled_date' => $date]);
        Surgery::factory()->create(['scheduled_date' => '2026-02-09']);

        $results = Surgery::onDate($date)->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_calculates_duration_attribute(): void
    {
        $start = now()->subHours(2);
        $end = now();
        $surgery = new Surgery([
            'actual_start' => $start,
            'actual_end' => $end,
        ]);

        $this->assertEquals(120, $surgery->duration);
    }

    #[Test]
    public function it_returns_null_duration_when_not_started(): void
    {
        $surgery = new Surgery([
            'actual_start' => null,
            'actual_end' => now(),
        ]);

        $this->assertNull($surgery->duration);
    }

    #[Test]
    public function it_returns_null_duration_when_not_ended(): void
    {
        $surgery = new Surgery([
            'actual_start' => now()->subHours(2),
            'actual_end' => null,
        ]);

        $this->assertNull($surgery->duration);
    }

    #[Test]
    public function it_calculates_estimated_duration_attribute(): void
    {
        $start = now();
        $end = now()->addHours(3);
        $surgery = new Surgery([
            'start_time' => $start,
            'estimated_end_time' => $end,
        ]);

        $this->assertEquals(180, $surgery->estimated_duration);
    }

    #[Test]
    public function it_returns_correct_status_color_attribute(): void
    {
        $scheduled = new Surgery(['status' => 'scheduled']);
        $preparation = new Surgery(['status' => 'preparation']);
        $inProgress = new Surgery(['status' => 'in_progress']);
        $completed = new Surgery(['status' => 'completed']);
        $cancelled = new Surgery(['status' => 'cancelled']);
        $default = new Surgery(['status' => 'unknown']);

        $this->assertEquals('info', $scheduled->status_color);
        $this->assertEquals('warning', $preparation->status_color);
        $this->assertEquals('primary', $inProgress->status_color);
        $this->assertEquals('success', $completed->status_color);
        $this->assertEquals('danger', $cancelled->status_color);
        $this->assertEquals('gray', $default->status_color);
    }

    #[Test]
    public function it_returns_correct_status_label_attribute(): void
    {
        $scheduled = new Surgery(['status' => 'scheduled']);
        $preparation = new Surgery(['status' => 'preparation']);
        $inProgress = new Surgery(['status' => 'in_progress']);
        $completed = new Surgery(['status' => 'completed']);
        $cancelled = new Surgery(['status' => 'cancelled']);

        $this->assertEquals('Terjadwal', $scheduled->status_label);
        $this->assertEquals('Persiapan', $preparation->status_label);
        $this->assertEquals('Sedang Berlangsung', $inProgress->status_label);
        $this->assertEquals('Selesai', $completed->status_label);
        $this->assertEquals('Dibatalkan', $cancelled->status_label);
    }

    #[Test]
    public function it_returns_correct_surgery_type_color_attribute(): void
    {
        $elektif = new Surgery(['surgery_type' => 'elektif']);
        $urgent = new Surgery(['surgery_type' => 'urgent']);
        $cito = new Surgery(['surgery_type' => 'cito']);
        $emergency = new Surgery(['surgery_type' => 'emergency']);
        $default = new Surgery(['surgery_type' => 'unknown']);

        $this->assertEquals('info', $elektif->surgery_type_color);
        $this->assertEquals('warning', $urgent->surgery_type_color);
        $this->assertEquals('danger', $cito->surgery_type_color);
        $this->assertEquals('danger', $emergency->surgery_type_color);
        $this->assertEquals('gray', $default->surgery_type_color);
    }

    #[Test]
    public function it_returns_correct_surgery_type_label_attribute(): void
    {
        $elektif = new Surgery(['surgery_type' => 'elektif']);
        $urgent = new Surgery(['surgery_type' => 'urgent']);
        $cito = new Surgery(['surgery_type' => 'cito']);
        $emergency = new Surgery(['surgery_type' => 'emergency']);

        $this->assertEquals('Elektif', $elektif->surgery_type_label);
        $this->assertEquals('Urgent', $urgent->surgery_type_label);
        $this->assertEquals('CITO', $cito->surgery_type_label);
        $this->assertEquals('Emergency', $emergency->surgery_type_label);
    }

    #[Test]
    public function it_returns_is_today_attribute(): void
    {
        $todaySurgery = new Surgery(['scheduled_date' => today()]);
        $pastSurgery = new Surgery(['scheduled_date' => today()->subDay()]);
        $futureSurgery = new Surgery(['scheduled_date' => today()->addDay()]);

        $this->assertTrue($todaySurgery->is_today);
        $this->assertFalse($pastSurgery->is_today);
        $this->assertFalse($futureSurgery->is_today);
    }

    #[Test]
    public function it_returns_is_overdue_attribute(): void
    {
        $overdueScheduled = new Surgery([
            'scheduled_date' => today()->subDay(),
            'status' => 'scheduled',
        ]);
        $completed = new Surgery([
            'scheduled_date' => today()->subDay(),
            'status' => 'completed',
        ]);
        $future = new Surgery([
            'scheduled_date' => today()->addDay(),
            'status' => 'scheduled',
        ]);

        $this->assertTrue($overdueScheduled->is_overdue);
        $this->assertFalse($completed->is_overdue);
        $this->assertFalse($future->is_overdue);
    }

    #[Test]
    public function it_calculates_safety_checklist_progress_attribute(): void
    {
        $noneCompleted = new Surgery([
            'safety_checklist_sign_in' => false,
            'safety_checklist_time_out' => false,
            'safety_checklist_sign_out' => false,
        ]);
        $oneCompleted = new Surgery([
            'safety_checklist_sign_in' => true,
            'safety_checklist_time_out' => false,
            'safety_checklist_sign_out' => false,
        ]);
        $allCompleted = new Surgery([
            'safety_checklist_sign_in' => true,
            'safety_checklist_time_out' => true,
            'safety_checklist_sign_out' => true,
        ]);

        $this->assertEquals(0, $noneCompleted->safety_checklist_progress);
        $this->assertEquals(33, $oneCompleted->safety_checklist_progress);
        $this->assertEquals(100, $allCompleted->safety_checklist_progress);
    }

    #[Test]
    public function it_returns_is_safety_checklist_complete_attribute(): void
    {
        $incomplete = new Surgery([
            'safety_checklist_sign_in' => true,
            'safety_checklist_time_out' => true,
            'safety_checklist_sign_out' => false,
        ]);
        $complete = new Surgery([
            'safety_checklist_sign_in' => true,
            'safety_checklist_time_out' => true,
            'safety_checklist_sign_out' => true,
        ]);

        $this->assertFalse($incomplete->is_safety_checklist_complete);
        $this->assertTrue($complete->is_safety_checklist_complete);
    }

    #[Test]
    public function it_returns_operating_rooms_list(): void
    {
        $rooms = Surgery::getOperatingRooms();

        $this->assertIsArray($rooms);
        $this->assertArrayHasKey('OK1', $rooms);
        $this->assertArrayHasKey('OK_CITO', $rooms);
        $this->assertEquals('OK 1', $rooms['OK1']);
        $this->assertEquals('OK CITO/Emergency', $rooms['OK_CITO']);
    }

    #[Test]
    public function it_returns_anesthesia_types_list(): void
    {
        $types = Surgery::getAnesthesiaTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('umum', $types);
        $this->assertArrayHasKey('spinal', $types);
        $this->assertEquals('Anestesi Umum (General)', $types['umum']);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $surgery = Surgery::factory()->create();

        $this->assertDatabaseHas('surgeries', ['id' => $surgery->id]);

        $surgery->delete();

        $this->assertSoftDeleted('surgeries', ['id' => $surgery->id]);
    }

    #[Test]
    public function it_generates_unique_surgery_numbers(): void
    {
        $surgery1 = Surgery::factory()->create();
        $surgery2 = Surgery::factory()->create();

        $this->assertNotEquals($surgery1->surgery_number, $surgery2->surgery_number);
        $this->assertStringStartsWith('SRG', $surgery1->surgery_number);
        $this->assertStringStartsWith('SRG', $surgery2->surgery_number);
    }

    #[Test]
    public function it_can_create_emergency_surgery(): void
    {
        $surgery = Surgery::factory()->emergency()->create();

        $this->assertEquals('emergency', $surgery->surgery_type);
    }

    #[Test]
    public function it_can_create_scheduled_surgery(): void
    {
        $surgery = Surgery::factory()->scheduled()->create();

        $this->assertEquals('scheduled', $surgery->status);
        $this->assertEquals('elektif', $surgery->surgery_type);
    }
}
