<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $room = new Room();

        $expectedFillable = [
            'code',
            'name',
            'room_class',
            'floor',
            'building',
            'gender_preference',
            'total_beds',
            'available_beds',
            'base_price',
            'bpjs_price',
            'facilities',
            'description',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $room->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $room = new Room();
        $casts = $room->getCasts();

        $this->assertArrayHasKey('total_beds', $casts);
        $this->assertArrayHasKey('available_beds', $casts);
        $this->assertArrayHasKey('base_price', $casts);
        $this->assertArrayHasKey('bpjs_price', $casts);
        $this->assertArrayHasKey('facilities', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_has_beds_relationship(): void
    {
        $room = new Room();
        $relation = $room->beds();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('room_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Bed::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_beds(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->count(4)->create(['room_id' => $room->id]);

        $this->assertInstanceOf(Collection::class, $room->beds);
        $this->assertCount(4, $room->beds);
        $this->assertTrue($room->beds->every(fn ($bed) => $bed instanceof Bed));
    }

    #[Test]
    public function it_has_available_beds_relationship(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->available()->create(['room_id' => $room->id]);
        Bed::factory()->occupied()->create(['room_id' => $room->id]);

        $this->assertCount(1, $room->availableBeds);
        $this->assertTrue($room->availableBeds->first()->status === 'available');
    }

    #[Test]
    public function it_has_occupied_beds_relationship(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->available()->create(['room_id' => $room->id]);
        Bed::factory()->occupied()->create(['room_id' => $room->id]);

        $this->assertCount(1, $room->occupiedBeds);
        $this->assertTrue($room->occupiedBeds->first()->status === 'occupied');
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Room::factory()->count(3)->create(['is_active' => true]);
        Room::factory()->count(2)->create(['is_active' => false]);

        $results = Room::active()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($room) => $room->is_active === true));
    }

    #[Test]
    public function it_has_by_class_scope(): void
    {
        $vvip = Room::factory()->vvip()->create();
        Room::factory()->kelas3()->create();

        $results = Room::byClass('VVIP')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($vvip));
    }

    #[Test]
    public function it_has_on_floor_scope(): void
    {
        $room1 = Room::factory()->create(['floor' => 1]);
        Room::factory()->create(['floor' => 2]);

        $results = Room::onFloor(1)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($room1));
    }

    #[Test]
    public function it_has_with_available_beds_scope(): void
    {
        $roomWithBeds = Room::factory()->create();
        Bed::factory()->available()->create(['room_id' => $roomWithBeds->id]);

        $roomFull = Room::factory()->create();
        Bed::factory()->occupied()->create(['room_id' => $roomFull->id]);

        $results = Room::withAvailableBeds()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($roomWithBeds));
    }

    #[Test]
    public function it_calculates_total_beds_attribute(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->count(5)->create(['room_id' => $room->id]);

        $this->assertEquals(5, $room->total_beds);
    }

    #[Test]
    public function it_calculates_available_beds_count_attribute(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->count(3)->available()->create(['room_id' => $room->id]);
        Bed::factory()->count(2)->occupied()->create(['room_id' => $room->id]);

        $this->assertEquals(3, $room->available_beds_count);
    }

    #[Test]
    public function it_calculates_occupied_beds_count_attribute(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->count(3)->available()->create(['room_id' => $room->id]);
        Bed::factory()->count(2)->occupied()->create(['room_id' => $room->id]);

        $this->assertEquals(2, $room->occupied_beds_count);
    }

    #[Test]
    public function it_calculates_occupancy_rate_attribute(): void
    {
        $room = Room::factory()->create();
        Bed::factory()->count(3)->available()->create(['room_id' => $room->id]);
        Bed::factory()->count(2)->occupied()->create(['room_id' => $room->id]);

        // 2 occupied out of 5 total = 40%
        $this->assertEquals(40.0, $room->occupancy_rate);
    }

    #[Test]
    public function it_returns_zero_occupancy_rate_when_no_beds(): void
    {
        $room = Room::factory()->create();

        $this->assertEquals(0.0, $room->occupancy_rate);
    }

    #[Test]
    public function it_returns_is_full_attribute_true_when_no_available_beds(): void
    {
        $room = Room::factory()->create(['total_beds' => 5, 'available_beds' => 0]);

        $this->assertTrue($room->is_full);
    }

    #[Test]
    public function it_returns_is_full_attribute_false_when_has_available_beds(): void
    {
        $room = Room::factory()->create(['total_beds' => 5, 'available_beds' => 2]);

        $this->assertFalse($room->is_full);
    }

    #[Test]
    public function it_returns_total_daily_rate_attribute(): void
    {
        $room = Room::factory()->create(['base_price' => 500000]);

        $this->assertEquals(500000, $room->total_daily_rate);
    }

    #[Test]
    public function it_returns_zero_total_daily_rate_when_base_price_null(): void
    {
        $room = Room::factory()->create(['base_price' => null]);

        $this->assertEquals(0, $room->total_daily_rate);
    }

    #[Test]
    public function it_returns_room_class_color_attribute(): void
    {
        $vvip = Room::factory()->vvip()->create();
        $vip = Room::factory()->vip()->create();
        $kelas1 = Room::factory()->create(['room_class' => 'Kelas I']);
        $kelas2 = Room::factory()->create(['room_class' => 'Kelas II']);
        $kelas3 = Room::factory()->kelas3()->create();
        $icu = Room::factory()->icu()->create();

        $this->assertEquals('danger', $vvip->room_class_color);
        $this->assertEquals('warning', $vip->room_class_color);
        $this->assertEquals('primary', $kelas1->room_class_color);
        $this->assertEquals('info', $kelas2->room_class_color);
        $this->assertEquals('success', $kelas3->room_class_color);
        $this->assertEquals('purple', $icu->room_class_color);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $room = Room::factory()->create();

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);

        $room->delete();

        $this->assertSoftDeleted('rooms', ['id' => $room->id]);
    }

    #[Test]
    public function it_can_create_vvip_room(): void
    {
        $room = Room::factory()->vvip()->create();

        $this->assertEquals('VVIP', $room->room_class);
        $this->assertGreaterThanOrEqual(3000000, $room->base_price);
    }

    #[Test]
    public function it_can_create_vip_room(): void
    {
        $room = Room::factory()->vip()->create();

        $this->assertEquals('VIP', $room->room_class);
        $this->assertGreaterThanOrEqual(1500000, $room->base_price);
    }

    #[Test]
    public function it_can_create_kelas3_room(): void
    {
        $room = Room::factory()->kelas3()->create();

        $this->assertEquals('Kelas III', $room->room_class);
        $this->assertEquals(6, $room->total_beds);
    }

    #[Test]
    public function it_can_create_icu_room(): void
    {
        $room = Room::factory()->icu()->create();

        $this->assertEquals('ICU', $room->room_class);
        $this->assertEquals(2, $room->total_beds);
    }

    #[Test]
    public function it_can_create_full_room(): void
    {
        $room = Room::factory()->full()->create();

        $this->assertEquals(0, $room->available_beds);
        $this->assertTrue($room->is_full);
    }

    #[Test]
    public function it_can_create_available_room(): void
    {
        $room = Room::factory()->available()->create(['total_beds' => 5]);

        $this->assertEquals(5, $room->available_beds);
        $this->assertFalse($room->is_full);
    }
}
