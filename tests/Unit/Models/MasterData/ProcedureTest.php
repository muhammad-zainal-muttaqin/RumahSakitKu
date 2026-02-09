<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\Procedure;
use App\Models\MasterData\ProcedureCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcedureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $procedure = new Procedure();

        $expectedFillable = [
            'procedure_code',
            'name',
            'category_id',
            'base_price',
            'bpjs_tariff',
            'material_cost',
            'is_bpjs_covered',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $procedure->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $procedure = new Procedure();
        $casts = $procedure->getCasts();

        $this->assertArrayHasKey('base_price', $casts);
        $this->assertArrayHasKey('bpjs_tariff', $casts);
        $this->assertArrayHasKey('material_cost', $casts);
        $this->assertArrayHasKey('is_bpjs_covered', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
        $this->assertEquals('decimal:2', $casts['base_price']);
        $this->assertEquals('decimal:2', $casts['bpjs_tariff']);
        $this->assertEquals('decimal:2', $casts['material_cost']);
        $this->assertEquals('boolean', $casts['is_bpjs_covered']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $procedure = Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Test Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('procedures', ['id' => $procedure->id]);

        $procedure->delete();

        $this->assertSoftDeleted('procedures', ['id' => $procedure->id]);
    }

    #[Test]
    public function it_belongs_to_category(): void
    {
        $procedure = new Procedure();
        $relation = $procedure->category();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(ProcedureCategory::class, $relation->getRelated());
        $this->assertEquals('category_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Active Procedure 1',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Active Procedure 2',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Inactive Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => false,
        ]);

        $results = Procedure::active()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($p) => $p->is_active === true));
    }

    #[Test]
    public function it_has_bpjs_covered_scope(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'BPJS Covered',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_bpjs_covered' => true,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'BPJS Covered 2',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_bpjs_covered' => true,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Non BPJS',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_bpjs_covered' => false,
            'is_active' => true,
        ]);

        $results = Procedure::bpjsCovered()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($p) => $p->is_bpjs_covered === true));
    }

    #[Test]
    public function it_has_by_category_scope(): void
    {
        $category1 = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Category 1',
            'is_active' => true,
        ]);
        $category2 = ProcedureCategory::create([
            'code' => 'CAT002',
            'name' => 'Category 2',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Procedure 1',
            'category_id' => $category1->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Procedure 2',
            'category_id' => $category1->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Procedure 3',
            'category_id' => $category2->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $results = Procedure::byCategory($category1->id)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($p) => $p->category_id === $category1->id));
    }

    #[Test]
    public function it_has_search_scope_for_name(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Appendectomy',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Cholecystectomy',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Hernia Repair',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $results = Procedure::search('ectomy')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_search_scope_for_procedure_code(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'APP-001',
            'name' => 'Appendectomy',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'CHO-001',
            'name' => 'Cholecystectomy',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $results = Procedure::search('APP')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('APP-001', $results->first()->procedure_code);
    }

    #[Test]
    public function it_returns_formatted_base_price_attribute(): void
    {
        $procedure = new Procedure(['base_price' => 750000.50]);

        $this->assertEquals('Rp 750.001', $procedure->formatted_base_price);
    }

    #[Test]
    public function it_returns_formatted_bpjs_tariff_when_present(): void
    {
        $procedure = new Procedure(['bpjs_tariff' => 500000]);

        $this->assertEquals('Rp 500.000', $procedure->formatted_bpjs_tariff);
    }

    #[Test]
    public function it_returns_dash_for_formatted_bpjs_tariff_when_null(): void
    {
        $procedure = new Procedure(['bpjs_tariff' => null]);

        $this->assertEquals('-', $procedure->formatted_bpjs_tariff);
    }

    #[Test]
    public function it_calculates_total_price_attribute(): void
    {
        $procedure = new Procedure([
            'base_price' => 500000,
            'material_cost' => 150000,
        ]);

        $this->assertEquals(650000, $procedure->total_price);
    }

    #[Test]
    public function it_calculates_total_price_with_null_material_cost(): void
    {
        $procedure = new Procedure([
            'base_price' => 500000,
            'material_cost' => null,
        ]);

        $this->assertEquals(500000, $procedure->total_price);
    }

    #[Test]
    public function it_calculates_total_price_with_null_base_price(): void
    {
        $procedure = new Procedure([
            'base_price' => null,
            'material_cost' => 150000,
        ]);

        $this->assertEquals(150000, $procedure->total_price);
    }

    #[Test]
    public function it_returns_formatted_total_price_attribute(): void
    {
        $procedure = new Procedure([
            'base_price' => 500000,
            'material_cost' => 150000,
        ]);

        $this->assertEquals('Rp 650.000', $procedure->formatted_total_price);
    }

    #[Test]
    public function it_can_be_created_with_all_attributes(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        $procedure = Procedure::create([
            'procedure_code' => 'APP-001',
            'name' => 'Appendectomy',
            'category_id' => $category->id,
            'base_price' => 5000000,
            'bpjs_tariff' => 3500000,
            'material_cost' => 800000,
            'is_bpjs_covered' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('procedures', [
            'id' => $procedure->id,
            'procedure_code' => 'APP-001',
            'name' => 'Appendectomy',
        ]);

        $this->assertEquals('APP-001', $procedure->procedure_code);
        $this->assertEquals('Appendectomy', $procedure->name);
        $this->assertEquals($category->id, $procedure->category_id);
        $this->assertEquals(5000000, $procedure->base_price);
        $this->assertEquals(3500000, $procedure->bpjs_tariff);
        $this->assertEquals(800000, $procedure->material_cost);
        $this->assertTrue($procedure->is_bpjs_covered);
        $this->assertTrue($procedure->is_active);
    }

    #[Test]
    public function it_casts_is_bpjs_covered_to_boolean(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $procedure = Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Test Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_bpjs_covered' => 1,
            'is_active' => true,
        ]);

        $this->assertIsBool($procedure->is_bpjs_covered);
        $this->assertTrue($procedure->is_bpjs_covered);
    }

    #[Test]
    public function it_casts_prices_to_decimal(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $procedure = Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Test Procedure',
            'category_id' => $category->id,
            'base_price' => 500000.555,
            'bpjs_tariff' => 350000.999,
            'material_cost' => 100000.123,
            'is_active' => true,
        ]);

        $this->assertEquals(500000.56, $procedure->base_price);
        $this->assertEquals(350001.00, $procedure->bpjs_tariff);
        $this->assertEquals(100000.12, $procedure->material_cost);
    }
}
