<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\LaboratoryResult;
use App\Models\MasterData\Employee;
use App\Models\MasterData\LabTest;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LaboratoryResultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $result = new LaboratoryResult();

        $expectedFillable = [
            'laboratory_order_id',
            'lab_test_id',
            'result_value',
            'result_text',
            'flag',
            'reference_range',
            'unit',
            'notes',
            'test_method',
            'analyzer_machine',
            'validated_by',
            'validated_at',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $result->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $result = new LaboratoryResult();
        $casts = $result->getCasts();

        $this->assertArrayHasKey('validated_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_laboratory_order(): void
    {
        $result = new LaboratoryResult();
        $relation = $result->laboratoryOrder();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('laboratory_order_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(LaboratoryOrder::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_lab_test(): void
    {
        $result = new LaboratoryResult();
        $relation = $result->labTest();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('lab_test_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(LabTest::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_validated_by_user(): void
    {
        $result = new LaboratoryResult();
        $relation = $result->validatedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('validated_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_abnormal_scope(): void
    {
        LaboratoryResult::factory()->count(3)->create(['flag' => 'normal']);
        LaboratoryResult::factory()->count(2)->create(['flag' => 'high']);
        LaboratoryResult::factory()->count(2)->create(['flag' => 'critical']);

        $results = LaboratoryResult::abnormal()->get();

        $this->assertCount(4, $results);
        $this->assertTrue($results->every(fn ($result) => in_array($result->flag, ['low', 'high', 'abnormal', 'critical'])));
    }

    #[Test]
    public function it_has_critical_scope(): void
    {
        LaboratoryResult::factory()->count(3)->create(['flag' => 'high']);
        LaboratoryResult::factory()->count(2)->create(['flag' => 'critical']);

        $results = LaboratoryResult::critical()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->flag === 'critical'));
    }

    #[Test]
    public function it_has_validated_scope(): void
    {
        LaboratoryResult::factory()->count(3)->create(['validated_at' => null]);
        LaboratoryResult::factory()->count(2)->create(['validated_at' => now()]);

        $results = LaboratoryResult::validated()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->validated_at !== null));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        LaboratoryResult::factory()->count(3)->create([
            'result_value' => null,
            'result_text' => null,
        ]);
        LaboratoryResult::factory()->count(2)->create(['result_value' => '10.5']);

        $results = LaboratoryResult::pending()->get();

        $this->assertCount(3, $results);
    }

    #[Test]
    public function it_has_with_flag_scope(): void
    {
        LaboratoryResult::factory()->count(3)->create(['flag' => 'normal']);
        LaboratoryResult::factory()->count(2)->create(['flag' => 'high']);

        $results = LaboratoryResult::withFlag('high')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->flag === 'high'));
    }

    #[Test]
    public function it_returns_correct_flag_color_attribute(): void
    {
        $normal = new LaboratoryResult(['flag' => 'normal']);
        $low = new LaboratoryResult(['flag' => 'low']);
        $high = new LaboratoryResult(['flag' => 'high']);
        $abnormal = new LaboratoryResult(['flag' => 'abnormal']);
        $critical = new LaboratoryResult(['flag' => 'critical']);
        $default = new LaboratoryResult(['flag' => null]);

        $this->assertEquals('success', $normal->flag_color);
        $this->assertEquals('warning', $low->flag_color);
        $this->assertEquals('warning', $high->flag_color);
        $this->assertEquals('warning', $abnormal->flag_color);
        $this->assertEquals('danger', $critical->flag_color);
        $this->assertEquals('gray', $default->flag_color);
    }

    #[Test]
    public function it_returns_correct_flag_label_attribute(): void
    {
        $normal = new LaboratoryResult(['flag' => 'normal']);
        $low = new LaboratoryResult(['flag' => 'low']);
        $high = new LaboratoryResult(['flag' => 'high']);
        $abnormal = new LaboratoryResult(['flag' => 'abnormal']);
        $critical = new LaboratoryResult(['flag' => 'critical']);

        $this->assertEquals('Normal', $normal->flag_label);
        $this->assertEquals('Rendah', $low->flag_label);
        $this->assertEquals('Tinggi', $high->flag_label);
        $this->assertEquals('Abnormal', $abnormal->flag_label);
        $this->assertEquals('Kritis', $critical->flag_label);
    }

    #[Test]
    public function it_returns_is_abnormal_attribute(): void
    {
        $normal = new LaboratoryResult(['flag' => 'normal']);
        $high = new LaboratoryResult(['flag' => 'high']);
        $low = new LaboratoryResult(['flag' => 'low']);
        $abnormal = new LaboratoryResult(['flag' => 'abnormal']);
        $critical = new LaboratoryResult(['flag' => 'critical']);

        $this->assertFalse($normal->is_abnormal);
        $this->assertTrue($high->is_abnormal);
        $this->assertTrue($low->is_abnormal);
        $this->assertTrue($abnormal->is_abnormal);
        $this->assertTrue($critical->is_abnormal);
    }

    #[Test]
    public function it_returns_is_critical_attribute(): void
    {
        $normal = new LaboratoryResult(['flag' => 'normal']);
        $high = new LaboratoryResult(['flag' => 'high']);
        $critical = new LaboratoryResult(['flag' => 'critical']);

        $this->assertFalse($normal->is_critical);
        $this->assertFalse($high->is_critical);
        $this->assertTrue($critical->is_critical);
    }

    #[Test]
    public function it_returns_is_validated_attribute(): void
    {
        $pending = new LaboratoryResult(['validated_at' => null]);
        $validated = new LaboratoryResult(['validated_at' => now()]);

        $this->assertFalse($pending->is_validated);
        $this->assertTrue($validated->is_validated);
    }

    #[Test]
    public function it_returns_display_value_attribute(): void
    {
        $numericResult = new LaboratoryResult(['result_value' => 10.5, 'result_text' => null]);
        $textResult = new LaboratoryResult(['result_value' => null, 'result_text' => 'Positive']);
        $emptyResult = new LaboratoryResult(['result_value' => null, 'result_text' => null]);

        $this->assertEquals('10.5', $numericResult->display_value);
        $this->assertEquals('Positive', $textResult->display_value);
        $this->assertEquals('-', $emptyResult->display_value);
    }

    #[Test]
    public function it_returns_formatted_reference_range_attribute(): void
    {
        $withUnit = new LaboratoryResult([
            'reference_range' => '10-20',
            'unit' => 'mg/dL',
        ]);
        $withoutUnit = new LaboratoryResult([
            'reference_range' => '10-20',
            'unit' => null,
        ]);
        $emptyRange = new LaboratoryResult([
            'reference_range' => null,
            'unit' => 'mg/dL',
        ]);

        $this->assertEquals('10-20 mg/dL', $withUnit->formatted_reference_range);
        $this->assertEquals('10-20', $withoutUnit->formatted_reference_range);
        $this->assertEquals('-', $emptyRange->formatted_reference_range);
    }

    #[Test]
    public function it_can_create_with_numeric_result(): void
    {
        $result = LaboratoryResult::factory()->withNumericResult(15.5)->create();

        $this->assertEquals(15.5, $result->result_value);
        $this->assertNull($result->result_text);
    }

    #[Test]
    public function it_can_create_with_text_result(): void
    {
        $result = LaboratoryResult::factory()->withTextResult('Negative')->create();

        $this->assertEquals('Negative', $result->result_text);
        $this->assertNull($result->result_value);
    }

    #[Test]
    public function it_can_create_with_flag(): void
    {
        $result = LaboratoryResult::factory()->withFlag('high')->create();

        $this->assertEquals('high', $result->flag);
    }

    #[Test]
    public function it_can_create_validated_result(): void
    {
        $validator = Employee::factory()->create();
        $result = LaboratoryResult::factory()->validated($validator->id)->create();

        $this->assertNotNull($result->validated_at);
        $this->assertEquals($validator->id, $result->validated_by);
    }

    #[Test]
    public function it_can_create_critical_result(): void
    {
        $result = LaboratoryResult::factory()->critical()->create();

        $this->assertEquals('critical', $result->flag);
        $this->assertTrue($result->is_critical);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $result = LaboratoryResult::factory()->create();

        $this->assertDatabaseHas('laboratory_results', ['id' => $result->id]);

        $result->delete();

        $this->assertSoftDeleted('laboratory_results', ['id' => $result->id]);
    }
}
