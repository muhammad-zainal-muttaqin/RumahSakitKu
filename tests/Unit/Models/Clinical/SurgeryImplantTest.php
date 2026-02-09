<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Surgery;
use App\Models\Clinical\SurgeryImplant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SurgeryImplantTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $implant = new SurgeryImplant();

        $expectedFillable = [
            'surgery_id',
            'implant_name',
            'implant_type',
            'serial_number',
            'batch_number',
            'manufacturer',
            'quantity',
            'unit',
            'expiry_date',
            'notes',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $implant->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $implant = new SurgeryImplant();
        $casts = $implant->getCasts();

        $this->assertArrayHasKey('quantity', $casts);
        $this->assertArrayHasKey('expiry_date', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_surgery(): void
    {
        $implant = new SurgeryImplant();
        $relation = $implant->surgery();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('surgery_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Surgery::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        SurgeryImplant::factory()->count(2)->create(['implant_type' => 'prosthetic']);
        SurgeryImplant::factory()->count(3)->create(['implant_type' => 'orthopedic']);

        $results = SurgeryImplant::byType('prosthetic')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($implant) => $implant->implant_type === 'prosthetic'));
    }

    #[Test]
    public function it_has_search_scope(): void
    {
        SurgeryImplant::factory()->create(['implant_name' => 'Hip Prosthesis']);
        SurgeryImplant::factory()->create(['serial_number' => 'SN123456']);
        SurgeryImplant::factory()->create(['manufacturer' => 'MedTech Inc']);
        SurgeryImplant::factory()->create(['implant_name' => 'Knee Replacement']);

        $results = SurgeryImplant::search('Prosthesis')->get();
        $this->assertCount(1, $results);

        $results2 = SurgeryImplant::search('SN123456')->get();
        $this->assertCount(1, $results2);

        $results3 = SurgeryImplant::search('MedTech')->get();
        $this->assertCount(1, $results3);
    }

    #[Test]
    public function it_returns_implant_types_list(): void
    {
        $types = SurgeryImplant::getImplantTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('prosthetic', $types);
        $this->assertArrayHasKey('orthopedic', $types);
        $this->assertArrayHasKey('cardiac', $types);
        $this->assertArrayHasKey('other', $types);
        $this->assertEquals('Prostetik', $types['prosthetic']);
        $this->assertEquals('Ortopedi', $types['orthopedic']);
    }

    #[Test]
    public function it_returns_units_list(): void
    {
        $units = SurgeryImplant::getUnits();

        $this->assertIsArray($units);
        $this->assertArrayHasKey('pcs', $units);
        $this->assertArrayHasKey('set', $units);
        $this->assertArrayHasKey('pair', $units);
        $this->assertArrayHasKey('unit', $units);
        $this->assertEquals('Pcs', $units['pcs']);
        $this->assertEquals('Set', $units['set']);
    }

    #[Test]
    public function it_returns_implant_type_label_attribute(): void
    {
        $implant = new SurgeryImplant(['implant_type' => 'prosthetic']);
        $unknownImplant = new SurgeryImplant(['implant_type' => 'custom_type']);

        $this->assertEquals('Prostetik', $implant->implant_type_label);
        $this->assertEquals('Custom_type', $unknownImplant->implant_type_label);
    }

    #[Test]
    public function it_returns_unit_label_attribute(): void
    {
        $implant = new SurgeryImplant(['unit' => 'pcs']);
        $unknownUnit = new SurgeryImplant(['unit' => 'custom']);

        $this->assertEquals('Pcs', $implant->unit_label);
        $this->assertEquals('custom', $unknownUnit->unit_label);
    }

    #[Test]
    public function it_returns_is_expired_attribute(): void
    {
        $expired = new SurgeryImplant(['expiry_date' => now()->subDay()]);
        $notExpired = new SurgeryImplant(['expiry_date' => now()->addMonth()]);
        $noExpiry = new SurgeryImplant(['expiry_date' => null]);

        $this->assertTrue($expired->is_expired);
        $this->assertFalse($notExpired->is_expired);
        $this->assertFalse($noExpiry->is_expired);
    }

    #[Test]
    public function it_returns_is_expiring_soon_attribute(): void
    {
        $expiringSoon = new SurgeryImplant(['expiry_date' => now()->addDays(15)]);
        $notExpiringSoon = new SurgeryImplant(['expiry_date' => now()->addDays(60)]);
        $expired = new SurgeryImplant(['expiry_date' => now()->subDay()]);
        $noExpiry = new SurgeryImplant(['expiry_date' => null]);

        $this->assertTrue($expiringSoon->is_expiring_soon);
        $this->assertFalse($notExpiringSoon->is_expiring_soon);
        $this->assertFalse($expired->is_expiring_soon);
        $this->assertFalse($noExpiry->is_expiring_soon);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $implant = SurgeryImplant::factory()->create();

        $this->assertDatabaseHas('surgery_implants', ['id' => $implant->id]);

        $implant->delete();

        $this->assertSoftDeleted('surgery_implants', ['id' => $implant->id]);
    }

    #[Test]
    public function it_can_create_with_expiry_date(): void
    {
        $expiryDate = now()->addYear();
        $implant = SurgeryImplant::factory()->withExpiryDate($expiryDate)->create();

        $this->assertEquals($expiryDate->format('Y-m-d'), $implant->expiry_date->format('Y-m-d'));
    }

    #[Test]
    public function it_can_create_with_serial_number(): void
    {
        $implant = SurgeryImplant::factory()->withSerialNumber('SN123456789')->create();

        $this->assertEquals('SN123456789', $implant->serial_number);
    }

    #[Test]
    public function it_can_create_prosthetic_implant(): void
    {
        $implant = SurgeryImplant::factory()->prosthetic()->create();

        $this->assertEquals('prosthetic', $implant->implant_type);
    }

    #[Test]
    public function it_can_create_orthopedic_implant(): void
    {
        $implant = SurgeryImplant::factory()->orthopedic()->create();

        $this->assertEquals('orthopedic', $implant->implant_type);
    }
}
