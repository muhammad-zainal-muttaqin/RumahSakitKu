<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Patient;

use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitQueueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $queue = new VisitQueue();

        $expectedFillable = [
            'visit_id',
            'patient_id',
            'polyclinic_id',
            'queue_number',
            'display_number',
            'status',
            'called_at',
            'completed_at',
            'counter_number',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $queue->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $queue = new VisitQueue();
        $casts = $queue->getCasts();

        $this->assertArrayHasKey('queue_number', $casts);
        $this->assertArrayHasKey('called_at', $casts);
        $this->assertArrayHasKey('completed_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $queue = new VisitQueue();
        $relation = $queue->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $queue = new VisitQueue();
        $relation = $queue->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_polyclinic(): void
    {
        $queue = new VisitQueue();
        $relation = $queue->polyclinic();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('polyclinic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Polyclinic::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_with_status_scope(): void
    {
        $waitingQueue = VisitQueue::factory()->create(['status' => 'waiting']);
        $calledQueue = VisitQueue::factory()->create(['status' => 'called']);

        $results = VisitQueue::withStatus('waiting')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($waitingQueue));
        $this->assertFalse($results->contains($calledQueue));
    }

    #[Test]
    public function it_has_waiting_scope(): void
    {
        VisitQueue::factory()->create(['status' => 'waiting']);
        VisitQueue::factory()->create(['status' => 'called']);

        $results = VisitQueue::waiting()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('waiting', $results->first()->status);
    }

    #[Test]
    public function it_has_called_scope(): void
    {
        VisitQueue::factory()->create(['status' => 'waiting']);
        VisitQueue::factory()->create(['status' => 'called']);

        $results = VisitQueue::called()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('called', $results->first()->status);
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        VisitQueue::factory()->create(['status' => 'waiting']);
        VisitQueue::factory()->create(['status' => 'completed']);

        $results = VisitQueue::completed()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('completed', $results->first()->status);
    }

    #[Test]
    public function it_has_in_polyclinic_scope(): void
    {
        $polyclinic1 = Polyclinic::factory()->create();
        $polyclinic2 = Polyclinic::factory()->create();

        $queue1 = VisitQueue::factory()->create(['polyclinic_id' => $polyclinic1->id]);
        VisitQueue::factory()->create(['polyclinic_id' => $polyclinic2->id]);

        $results = VisitQueue::inPolyclinic($polyclinic1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($queue1));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        VisitQueue::factory()->create(['created_at' => now()]);
        VisitQueue::factory()->create(['created_at' => now()->subDay()]);

        $results = VisitQueue::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_ordered_scope(): void
    {
        $queue1 = VisitQueue::factory()->create(['queue_number' => 5]);
        $queue2 = VisitQueue::factory()->create(['queue_number' => 1]);
        $queue3 = VisitQueue::factory()->create(['queue_number' => 10]);

        $results = VisitQueue::ordered()->get();

        $this->assertEquals($queue2->id, $results->first()->id);
        $this->assertEquals($queue1->id, $results->get(1)->id);
        $this->assertEquals($queue3->id, $results->last()->id);
    }

    #[Test]
    public function it_can_mark_as_called(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $queue->markAsCalled('Counter 1');

        $this->assertEquals('called', $queue->fresh()->status);
        $this->assertNotNull($queue->fresh()->called_at);
        $this->assertEquals('Counter 1', $queue->fresh()->counter_number);
    }

    #[Test]
    public function it_can_mark_as_completed(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $queue->markAsCompleted();

        $this->assertEquals('completed', $queue->fresh()->status);
        $this->assertNotNull($queue->fresh()->completed_at);
    }

    #[Test]
    public function it_can_mark_as_skipped(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $queue->markAsSkipped();

        $this->assertEquals('skipped', $queue->fresh()->status);
    }

    #[Test]
    public function it_can_mark_as_in_progress(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $queue->markAsInProgress();

        $this->assertEquals('in_progress', $queue->fresh()->status);
    }

    #[Test]
    public function it_can_mark_as_cancelled(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $queue->markAsCancelled();

        $this->assertEquals('cancelled', $queue->fresh()->status);
    }

    #[Test]
    public function it_calculates_waiting_time_when_called(): void
    {
        $queue = VisitQueue::factory()->create([
            'status' => 'called',
            'created_at' => now()->subMinutes(30),
            'called_at' => now(),
        ]);

        $this->assertEquals(30, $queue->waiting_time);
    }

    #[Test]
    public function it_calculates_waiting_time_from_created_at_when_not_called(): void
    {
        $queue = VisitQueue::factory()->create([
            'status' => 'waiting',
            'created_at' => now()->subMinutes(15),
            'called_at' => null,
        ]);

        $this->assertGreaterThanOrEqual(14, $queue->waiting_time);
        $this->assertLessThanOrEqual(16, $queue->waiting_time);
    }

    #[Test]
    public function it_calculates_service_time_when_completed(): void
    {
        $queue = VisitQueue::factory()->create([
            'status' => 'completed',
            'called_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);

        $this->assertEquals(20, $queue->service_time);
    }

    #[Test]
    public function it_returns_null_service_time_when_not_completed(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $this->assertNull($queue->service_time);
    }

    #[Test]
    public function it_returns_status_color_for_waiting(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $this->assertEquals('gray', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_color_for_called(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $this->assertEquals('yellow', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_color_for_in_progress(): void
    {
        $queue = VisitQueue::factory()->inProgress()->create();

        $this->assertEquals('blue', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_color_for_completed(): void
    {
        $queue = VisitQueue::factory()->completed()->create();

        $this->assertEquals('green', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_color_for_cancelled(): void
    {
        $queue = VisitQueue::factory()->cancelled()->create();

        $this->assertEquals('red', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_color_for_skipped(): void
    {
        $queue = VisitQueue::factory()->skipped()->create();

        $this->assertEquals('orange', $queue->status_color);
    }

    #[Test]
    public function it_returns_status_label_in_indonesian(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $this->assertEquals('Menunggu', $queue->status_label);
    }

    #[Test]
    public function it_can_be_called_when_waiting(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $this->assertTrue($queue->can_be_called);
    }

    #[Test]
    public function it_can_be_called_when_skipped(): void
    {
        $queue = VisitQueue::factory()->skipped()->create();

        $this->assertTrue($queue->can_be_called);
    }

    #[Test]
    public function it_cannot_be_called_when_completed(): void
    {
        $queue = VisitQueue::factory()->completed()->create();

        $this->assertFalse($queue->can_be_called);
    }

    #[Test]
    public function it_can_be_completed_when_called(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $this->assertTrue($queue->can_be_completed);
    }

    #[Test]
    public function it_can_be_completed_when_in_progress(): void
    {
        $queue = VisitQueue::factory()->inProgress()->create();

        $this->assertTrue($queue->can_be_completed);
    }

    #[Test]
    public function it_cannot_be_completed_when_waiting(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $this->assertFalse($queue->can_be_completed);
    }

    #[Test]
    public function it_can_be_skipped_when_waiting(): void
    {
        $queue = VisitQueue::factory()->waiting()->create();

        $this->assertTrue($queue->can_be_skipped);
    }

    #[Test]
    public function it_can_be_skipped_when_called(): void
    {
        $queue = VisitQueue::factory()->called()->create();

        $this->assertTrue($queue->can_be_skipped);
    }

    #[Test]
    public function it_cannot_be_skipped_when_completed(): void
    {
        $queue = VisitQueue::factory()->completed()->create();

        $this->assertFalse($queue->can_be_skipped);
    }

    #[Test]
    public function it_generates_display_number_with_leading_zeros(): void
    {
        $queue = VisitQueue::factory()->create(['queue_number' => 5]);

        $this->assertEquals('005', $queue->display_number);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $queue = VisitQueue::factory()->create();

        $this->assertDatabaseHas('visit_queues', ['id' => $queue->id]);

        $queue->delete();

        $this->assertSoftDeleted('visit_queues', ['id' => $queue->id]);
    }
}
