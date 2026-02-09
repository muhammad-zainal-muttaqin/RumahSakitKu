<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Prescription;
use App\Models\Clinical\PrescriptionItem;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrescriptionItemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $item = new PrescriptionItem();

        $expectedFillable = [
            'prescription_id',
            'medicine_id',
            'generic_name',
            'brand_name',
            'dosage_form',
            'strength',
            'quantity',
            'unit',
            'dosage_instructions',
            'frequency',
            'duration_days',
            'route_of_administration',
            'instructions',
            'is_substitutable',
            'substitution_notes',
            'unit_price',
            'total_price',
            'is_dispensed',
            'dispensed_quantity',
            'dispensed_at',
            'notes',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $item->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $item = new PrescriptionItem();
        $casts = $item->getCasts();

        $this->assertArrayHasKey('quantity', $casts);
        $this->assertArrayHasKey('unit_price', $casts);
        $this->assertArrayHasKey('total_price', $casts);
        $this->assertArrayHasKey('duration_days', $casts);
        $this->assertArrayHasKey('dispensed_quantity', $casts);
        $this->assertArrayHasKey('dispensed_at', $casts);
        $this->assertArrayHasKey('is_substitutable', $casts);
        $this->assertArrayHasKey('is_dispensed', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_prescription(): void
    {
        $item = new PrescriptionItem();
        $relation = $item->prescription();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('prescription_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Prescription::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_medicine(): void
    {
        $item = new PrescriptionItem();
        $relation = $item->medicine();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medicine_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Medicine::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_dispensed_scope(): void
    {
        PrescriptionItem::factory()->count(2)->dispensed()->create();
        PrescriptionItem::factory()->count(3)->pending()->create();

        $results = PrescriptionItem::dispensed()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($item) => $item->is_dispensed === true));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        PrescriptionItem::factory()->count(2)->dispensed()->create();
        PrescriptionItem::factory()->count(3)->pending()->create();

        $results = PrescriptionItem::pending()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($item) => $item->is_dispensed === false));
    }

    #[Test]
    public function it_has_substitutable_scope(): void
    {
        PrescriptionItem::factory()->count(2)->substitutable()->create();
        PrescriptionItem::factory()->count(3)->notSubstitutable()->create();

        $results = PrescriptionItem::substitutable()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($item) => $item->is_substitutable === true));
    }

    #[Test]
    public function it_has_by_medicine_scope(): void
    {
        $medicine1 = Medicine::factory()->create();
        $medicine2 = Medicine::factory()->create();

        $item1 = PrescriptionItem::factory()->create(['medicine_id' => $medicine1->id]);
        PrescriptionItem::factory()->create(['medicine_id' => $medicine2->id]);

        $results = PrescriptionItem::byMedicine($medicine1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($item1));
    }

    #[Test]
    public function it_calculates_total_price_on_saving(): void
    {
        $item = PrescriptionItem::factory()->create([
            'quantity' => 10,
            'unit_price' => 5000,
        ]);

        $this->assertEquals(50000, $item->total_price);
    }

    #[Test]
    public function it_calculates_total_price_with_decimal_values(): void
    {
        $item = PrescriptionItem::factory()->create([
            'quantity' => 5.5,
            'unit_price' => 10000,
        ]);

        $this->assertEquals(55000, $item->total_price);
    }

    #[Test]
    public function it_returns_formatted_dosage_attribute(): void
    {
        $item = PrescriptionItem::factory()->create([
            'dosage_instructions' => '3x1',
            'frequency' => 'Sehari 3 kali',
            'route_of_administration' => 'oral',
            'duration_days' => 7,
        ]);

        $formatted = $item->formatted_dosage;

        $this->assertStringContainsString('3x1', $formatted);
        $this->assertStringContainsString('Sehari 3 kali', $formatted);
        $this->assertStringContainsString('via oral', $formatted);
        $this->assertStringContainsString('for 7 days', $formatted);
    }

    #[Test]
    public function it_returns_partial_formatted_dosage_when_some_fields_null(): void
    {
        $item = PrescriptionItem::factory()->create([
            'dosage_instructions' => '2x1',
            'frequency' => null,
            'route_of_administration' => null,
            'duration_days' => null,
        ]);

        $formatted = $item->formatted_dosage;

        $this->assertEquals('2x1', $formatted);
    }

    #[Test]
    public function it_returns_full_medicine_name_attribute(): void
    {
        $item = PrescriptionItem::factory()->create([
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Panadol',
            'strength' => '500mg',
        ]);

        $this->assertEquals('Paracetamol (Panadol) 500mg', $item->full_medicine_name);
    }

    #[Test]
    public function it_returns_generic_name_only_when_no_brand_or_strength(): void
    {
        $item = PrescriptionItem::factory()->create([
            'generic_name' => 'Aspirin',
            'brand_name' => null,
            'strength' => null,
        ]);

        $this->assertEquals('Aspirin', $item->full_medicine_name);
    }

    #[Test]
    public function it_detects_partially_dispensed_status(): void
    {
        $item = PrescriptionItem::factory()->create([
            'quantity' => 10,
            'is_dispensed' => true,
            'dispensed_quantity' => 5,
        ]);

        $this->assertTrue($item->is_partially_dispensed);
    }

    #[Test]
    public function it_returns_not_partially_dispensed_when_fully_dispensed(): void
    {
        $item = PrescriptionItem::factory()->create([
            'quantity' => 10,
            'is_dispensed' => true,
            'dispensed_quantity' => 10,
        ]);

        $this->assertFalse($item->is_partially_dispensed);
    }

    #[Test]
    public function it_returns_not_partially_dispensed_when_not_dispensed(): void
    {
        $item = PrescriptionItem::factory()->create([
            'quantity' => 10,
            'is_dispensed' => false,
            'dispensed_quantity' => null,
        ]);

        $this->assertFalse($item->is_partially_dispensed);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $item = PrescriptionItem::factory()->create();

        $this->assertDatabaseHas('prescription_items', ['id' => $item->id]);

        $item->delete();

        $this->assertSoftDeleted('prescription_items', ['id' => $item->id]);
    }

    #[Test]
    public function it_can_create_dispensed_item(): void
    {
        $item = PrescriptionItem::factory()->dispensed()->create();

        $this->assertTrue($item->is_dispensed);
        $this->assertNotNull($item->dispensed_quantity);
        $this->assertNotNull($item->dispensed_at);
    }

    #[Test]
    public function it_can_create_partially_dispensed_item(): void
    {
        $item = PrescriptionItem::factory()->partiallyDispensed(5)->create([
            'quantity' => 10,
        ]);

        $this->assertTrue($item->is_dispensed);
        $this->assertEquals(5, $item->dispensed_quantity);
        $this->assertNotNull($item->dispensed_at);
    }

    #[Test]
    public function it_can_create_pending_item(): void
    {
        $item = PrescriptionItem::factory()->pending()->create();

        $this->assertFalse($item->is_dispensed);
        $this->assertNull($item->dispensed_quantity);
        $this->assertNull($item->dispensed_at);
    }

    #[Test]
    public function it_can_create_substitutable_item(): void
    {
        $item = PrescriptionItem::factory()->substitutable()->create();

        $this->assertTrue($item->is_substitutable);
    }

    #[Test]
    public function it_can_create_not_substitutable_item(): void
    {
        $item = PrescriptionItem::factory()->notSubstitutable()->create();

        $this->assertFalse($item->is_substitutable);
    }

    #[Test]
    public function it_can_create_with_custom_price(): void
    {
        $item = PrescriptionItem::factory()->withPrice(15000, 20)->create();

        $this->assertEquals(15000, $item->unit_price);
        $this->assertEquals(20, $item->quantity);
        $this->assertEquals(300000, $item->total_price);
    }
}
