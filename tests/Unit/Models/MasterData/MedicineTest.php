<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\Clinical\PrescriptionItem;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $medicine = new Medicine();

        $expectedFillable = [
            'code',
            'name',
            'classification',
            'dosage_form',
            'unit',
            'manufacturer',
            'registration_number',
            'is_generic',
            'stock',
            'min_stock',
            'selling_price',
            'purchase_price',
            'expired_date',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $medicine->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $medicine = new Medicine();
        $casts = $medicine->getCasts();

        $this->assertArrayHasKey('stock', $casts);
        $this->assertArrayHasKey('min_stock', $casts);
        $this->assertArrayHasKey('purchase_price', $casts);
        $this->assertArrayHasKey('selling_price', $casts);
        $this->assertArrayHasKey('expired_date', $casts);
        $this->assertArrayHasKey('is_generic', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_has_prescription_items_relationship(): void
    {
        $medicine = new Medicine();
        $relation = $medicine->prescriptionItems();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('medicine_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(PrescriptionItem::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_prescription_items(): void
    {
        $medicine = Medicine::factory()->create();
        PrescriptionItem::factory()->count(3)->create(['medicine_id' => $medicine->id]);

        $this->assertInstanceOf(Collection::class, $medicine->prescriptionItems);
        $this->assertCount(3, $medicine->prescriptionItems);
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Medicine::factory()->count(3)->create(['is_active' => true]);
        Medicine::factory()->count(2)->inactive()->create();

        $results = Medicine::active()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($medicine) => $medicine->is_active === true));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_name(): void
    {
        $medicine1 = Medicine::factory()->create(['name' => 'Paracetamol']);
        $medicine2 = Medicine::factory()->create(['name' => 'Amoxicillin']);

        $results = Medicine::search('Paracetamol')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($medicine1));
        $this->assertFalse($results->contains($medicine2));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_code(): void
    {
        $medicine = Medicine::factory()->create(['code' => 'MED001']);
        Medicine::factory()->create(['code' => 'MED002']);

        $results = Medicine::search('MED001')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($medicine));
    }

    #[Test]
    public function it_has_by_classification_scope(): void
    {
        $obatKeras = Medicine::factory()->prescriptionOnly()->create();
        Medicine::factory()->generic()->create();

        $results = Medicine::byClassification('obat_keras')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($obatKeras));
    }

    #[Test]
    public function it_has_by_dosage_form_scope(): void
    {
        $tablet = Medicine::factory()->create(['dosage_form' => 'tablet']);
        Medicine::factory()->create(['dosage_form' => 'sirup']);

        $results = Medicine::byDosageForm('tablet')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($tablet));
    }

    #[Test]
    public function it_has_low_stock_scope(): void
    {
        Medicine::factory()->count(2)->lowStock()->create();
        Medicine::factory()->count(3)->create([
            'stock' => 100,
            'min_stock' => 10,
        ]);

        $results = Medicine::lowStock()->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_out_of_stock_scope(): void
    {
        Medicine::factory()->count(2)->outOfStock()->create();
        Medicine::factory()->count(3)->create(['stock' => 10]);

        $results = Medicine::outOfStock()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($medicine) => $medicine->stock <= 0));
    }

    #[Test]
    public function it_has_expired_scope(): void
    {
        Medicine::factory()->count(2)->expired()->create();
        Medicine::factory()->count(3)->create([
            'expired_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $results = Medicine::expired()->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_expiring_soon_scope(): void
    {
        Medicine::factory()->count(2)->expiringSoon()->create();
        Medicine::factory()->count(3)->create([
            'expired_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $results = Medicine::expiringSoon()->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_returns_is_low_stock_true_when_stock_below_min(): void
    {
        $medicine = Medicine::factory()->lowStock()->create();

        $this->assertTrue($medicine->is_low_stock);
    }

    #[Test]
    public function it_returns_is_low_stock_false_when_stock_above_min(): void
    {
        $medicine = Medicine::factory()->create([
            'stock' => 100,
            'min_stock' => 10,
        ]);

        $this->assertFalse($medicine->is_low_stock);
    }

    #[Test]
    public function it_returns_is_out_of_stock_true_when_stock_zero(): void
    {
        $medicine = Medicine::factory()->outOfStock()->create();

        $this->assertTrue($medicine->is_out_of_stock);
    }

    #[Test]
    public function it_returns_is_out_of_stock_false_when_stock_positive(): void
    {
        $medicine = Medicine::factory()->create(['stock' => 10]);

        $this->assertFalse($medicine->is_out_of_stock);
    }

    #[Test]
    public function it_returns_is_expired_true_when_past_expiry(): void
    {
        $medicine = Medicine::factory()->expired()->create();

        $this->assertTrue($medicine->is_expired);
    }

    #[Test]
    public function it_returns_is_expired_false_when_future_expiry(): void
    {
        $medicine = Medicine::factory()->create([
            'expired_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->assertFalse($medicine->is_expired);
    }

    #[Test]
    public function it_returns_is_expiring_soon_true_within_30_days(): void
    {
        $medicine = Medicine::factory()->expiringSoon()->create();

        $this->assertTrue($medicine->is_expiring_soon);
    }

    #[Test]
    public function it_returns_is_expiring_soon_false_when_more_than_30_days(): void
    {
        $medicine = Medicine::factory()->create([
            'expired_date' => now()->addDays(60)->format('Y-m-d'),
        ]);

        $this->assertFalse($medicine->is_expiring_soon);
    }

    #[Test]
    public function it_returns_stock_status_out_of_stock_when_empty(): void
    {
        $medicine = Medicine::factory()->outOfStock()->create();

        $this->assertEquals('out_of_stock', $medicine->stock_status);
    }

    #[Test]
    public function it_returns_stock_status_low_stock_when_below_minimum(): void
    {
        $medicine = Medicine::factory()->lowStock()->create();

        $this->assertEquals('low_stock', $medicine->stock_status);
    }

    #[Test]
    public function it_returns_stock_status_in_stock_when_normal(): void
    {
        $medicine = Medicine::factory()->create([
            'stock' => 100,
            'min_stock' => 10,
        ]);

        $this->assertEquals('in_stock', $medicine->stock_status);
    }

    #[Test]
    public function it_returns_expiration_status_expired_when_past_date(): void
    {
        $medicine = Medicine::factory()->expired()->create();

        $this->assertEquals('expired', $medicine->expiration_status);
    }

    #[Test]
    public function it_returns_expiration_status_expiring_soon_when_within_30_days(): void
    {
        $medicine = Medicine::factory()->expiringSoon()->create();

        $this->assertEquals('expiring_soon', $medicine->expiration_status);
    }

    #[Test]
    public function it_returns_expiration_status_valid_when_future_date(): void
    {
        $medicine = Medicine::factory()->create([
            'expired_date' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->assertEquals('valid', $medicine->expiration_status);
    }

    #[Test]
    public function it_can_update_stock_in(): void
    {
        $medicine = Medicine::factory()->create([
            'stock' => 100,
        ]);

        $result = $medicine->updateStock(50, 'in');

        $this->assertTrue($result);
        $this->assertEquals(150, $medicine->fresh()->stock);
    }

    #[Test]
    public function it_can_update_stock_out(): void
    {
        $medicine = Medicine::factory()->create([
            'stock' => 100,
        ]);

        $result = $medicine->updateStock(30, 'out');

        $this->assertTrue($result);
        $this->assertEquals(70, $medicine->fresh()->stock);
    }

    #[Test]
    public function it_returns_classification_label_attribute(): void
    {
        $obatBebas = Medicine::factory()->create(['classification' => 'obat_bebas']);
        $obatKeras = Medicine::factory()->prescriptionOnly()->create();
        $narkotika = Medicine::factory()->narcotic()->create();

        $this->assertEquals('Obat Bebas', $obatBebas->classification_label);
        $this->assertEquals('Obat Keras', $obatKeras->classification_label);
        $this->assertEquals('Narkotika', $narkotika->classification_label);
    }

    #[Test]
    public function it_returns_dosage_form_label_attribute(): void
    {
        $tablet = Medicine::factory()->create(['dosage_form' => 'tablet']);
        $kapsul = Medicine::factory()->create(['dosage_form' => 'kapsul']);
        $sirup = Medicine::factory()->create(['dosage_form' => 'sirup']);

        $this->assertEquals('Tablet', $tablet->dosage_form_label);
        $this->assertEquals('Kapsul', $kapsul->dosage_form_label);
        $this->assertEquals('Sirup', $sirup->dosage_form_label);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $medicine = Medicine::factory()->create();

        $this->assertDatabaseHas('medicines', ['id' => $medicine->id]);

        $medicine->delete();

        $this->assertSoftDeleted('medicines', ['id' => $medicine->id]);
    }

    #[Test]
    public function it_can_create_generic_medicine(): void
    {
        $medicine = Medicine::factory()->generic()->create();

        $this->assertTrue($medicine->is_generic);
    }

    #[Test]
    public function it_can_create_branded_medicine(): void
    {
        $medicine = Medicine::factory()->branded()->create();

        $this->assertFalse($medicine->is_generic);
    }

    #[Test]
    public function it_can_create_narcotic_medicine(): void
    {
        $medicine = Medicine::factory()->narcotic()->create();

        $this->assertEquals('narkotika', $medicine->classification);
    }

    #[Test]
    public function it_can_create_psychotropic_medicine(): void
    {
        $medicine = Medicine::factory()->psychotropic()->create();

        $this->assertEquals('psikotropik', $medicine->classification);
    }

    #[Test]
    public function it_can_create_with_custom_stock(): void
    {
        $medicine = Medicine::factory()->withStock(50, 10)->create();

        $this->assertEquals(50, $medicine->stock);
        $this->assertEquals(10, $medicine->min_stock);
    }
}
