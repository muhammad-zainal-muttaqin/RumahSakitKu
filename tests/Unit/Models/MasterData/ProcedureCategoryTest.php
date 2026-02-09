<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\Procedure;
use App\Models\MasterData\ProcedureCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcedureCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $category = new ProcedureCategory();

        $expectedFillable = [
            'code',
            'name',
            'description',
            'color',
            'icon',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $category->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $category = new ProcedureCategory();
        $casts = $category->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
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

        $this->assertDatabaseHas('procedure_categories', ['id' => $category->id]);

        $category->delete();

        $this->assertSoftDeleted('procedure_categories', ['id' => $category->id]);
    }

    #[Test]
    public function it_has_procedures_relationship(): void
    {
        $category = new ProcedureCategory();
        $relation = $category->procedures();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Procedure::class, $relation->getRelated());
        $this->assertEquals('procedure_category_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function it_has_active_procedures_relationship(): void
    {
        $category = new ProcedureCategory();
        $relation = $category->activeProcedures();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Procedure::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_procedures(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Procedure 1',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Procedure 2',
            'category_id' => $category->id,
            'base_price' => 750000,
            'is_active' => true,
        ]);

        $this->assertCount(2, $category->procedures);
        $this->assertTrue($category->procedures->every(fn ($p) => $p instanceof Procedure));
    }

    #[Test]
    public function it_active_procedures_only_returns_active_ones(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Active Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Inactive Procedure',
            'category_id' => $category->id,
            'base_price' => 750000,
            'is_active' => false,
        ]);

        $this->assertCount(1, $category->activeProcedures);
        $this->assertTrue($category->activeProcedures->first()->is_active);
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Active Category 1',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'CAT002',
            'name' => 'Active Category 2',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'CAT003',
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        $results = ProcedureCategory::active()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($c) => $c->is_active === true));
    }

    #[Test]
    public function it_has_ordered_scope(): void
    {
        ProcedureCategory::create([
            'code' => 'Z',
            'name' => 'Zebra',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'A',
            'name' => 'Apple',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'M',
            'name' => 'Mango',
            'is_active' => true,
        ]);

        $results = ProcedureCategory::ordered()->get();

        $this->assertEquals('Apple', $results->first()->name);
        $this->assertEquals('Zebra', $results->last()->name);
    }

    #[Test]
    public function it_has_search_scope_for_name(): void
    {
        ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'RAD001',
            'name' => 'Radiology',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'LAB001',
            'name' => 'Laboratory',
            'is_active' => true,
        ]);

        $results = ProcedureCategory::search('Surg')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Surgery', $results->first()->name);
    }

    #[Test]
    public function it_has_search_scope_for_code(): void
    {
        ProcedureCategory::create([
            'code' => 'SUR-001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);
        ProcedureCategory::create([
            'code' => 'RAD-001',
            'name' => 'Radiology',
            'is_active' => true,
        ]);

        $results = ProcedureCategory::search('SUR')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('SUR-001', $results->first()->code);
    }

    #[Test]
    public function it_returns_procedure_count_attribute(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Procedure 1',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC002',
            'name' => 'Procedure 2',
            'category_id' => $category->id,
            'base_price' => 750000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Procedure 3',
            'category_id' => $category->id,
            'base_price' => 1000000,
            'is_active' => false,
        ]);

        $this->assertEquals(3, $category->procedure_count);
    }

    #[Test]
    public function it_returns_zero_procedure_count_when_no_procedures(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'EMP001',
            'name' => 'Empty Category',
            'is_active' => true,
        ]);

        $this->assertEquals(0, $category->procedure_count);
    }

    #[Test]
    public function it_returns_active_procedure_count_attribute(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
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
            'base_price' => 750000,
            'is_active' => true,
        ]);
        Procedure::create([
            'procedure_code' => 'PROC003',
            'name' => 'Inactive Procedure',
            'category_id' => $category->id,
            'base_price' => 1000000,
            'is_active' => false,
        ]);

        $this->assertEquals(2, $category->active_procedure_count);
    }

    #[Test]
    public function it_returns_zero_active_procedure_count_when_none_active(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Inactive Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => false,
        ]);

        $this->assertEquals(0, $category->active_procedure_count);
    }

    #[Test]
    public function it_can_be_created_with_all_attributes(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR-001',
            'name' => 'General Surgery',
            'description' => 'Procedures related to general surgery',
            'color' => '#FF0000',
            'icon' => 'heroicon-o-scissors',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('procedure_categories', [
            'id' => $category->id,
            'code' => 'SUR-001',
            'name' => 'General Surgery',
        ]);

        $this->assertEquals('SUR-001', $category->code);
        $this->assertEquals('General Surgery', $category->name);
        $this->assertEquals('Procedures related to general surgery', $category->description);
        $this->assertEquals('#FF0000', $category->color);
        $this->assertEquals('heroicon-o-scissors', $category->icon);
        $this->assertTrue($category->is_active);
    }

    #[Test]
    public function it_can_be_created_with_minimal_attributes(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'RAD001',
            'name' => 'Radiology',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('procedure_categories', [
            'id' => $category->id,
            'code' => 'RAD001',
            'name' => 'Radiology',
        ]);

        $this->assertNull($category->description);
        $this->assertNull($category->color);
        $this->assertNull($category->icon);
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => 1,
        ]);

        $this->assertIsBool($category->is_active);
        $this->assertTrue($category->is_active);
    }

    #[Test]
    public function it_can_update_attributes(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        $category->update([
            'name' => 'Updated Name',
            'description' => 'New description',
            'color' => '#00FF00',
        ]);

        $freshCategory = $category->fresh();
        $this->assertEquals('Updated Name', $freshCategory->name);
        $this->assertEquals('New description', $freshCategory->description);
        $this->assertEquals('#00FF00', $freshCategory->color);
    }

    #[Test]
    public function it_can_be_deactivated(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'CAT001',
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $category->update(['is_active' => false]);

        $this->assertFalse($category->fresh()->is_active);
    }

    #[Test]
    public function it_procedures_are_deleted_when_category_is_deleted(): void
    {
        $category = ProcedureCategory::create([
            'code' => 'SUR001',
            'name' => 'Surgery',
            'is_active' => true,
        ]);

        $procedure = Procedure::create([
            'procedure_code' => 'PROC001',
            'name' => 'Test Procedure',
            'category_id' => $category->id,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $category->delete();

        // Procedure should still exist (soft delete on category doesn't cascade)
        $this->assertDatabaseHas('procedures', ['id' => $procedure->id]);
    }
}
