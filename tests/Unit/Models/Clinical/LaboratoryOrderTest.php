<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\LaboratoryResult;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LaboratoryOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $order = new LaboratoryOrder();

        $expectedFillable = [
            'order_number',
            'visit_id',
            'patient_id',
            'doctor_id',
            'medical_record_id',
            'order_date',
            'priority',
            'status',
            'diagnosis_notes',
            'clinical_notes',
            'total_price',
            'is_cito',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $order->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $order = new LaboratoryOrder();
        $casts = $order->getCasts();

        $this->assertArrayHasKey('order_date', $casts);
        $this->assertArrayHasKey('total_price', $casts);
        $this->assertArrayHasKey('is_cito', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $order = new LaboratoryOrder();
        $relation = $order->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $order = new LaboratoryOrder();
        $relation = $order->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_doctor(): void
    {
        $order = new LaboratoryOrder();
        $relation = $order->doctor();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('doctor_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_medical_record(): void
    {
        $order = new LaboratoryOrder();
        $relation = $order->medicalRecord();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_results(): void
    {
        $order = new LaboratoryOrder();
        $relation = $order->results();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('laboratory_order_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(LaboratoryResult::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_results(): void
    {
        $order = LaboratoryOrder::factory()->create();
        LaboratoryResult::factory()->count(3)->create(['laboratory_order_id' => $order->id]);

        $this->assertInstanceOf(Collection::class, $order->results);
        $this->assertCount(3, $order->results);
        $this->assertTrue($order->results->every(fn ($result) => $result instanceof LaboratoryResult));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        LaboratoryOrder::factory()->count(2)->create(['status' => 'pending']);
        LaboratoryOrder::factory()->count(3)->create(['status' => 'completed']);

        $results = LaboratoryOrder::pending()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'pending'));
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        LaboratoryOrder::factory()->count(2)->create(['status' => 'completed']);
        LaboratoryOrder::factory()->count(3)->create(['status' => 'pending']);

        $results = LaboratoryOrder::completed()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'completed'));
    }

    #[Test]
    public function it_has_by_priority_scope(): void
    {
        LaboratoryOrder::factory()->count(2)->create(['priority' => 'urgent']);
        LaboratoryOrder::factory()->count(3)->create(['priority' => 'normal']);

        $results = LaboratoryOrder::byPriority('urgent')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->priority === 'urgent'));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        LaboratoryOrder::factory()->create(['order_date' => now()]);
        LaboratoryOrder::factory()->create(['order_date' => now()->subDay()]);

        $results = LaboratoryOrder::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_cito_scope(): void
    {
        LaboratoryOrder::factory()->count(2)->create(['is_cito' => true]);
        LaboratoryOrder::factory()->count(3)->create(['is_cito' => false]);

        $results = LaboratoryOrder::cito()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->is_cito === true));
    }

    #[Test]
    public function it_has_validated_scope(): void
    {
        LaboratoryOrder::factory()->count(2)->create(['status' => 'validated']);
        LaboratoryOrder::factory()->count(3)->create(['status' => 'pending']);

        $results = LaboratoryOrder::validated()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($order) => $order->status === 'validated'));
    }

    #[Test]
    public function it_returns_correct_status_color_attribute(): void
    {
        $pendingOrder = new LaboratoryOrder(['status' => 'pending']);
        $inProgressOrder = new LaboratoryOrder(['status' => 'in_progress']);
        $completedOrder = new LaboratoryOrder(['status' => 'completed']);
        $validatedOrder = new LaboratoryOrder(['status' => 'validated']);
        $cancelledOrder = new LaboratoryOrder(['status' => 'cancelled']);
        $defaultOrder = new LaboratoryOrder(['status' => 'unknown']);

        $this->assertEquals('warning', $pendingOrder->status_color);
        $this->assertEquals('info', $inProgressOrder->status_color);
        $this->assertEquals('success', $completedOrder->status_color);
        $this->assertEquals('primary', $validatedOrder->status_color);
        $this->assertEquals('danger', $cancelledOrder->status_color);
        $this->assertEquals('gray', $defaultOrder->status_color);
    }

    #[Test]
    public function it_returns_correct_priority_color_attribute(): void
    {
        $normalOrder = new LaboratoryOrder(['priority' => 'normal']);
        $urgentOrder = new LaboratoryOrder(['priority' => 'urgent']);
        $citoOrder = new LaboratoryOrder(['priority' => 'cito']);
        $defaultOrder = new LaboratoryOrder(['priority' => 'unknown']);

        $this->assertEquals('gray', $normalOrder->priority_color);
        $this->assertEquals('warning', $urgentOrder->priority_color);
        $this->assertEquals('danger', $citoOrder->priority_color);
        $this->assertEquals('gray', $defaultOrder->priority_color);
    }

    #[Test]
    public function it_returns_correct_status_label_attribute(): void
    {
        $pendingOrder = new LaboratoryOrder(['status' => 'pending']);
        $inProgressOrder = new LaboratoryOrder(['status' => 'in_progress']);
        $completedOrder = new LaboratoryOrder(['status' => 'completed']);

        $this->assertEquals('Pending', $pendingOrder->status_label);
        $this->assertEquals('Diproses', $inProgressOrder->status_label);
        $this->assertEquals('Selesai', $completedOrder->status_label);
    }

    #[Test]
    public function it_returns_correct_priority_label_attribute(): void
    {
        $normalOrder = new LaboratoryOrder(['priority' => 'normal']);
        $urgentOrder = new LaboratoryOrder(['priority' => 'urgent']);
        $citoOrder = new LaboratoryOrder(['priority' => 'cito']);

        $this->assertEquals('Normal', $normalOrder->priority_label);
        $this->assertEquals('Urgent', $urgentOrder->priority_label);
        $this->assertEquals('CITO', $citoOrder->priority_label);
    }

    #[Test]
    public function it_calculates_total_tests_attribute(): void
    {
        $order = LaboratoryOrder::factory()->create();
        LaboratoryResult::factory()->count(5)->create(['laboratory_order_id' => $order->id]);

        $this->assertEquals(5, $order->total_tests);
    }

    #[Test]
    public function it_calculates_completed_results_count_attribute(): void
    {
        $order = LaboratoryOrder::factory()->create();
        LaboratoryResult::factory()->count(3)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => '10.5',
        ]);
        LaboratoryResult::factory()->count(2)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => null,
        ]);

        $this->assertEquals(3, $order->completed_results_count);
    }

    #[Test]
    public function it_determines_if_all_results_entered(): void
    {
        $order = LaboratoryOrder::factory()->create();
        LaboratoryResult::factory()->count(3)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => '10.5',
        ]);

        $this->assertTrue($order->is_all_results_entered);
    }

    #[Test]
    public function it_returns_false_for_all_results_entered_when_incomplete(): void
    {
        $order = LaboratoryOrder::factory()->create();
        LaboratoryResult::factory()->count(2)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => '10.5',
        ]);
        LaboratoryResult::factory()->create([
            'laboratory_order_id' => $order->id,
            'result_value' => null,
        ]);

        $this->assertFalse($order->is_all_results_entered);
    }

    #[Test]
    public function it_can_be_processed_when_pending(): void
    {
        $order = new LaboratoryOrder(['status' => 'pending']);

        $this->assertTrue($order->canBeProcessed());
    }

    #[Test]
    public function it_cannot_be_processed_when_not_pending(): void
    {
        $order = new LaboratoryOrder(['status' => 'completed']);

        $this->assertFalse($order->canBeProcessed());
    }

    #[Test]
    public function it_can_enter_results_when_pending_or_in_progress(): void
    {
        $pendingOrder = new LaboratoryOrder(['status' => 'pending']);
        $inProgressOrder = new LaboratoryOrder(['status' => 'in_progress']);
        $completedOrder = new LaboratoryOrder(['status' => 'completed']);

        $this->assertTrue($pendingOrder->canEnterResults());
        $this->assertTrue($inProgressOrder->canEnterResults());
        $this->assertFalse($completedOrder->canEnterResults());
    }

    #[Test]
    public function it_can_be_validated_when_completed_and_all_results_entered(): void
    {
        $order = LaboratoryOrder::factory()->create(['status' => 'completed']);
        LaboratoryResult::factory()->count(3)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => '10.5',
        ]);
        $order->refresh();

        $this->assertTrue($order->canBeValidated());
    }

    #[Test]
    public function it_cannot_be_validated_when_not_completed(): void
    {
        $order = LaboratoryOrder::factory()->create(['status' => 'pending']);
        LaboratoryResult::factory()->count(3)->create([
            'laboratory_order_id' => $order->id,
            'result_value' => '10.5',
        ]);
        $order->refresh();

        $this->assertFalse($order->canBeValidated());
    }

    #[Test]
    public function it_can_be_cancelled_when_not_validated_or_cancelled(): void
    {
        $pendingOrder = new LaboratoryOrder(['status' => 'pending']);
        $inProgressOrder = new LaboratoryOrder(['status' => 'in_progress']);
        $validatedOrder = new LaboratoryOrder(['status' => 'validated']);
        $cancelledOrder = new LaboratoryOrder(['status' => 'cancelled']);

        $this->assertTrue($pendingOrder->canBeCancelled());
        $this->assertTrue($inProgressOrder->canBeCancelled());
        $this->assertFalse($validatedOrder->canBeCancelled());
        $this->assertFalse($cancelledOrder->canBeCancelled());
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $order = LaboratoryOrder::factory()->create();

        $this->assertDatabaseHas('laboratory_orders', ['id' => $order->id]);

        $order->delete();

        $this->assertSoftDeleted('laboratory_orders', ['id' => $order->id]);
    }

    #[Test]
    public function it_generates_unique_order_numbers(): void
    {
        $order1 = LaboratoryOrder::factory()->create();
        $order2 = LaboratoryOrder::factory()->create();

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('LAB', $order1->order_number);
        $this->assertStringStartsWith('LAB', $order2->order_number);
    }

    #[Test]
    public function it_can_create_cito_order(): void
    {
        $order = LaboratoryOrder::factory()->cito()->create();

        $this->assertTrue($order->is_cito);
        $this->assertEquals('cito', $order->priority);
    }

    #[Test]
    public function it_can_create_with_pending_status(): void
    {
        $order = LaboratoryOrder::factory()->pending()->create();

        $this->assertEquals('pending', $order->status);
    }
}
