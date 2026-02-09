<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $bed = new Bed();

        $expectedFillable = [
            'room_id',
            'bed_number',
            'bed_name',
            'bed_type',
            'status',
            'current_visit_id',
            'occupied_at',
            'vacated_at',
            'notes',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $bed->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $bed = new Bed();
        $casts = $bed->getCasts();

        $this->assertArrayHasKey('occupied_at', $casts);
        $this->assertArrayHasKey('vacated_at', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_room(): void
    {
        $bed = new Bed();
        $relation = $bed->room();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('room_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Room::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_current_visit(): void
    {
        $bed = new Bed();
        $relation = $bed->currentVisit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('current_visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_available_scope(): void
    {
        Bed::factory()->count(2)->available()->create();
        Bed::factory()->count(3)->occupied()->create();

        $results = Bed::available()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->status === 'kosong' && $bed->current_visit_id === null));
    }

    #[Test]
    public function it_has_occupied_scope(): void
    {
        Bed::factory()->count(2)->available()->create();
        Bed::factory()->count(3)->occupied()->create();

        $results = Bed::occupied()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->status === 'terisi' && $bed->current_visit_id !== null));
    }

    #[Test]
    public function it_has_maintenance_scope(): void
    {
        Bed::factory()->count(2)->available()->create();
        Bed::factory()->count(3)->maintenance()->create();

        $results = Bed::maintenance()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->status === 'maintenance'));
    }

    #[Test]
    public function it_has_reserved_scope(): void
    {
        Bed::factory()->count(2)->available()->create();
        Bed::factory()->count(3)->reserved()->create();

        $results = Bed::reserved()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->status === 'reserved'));
    }

    #[Test]
    public function it_has_cleaning_scope(): void
    {
        Bed::factory()->count(2)->available()->create();
        Bed::factory()->count(3)->cleaning()->create();

        $results = Bed::cleaning()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->status === 'cleaning'));
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        $electric = Bed::factory()->electric()->create();
        Bed::factory()->create(['bed_type' => 'standard']);

        $results = Bed::byType('electric')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($electric));
    }

    #[Test]
    public function it_has_in_room_scope(): void
    {
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();

        $bed1 = Bed::factory()->create(['room_id' => $room1->id]);
        Bed::factory()->create(['room_id' => $room2->id]);

        $results = Bed::inRoom($room1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($bed1));
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Bed::factory()->count(3)->create(['is_active' => true]);
        Bed::factory()->count(2)->inactive()->create();

        $results = Bed::active()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($bed) => $bed->is_active === true));
    }

    #[Test]
    public function it_can_occupy_bed(): void
    {
        $bed = Bed::factory()->available()->create();
        $visit = Visit::factory()->create();

        $result = $bed->occupy($visit->id);

        $this->assertTrue($result);
        $this->assertEquals('terisi', $bed->fresh()->status);
        $this->assertEquals($visit->id, $bed->fresh()->current_visit_id);
        $this->assertNotNull($bed->fresh()->occupied_at);
    }

    #[Test]
    public function it_cannot_occupy_already_occupied_bed(): void
    {
        $bed = Bed::factory()->occupied()->create();
        $visit = Visit::factory()->create();

        $result = $bed->occupy($visit->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_vacate_bed(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $result = $bed->vacate();

        $this->assertTrue($result);
        $this->assertEquals('kosong', $bed->fresh()->status);
        $this->assertNull($bed->fresh()->current_visit_id);
        $this->assertNull($bed->fresh()->occupied_at);
        $this->assertNotNull($bed->fresh()->vacated_at);
    }

    #[Test]
    public function it_can_set_maintenance_status(): void
    {
        $bed = Bed::factory()->available()->create();

        $result = $bed->setMaintenance('Renovation needed');

        $this->assertTrue($result);
        $this->assertEquals('maintenance', $bed->fresh()->status);
        $this->assertEquals('Renovation needed', $bed->fresh()->notes);
    }

    #[Test]
    public function it_cannot_set_maintenance_on_occupied_bed(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $result = $bed->setMaintenance('Renovation needed');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_set_cleaning_status(): void
    {
        $bed = Bed::factory()->available()->create();

        $result = $bed->setCleaning();

        $this->assertTrue($result);
        $this->assertEquals('cleaning', $bed->fresh()->status);
    }

    #[Test]
    public function it_cannot_set_cleaning_on_occupied_bed(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $result = $bed->setCleaning();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_set_reserved_status(): void
    {
        $bed = Bed::factory()->available()->create();

        $result = $bed->setReserved();

        $this->assertTrue($result);
        $this->assertEquals('reserved', $bed->fresh()->status);
    }

    #[Test]
    public function it_cannot_set_reserved_on_occupied_bed(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $result = $bed->setReserved();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_is_available_attribute_true_when_available(): void
    {
        $bed = Bed::factory()->available()->create();

        $this->assertTrue($bed->is_available);
    }

    #[Test]
    public function it_returns_is_available_attribute_false_when_occupied(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $this->assertFalse($bed->is_available);
    }

    #[Test]
    public function it_returns_is_occupied_attribute_true_when_occupied(): void
    {
        $bed = Bed::factory()->occupied()->create();

        $this->assertTrue($bed->is_occupied);
    }

    #[Test]
    public function it_returns_is_occupied_attribute_false_when_available(): void
    {
        $bed = Bed::factory()->available()->create();

        $this->assertFalse($bed->is_occupied);
    }

    #[Test]
    public function it_calculates_occupancy_duration_attribute(): void
    {
        $bed = Bed::factory()->create([
            'status' => 'terisi',
            'occupied_at' => now()->subHours(5),
        ]);

        $this->assertGreaterThanOrEqual(4, $bed->occupancy_duration);
        $this->assertLessThanOrEqual(6, $bed->occupancy_duration);
    }

    #[Test]
    public function it_returns_null_occupancy_duration_when_not_occupied(): void
    {
        $bed = Bed::factory()->available()->create();

        $this->assertNull($bed->occupancy_duration);
    }

    #[Test]
    public function it_returns_full_identifier_attribute(): void
    {
        $room = Room::factory()->create(['name' => 'Room 101']);
        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'bed_number' => 'A1',
        ]);

        $this->assertEquals('Room 101 - Bed A1', $bed->full_identifier);
    }

    #[Test]
    public function it_returns_status_color_attribute(): void
    {
        $available = Bed::factory()->available()->create();
        $occupied = Bed::factory()->occupied()->create();
        $reserved = Bed::factory()->reserved()->create();
        $maintenance = Bed::factory()->maintenance()->create();
        $cleaning = Bed::factory()->cleaning()->create();

        $this->assertEquals('success', $available->status_color);
        $this->assertEquals('danger', $occupied->status_color);
        $this->assertEquals('warning', $reserved->status_color);
        $this->assertEquals('gray', $maintenance->status_color);
        $this->assertEquals('info', $cleaning->status_color);
    }

    #[Test]
    public function it_returns_status_label_attribute(): void
    {
        $available = Bed::factory()->available()->create();
        $occupied = Bed::factory()->occupied()->create();
        $reserved = Bed::factory()->reserved()->create();
        $maintenance = Bed::factory()->maintenance()->create();
        $cleaning = Bed::factory()->cleaning()->create();

        $this->assertEquals('Kosong', $available->status_label);
        $this->assertEquals('Terisi', $occupied->status_label);
        $this->assertEquals('Dipesan', $reserved->status_label);
        $this->assertEquals('Maintenance', $maintenance->status_label);
        $this->assertEquals('Dibersihkan', $cleaning->status_label);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $bed = Bed::factory()->create();

        $this->assertDatabaseHas('beds', ['id' => $bed->id]);

        $bed->delete();

        $this->assertSoftDeleted('beds', ['id' => $bed->id]);
    }

    #[Test]
    public function it_can_create_electric_bed(): void
    {
        $bed = Bed::factory()->electric()->create();

        $this->assertEquals('electric', $bed->bed_type);
    }

    #[Test]
    public function it_can_create_icu_bed(): void
    {
        $bed = Bed::factory()->icu()->create();

        $this->assertEquals('icu', $bed->bed_type);
    }
}
