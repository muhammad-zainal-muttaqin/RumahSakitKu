<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\RadiologyOrder;
use App\Models\Clinical\RadiologyResult;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadiologyOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $order = new RadiologyOrder();

        $expectedFillable = [
            'order_number',
            'visit_id',
            'patient_id',
            'doctor_id',
            'medical_record_id',
            'examination_type',
            'body_area',
            'position',
            'contrast',
            'contrast_type',
            'clinical_indication',
            'scheduled_date',
            'room',
            'priority',
            'status',
            'notes',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $order->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $order = new RadiologyOrder();
        $casts = $order->getCasts();

        $this->assertArrayHasKey('scheduled_date', $casts);
        $this->assertArrayHasKey('contrast', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $order = new RadiologyOrder();
        $relation = $order->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $order = new RadiologyOrder();
        $relation = $order->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_doctor(): void
    {
        $order = new RadiologyOrder();
        $relation = $order->doctor();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('doctor_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_medical_record(): void
    {
        $order = new RadiologyOrder();
        $relation = $order->medicalRecord();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_one_result(): void
    {
        $order = new RadiologyOrder();
        $relation = $order->result();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals('radiology_order_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(RadiologyResult::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_result(): void
    {
        $order = RadiologyOrder::factory()->create();
        RadiologyResult::factory()->create(['radiology_order_id' => $order->id]);

        $this->assertInstanceOf(RadiologyResult::class, $order->result);
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['status' => 'pending']);
        RadiologyOrder::factory()->count(3)->create(['status' => 'completed']);

        $results = RadiologyOrder::pending()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'pending'));
    }

    #[Test]
    public function it_has_scheduled_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['status' => 'scheduled']);
        RadiologyOrder::factory()->count(3)->create(['status' => 'pending']);

        $results = RadiologyOrder::scheduled()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'scheduled'));
    }

    #[Test]
    public function it_has_in_progress_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['status' => 'in_progress']);
        RadiologyOrder::factory()->count(3)->create(['status' => 'pending']);

        $results = RadiologyOrder::inProgress()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'in_progress'));
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['status' => 'completed']);
        RadiologyOrder::factory()->count(3)->create(['status' => 'pending']);

        $results = RadiologyOrder::completed()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'completed'));
    }

    #[Test]
    public function it_has_reported_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['status' => 'reported']);
        RadiologyOrder::factory()->count(3)->create(['status' => 'completed']);

        $results = RadiologyOrder::reported()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'reported'));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        RadiologyOrder::factory()->create(['scheduled_date' => now()]);
        RadiologyOrder::factory()->create(['scheduled_date' => now()->subDay()]);

        $results = RadiologyOrder::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_by_examination_type_scope(): void
    {
        RadiologyOrder::factory()->count(2)->create(['examination_type' => 'xray']);
        RadiologyOrder::factory()->count(3)->create(['examination_type' => 'ct_scan']);

        $results = RadiologyOrder::byExaminationType('xray')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->examination_type === 'xray'));
    }

    #[Test]
    public function it_returns_correct_status_color_attribute(): void
    {
        $pending = new RadiologyOrder(['status' => 'pending']);
        $scheduled = new RadiologyOrder(['status' => 'scheduled']);
        $inProgress = new RadiologyOrder(['status' => 'in_progress']);
        $completed = new RadiologyOrder(['status' => 'completed']);
        $reported = new RadiologyOrder(['status' => 'reported']);
        $cancelled = new RadiologyOrder(['status' => 'cancelled']);
        $default = new RadiologyOrder(['status' => 'unknown']);

        $this->assertEquals('warning', $pending->status_color);
        $this->assertEquals('info', $scheduled->status_color);
        $this->assertEquals('primary', $inProgress->status_color);
        $this->assertEquals('success', $completed->status_color);
        $this->assertEquals('primary', $reported->status_color);
        $this->assertEquals('danger', $cancelled->status_color);
        $this->assertEquals('gray', $default->status_color);
    }

    #[Test]
    public function it_returns_correct_status_label_attribute(): void
    {
        $pending = new RadiologyOrder(['status' => 'pending']);
        $scheduled = new RadiologyOrder(['status' => 'scheduled']);
        $inProgress = new RadiologyOrder(['status' => 'in_progress']);
        $completed = new RadiologyOrder(['status' => 'completed']);

        $this->assertEquals('Menunggu', $pending->status_label);
        $this->assertEquals('Terjadwal', $scheduled->status_label);
        $this->assertEquals('Sedang Dikerjakan', $inProgress->status_label);
        $this->assertEquals('Selesai', $completed->status_label);
    }

    #[Test]
    public function it_returns_correct_examination_type_label_attribute(): void
    {
        $xray = new RadiologyOrder(['examination_type' => 'xray']);
        $ctScan = new RadiologyOrder(['examination_type' => 'ct_scan']);
        $mri = new RadiologyOrder(['examination_type' => 'mri']);
        $usg = new RadiologyOrder(['examination_type' => 'usg']);
        $mammografi = new RadiologyOrder(['examination_type' => 'mammografi']);

        $this->assertEquals('Rontgen', $xray->examination_type_label);
        $this->assertEquals('CT Scan', $ctScan->examination_type_label);
        $this->assertEquals('MRI', $mri->examination_type_label);
        $this->assertEquals('USG', $usg->examination_type_label);
        $this->assertEquals('Mammografi', $mammografi->examination_type_label);
    }

    #[Test]
    public function it_returns_correct_priority_color_attribute(): void
    {
        $normal = new RadiologyOrder(['priority' => 'normal']);
        $urgent = new RadiologyOrder(['priority' => 'urgent']);
        $emergency = new RadiologyOrder(['priority' => 'emergency']);
        $default = new RadiologyOrder(['priority' => 'unknown']);

        $this->assertEquals('gray', $normal->priority_color);
        $this->assertEquals('warning', $urgent->priority_color);
        $this->assertEquals('danger', $emergency->priority_color);
        $this->assertEquals('gray', $default->priority_color);
    }

    #[Test]
    public function it_returns_correct_priority_label_attribute(): void
    {
        $normal = new RadiologyOrder(['priority' => 'normal']);
        $urgent = new RadiologyOrder(['priority' => 'urgent']);
        $emergency = new RadiologyOrder(['priority' => 'emergency']);

        $this->assertEquals('Normal', $normal->priority_label);
        $this->assertEquals('Urgent', $urgent->priority_label);
        $this->assertEquals('Emergency', $emergency->priority_label);
    }

    #[Test]
    public function it_returns_correct_contrast_label_attribute(): void
    {
        $noContrast = new RadiologyOrder(['contrast' => false, 'contrast_type' => null]);
        $withContrast = new RadiologyOrder(['contrast' => true, 'contrast_type' => 'Iodine']);
        $withContrastNoType = new RadiologyOrder(['contrast' => true, 'contrast_type' => null]);

        $this->assertEquals('Tanpa Kontras', $noContrast->contrast_label);
        $this->assertEquals('Dengan Iodine', $withContrast->contrast_label);
        $this->assertEquals('Dengan Kontras', $withContrastNoType->contrast_label);
    }

    #[Test]
    public function it_returns_examination_info_attribute(): void
    {
        $order = new RadiologyOrder([
            'examination_type' => 'xray',
            'body_area' => 'Chest',
            'position' => 'PA',
        ]);

        $this->assertStringContainsString('Rontgen', $order->examination_info);
        $this->assertStringContainsString('Chest', $order->examination_info);
        $this->assertStringContainsString('PA', $order->examination_info);
    }

    #[Test]
    public function it_can_be_scheduled_when_pending(): void
    {
        $order = new RadiologyOrder(['status' => 'pending']);

        $this->assertTrue($order->canBeScheduled());
    }

    #[Test]
    public function it_cannot_be_scheduled_when_not_pending(): void
    {
        $order = new RadiologyOrder(['status' => 'completed']);

        $this->assertFalse($order->canBeScheduled());
    }

    #[Test]
    public function it_can_be_started_when_pending_or_scheduled(): void
    {
        $pending = new RadiologyOrder(['status' => 'pending']);
        $scheduled = new RadiologyOrder(['status' => 'scheduled']);
        $completed = new RadiologyOrder(['status' => 'completed']);

        $this->assertTrue($pending->canBeStarted());
        $this->assertTrue($scheduled->canBeStarted());
        $this->assertFalse($completed->canBeStarted());
    }

    #[Test]
    public function it_can_enter_results_when_in_progress_or_completed(): void
    {
        $inProgress = new RadiologyOrder(['status' => 'in_progress']);
        $completed = new RadiologyOrder(['status' => 'completed']);
        $pending = new RadiologyOrder(['status' => 'pending']);

        $this->assertTrue($inProgress->canEnterResults());
        $this->assertTrue($completed->canEnterResults());
        $this->assertFalse($pending->canEnterResults());
    }

    #[Test]
    public function it_can_be_cancelled_when_not_reported_or_cancelled(): void
    {
        $pending = new RadiologyOrder(['status' => 'pending']);
        $completed = new RadiologyOrder(['status' => 'completed']);
        $reported = new RadiologyOrder(['status' => 'reported']);
        $cancelled = new RadiologyOrder(['status' => 'cancelled']);

        $this->assertTrue($pending->canBeCancelled());
        $this->assertTrue($completed->canBeCancelled());
        $this->assertFalse($reported->canBeCancelled());
        $this->assertFalse($cancelled->canBeCancelled());
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $order = RadiologyOrder::factory()->create();

        $this->assertDatabaseHas('radiology_orders', ['id' => $order->id]);

        $order->delete();

        $this->assertSoftDeleted('radiology_orders', ['id' => $order->id]);
    }

    #[Test]
    public function it_generates_unique_order_numbers(): void
    {
        $order1 = RadiologyOrder::factory()->create();
        $order2 = RadiologyOrder::factory()->create();

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('RAD', $order1->order_number);
        $this->assertStringStartsWith('RAD', $order2->order_number);
    }

    #[Test]
    public function it_can_create_emergency_order(): void
    {
        $order = RadiologyOrder::factory()->emergency()->create();

        $this->assertEquals('emergency', $order->priority);
    }

    #[Test]
    public function it_can_create_with_ct_scan_type(): void
    {
        $order = RadiologyOrder::factory()->ctScan()->create();

        $this->assertEquals('ct_scan', $order->examination_type);
    }
}
