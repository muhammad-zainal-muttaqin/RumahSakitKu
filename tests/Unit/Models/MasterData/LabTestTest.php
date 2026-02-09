<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\LabTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LabTestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $labTest = new LabTest();

        $expectedFillable = [
            'test_code',
            'name',
            'category',
            'specimen_type',
            'reference_value',
            'unit',
            'base_price',
            'is_active',
        ];

        $this->assertEquals($expectedFillable, $labTest->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $labTest = new LabTest();
        $casts = $labTest->getCasts();

        $this->assertArrayHasKey('base_price', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
        $this->assertEquals('decimal:2', $casts['base_price']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $labTest = LabTest::create([
            'test_code' => 'LAB001',
            'name' => 'Complete Blood Count',
            'category' => 'hematologi',
            'base_price' => 150000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('lab_tests', ['id' => $labTest->id]);

        $labTest->delete();

        $this->assertSoftDeleted('lab_tests', ['id' => $labTest->id]);
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        LabTest::create([
            'test_code' => 'LAB001',
            'name' => 'Test 1',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB002',
            'name' => 'Test 2',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB003',
            'name' => 'Test 3',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => false,
        ]);

        $results = LabTest::active()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($test) => $test->is_active === true));
    }

    #[Test]
    public function it_has_by_category_scope(): void
    {
        LabTest::create([
            'test_code' => 'LAB001',
            'name' => 'Hematology Test',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB002',
            'name' => 'Another Hematology',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB003',
            'name' => 'Chemistry Test',
            'category' => 'kimia_darah',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $results = LabTest::byCategory('hematologi')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($test) => $test->category === 'hematologi'));
    }

    #[Test]
    public function it_has_by_specimen_type_scope(): void
    {
        LabTest::create([
            'test_code' => 'LAB001',
            'name' => 'Blood Test',
            'category' => 'hematologi',
            'specimen_type' => 'darah',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB002',
            'name' => 'Urine Test',
            'category' => 'urinalisa',
            'specimen_type' => 'urine',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $results = LabTest::bySpecimenType('darah')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('darah', $results->first()->specimen_type);
    }

    #[Test]
    public function it_has_search_scope_for_name(): void
    {
        LabTest::create([
            'test_code' => 'LAB001',
            'name' => 'Complete Blood Count',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB002',
            'name' => 'Blood Glucose',
            'category' => 'gula_darah',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'LAB003',
            'name' => 'Urine Analysis',
            'category' => 'urinalisa',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $results = LabTest::search('Blood')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_search_scope_for_test_code(): void
    {
        LabTest::create([
            'test_code' => 'CBC001',
            'name' => 'Complete Blood Count',
            'category' => 'hematologi',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        LabTest::create([
            'test_code' => 'GLU001',
            'name' => 'Blood Glucose',
            'category' => 'gula_darah',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $results = LabTest::search('CBC')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('CBC001', $results->first()->test_code);
    }

    #[Test]
    public function it_returns_formatted_base_price_attribute(): void
    {
        $labTest = new LabTest(['base_price' => 150000.50]);

        $this->assertEquals('Rp 150.001', $labTest->formatted_base_price);
    }

    #[Test]
    public function it_returns_category_label_for_hematologi(): void
    {
        $labTest = new LabTest(['category' => 'hematologi']);

        $this->assertEquals('Hematologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_kimia_darah(): void
    {
        $labTest = new LabTest(['category' => 'kimia_darah']);

        $this->assertEquals('Kimia Darah', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_urinalisa(): void
    {
        $labTest = new LabTest(['category' => 'urinalisa']);

        $this->assertEquals('Urinalisa', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_mikrobiologi(): void
    {
        $labTest = new LabTest(['category' => 'mikrobiologi']);

        $this->assertEquals('Mikrobiologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_imunologi(): void
    {
        $labTest = new LabTest(['category' => 'imunologi']);

        $this->assertEquals('Imunologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_serologi(): void
    {
        $labTest = new LabTest(['category' => 'serologi']);

        $this->assertEquals('Serologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_endokrinologi(): void
    {
        $labTest = new LabTest(['category' => 'endokrinologi']);

        $this->assertEquals('Endokrinologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_tumor_marker(): void
    {
        $labTest = new LabTest(['category' => 'tumor_marker']);

        $this->assertEquals('Tumor Marker', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_elektrolit(): void
    {
        $labTest = new LabTest(['category' => 'elektrolit']);

        $this->assertEquals('Elektrolit', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_gula_darah(): void
    {
        $labTest = new LabTest(['category' => 'gula_darah']);

        $this->assertEquals('Gula Darah', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_fungsi_ginjal(): void
    {
        $labTest = new LabTest(['category' => 'fungsi_ginjal']);

        $this->assertEquals('Fungsi Ginjal', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_fungsi_hati(): void
    {
        $labTest = new LabTest(['category' => 'fungsi_hati']);

        $this->assertEquals('Fungsi Hati', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_lemak_darah(): void
    {
        $labTest = new LabTest(['category' => 'lemak_darah']);

        $this->assertEquals('Lemak Darah', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_koagulasi(): void
    {
        $labTest = new LabTest(['category' => 'koagulasi']);

        $this->assertEquals('Koagulasi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_gas_darah(): void
    {
        $labTest = new LabTest(['category' => 'gas_darah']);

        $this->assertEquals('Gas Darah', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_sitologi(): void
    {
        $labTest = new LabTest(['category' => 'sitologi']);

        $this->assertEquals('Sitologi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_patologi_anatomi(): void
    {
        $labTest = new LabTest(['category' => 'patologi_anatomi']);

        $this->assertEquals('Patologi Anatomi', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_molekuler(): void
    {
        $labTest = new LabTest(['category' => 'molekuler']);

        $this->assertEquals('Molekuler', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_lainnya(): void
    {
        $labTest = new LabTest(['category' => 'lainnya']);

        $this->assertEquals('Lainnya', $labTest->category_label);
    }

    #[Test]
    public function it_returns_category_label_for_unknown_category(): void
    {
        $labTest = new LabTest(['category' => 'unknown_category']);

        $this->assertEquals('Unknown Category', $labTest->category_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_darah(): void
    {
        $labTest = new LabTest(['specimen_type' => 'darah']);

        $this->assertEquals('Darah', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_urine(): void
    {
        $labTest = new LabTest(['specimen_type' => 'urine']);

        $this->assertEquals('Urine', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_feses(): void
    {
        $labTest = new LabTest(['specimen_type' => 'feses']);

        $this->assertEquals('Feses', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_sputum(): void
    {
        $labTest = new LabTest(['specimen_type' => 'sputum']);

        $this->assertEquals('Sputum', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_lendir(): void
    {
        $labTest = new LabTest(['specimen_type' => 'lendir']);

        $this->assertEquals('Lendir', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_jaringan(): void
    {
        $labTest = new LabTest(['specimen_type' => 'jaringan']);

        $this->assertEquals('Jaringan', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_cairan_serebrospinal(): void
    {
        $labTest = new LabTest(['specimen_type' => 'cairan_serebrospinal']);

        $this->assertEquals('Cairan Serebrospinal', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_cairan_sendi(): void
    {
        $labTest = new LabTest(['specimen_type' => 'cairan_sendi']);

        $this->assertEquals('Cairan Sendi', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_cairan_pleura(): void
    {
        $labTest = new LabTest(['specimen_type' => 'cairan_pleura']);

        $this->assertEquals('Cairan Pleura', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_swab(): void
    {
        $labTest = new LabTest(['specimen_type' => 'swab']);

        $this->assertEquals('Swab', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_lainnya(): void
    {
        $labTest = new LabTest(['specimen_type' => 'lainnya']);

        $this->assertEquals('Lainnya', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_specimen_type_label_for_unknown_type(): void
    {
        $labTest = new LabTest(['specimen_type' => 'unknown_type']);

        $this->assertEquals('Unknown Type', $labTest->specimen_type_label);
    }

    #[Test]
    public function it_returns_category_color_for_hematologi(): void
    {
        $labTest = new LabTest(['category' => 'hematologi']);

        $this->assertEquals('danger', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_kimia_darah(): void
    {
        $labTest = new LabTest(['category' => 'kimia_darah']);

        $this->assertEquals('primary', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_urinalisa(): void
    {
        $labTest = new LabTest(['category' => 'urinalisa']);

        $this->assertEquals('warning', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_mikrobiologi(): void
    {
        $labTest = new LabTest(['category' => 'mikrobiologi']);

        $this->assertEquals('success', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_imunologi(): void
    {
        $labTest = new LabTest(['category' => 'imunologi']);

        $this->assertEquals('info', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_serologi(): void
    {
        $labTest = new LabTest(['category' => 'serologi']);

        $this->assertEquals('purple', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_endokrinologi(): void
    {
        $labTest = new LabTest(['category' => 'endokrinologi']);

        $this->assertEquals('pink', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_tumor_marker(): void
    {
        $labTest = new LabTest(['category' => 'tumor_marker']);

        $this->assertEquals('orange', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_elektrolit(): void
    {
        $labTest = new LabTest(['category' => 'elektrolit']);

        $this->assertEquals('cyan', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_gula_darah(): void
    {
        $labTest = new LabTest(['category' => 'gula_darah']);

        $this->assertEquals('teal', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_fungsi_ginjal(): void
    {
        $labTest = new LabTest(['category' => 'fungsi_ginjal']);

        $this->assertEquals('indigo', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_fungsi_hati(): void
    {
        $labTest = new LabTest(['category' => 'fungsi_hati']);

        $this->assertEquals('amber', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_lemak_darah(): void
    {
        $labTest = new LabTest(['category' => 'lemak_darah']);

        $this->assertEquals('lime', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_koagulasi(): void
    {
        $labTest = new LabTest(['category' => 'koagulasi']);

        $this->assertEquals('rose', $labTest->category_color);
    }

    #[Test]
    public function it_returns_category_color_for_gas_darah(): void
    {
        $labTest = new LabTest(['category' => 'gas_darah']);

        $this->assertEquals('sky', $labTest->category_color);
    }

    #[Test]
    public function it_returns_gray_color_for_unknown_category(): void
    {
        $labTest = new LabTest(['category' => 'unknown_category']);

        $this->assertEquals('gray', $labTest->category_color);
    }

    #[Test]
    public function it_can_be_created_with_all_attributes(): void
    {
        $labTest = LabTest::create([
            'test_code' => 'CBC001',
            'name' => 'Complete Blood Count',
            'category' => 'hematologi',
            'specimen_type' => 'darah',
            'reference_value' => 'Male: 13.5-17.5 g/dL, Female: 12.0-16.0 g/dL',
            'unit' => 'g/dL',
            'base_price' => 150000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('lab_tests', [
            'id' => $labTest->id,
            'test_code' => 'CBC001',
            'name' => 'Complete Blood Count',
        ]);

        $this->assertEquals('CBC001', $labTest->test_code);
        $this->assertEquals('Complete Blood Count', $labTest->name);
        $this->assertEquals('hematologi', $labTest->category);
        $this->assertEquals('darah', $labTest->specimen_type);
        $this->assertEquals(150000, $labTest->base_price);
        $this->assertTrue($labTest->is_active);
    }
}
