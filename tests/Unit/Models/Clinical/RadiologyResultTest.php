<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\RadiologyOrder;
use App\Models\Clinical\RadiologyResult;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RadiologyResultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $result = new RadiologyResult();

        $expectedFillable = [
            'radiology_order_id',
            'result_images',
            'report_text',
            'conclusion',
            'recommendation',
            'radiologist_id',
            'reported_at',
            'technician_notes',
            'exposure_parameters',
            'dose_info',
            'quality_assurance',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $result->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $result = new RadiologyResult();
        $casts = $result->getCasts();

        $this->assertArrayHasKey('result_images', $casts);
        $this->assertArrayHasKey('reported_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_radiology_order(): void
    {
        $result = new RadiologyResult();
        $relation = $result->radiologyOrder();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('radiology_order_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(RadiologyOrder::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_radiologist(): void
    {
        $result = new RadiologyResult();
        $relation = $result->radiologist();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('radiologist_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_by_radiologist_scope(): void
    {
        $radiologist = Employee::factory()->create();
        RadiologyResult::factory()->count(3)->create();
        RadiologyResult::factory()->count(2)->create(['radiologist_id' => $radiologist->id]);

        $results = RadiologyResult::byRadiologist($radiologist->id)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->radiologist_id === $radiologist->id));
    }

    #[Test]
    public function it_has_reported_today_scope(): void
    {
        RadiologyResult::factory()->count(2)->create(['reported_at' => now()]);
        RadiologyResult::factory()->count(3)->create(['reported_at' => now()->subDay()]);

        $results = RadiologyResult::reportedToday()->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_reported_scope(): void
    {
        RadiologyResult::factory()->count(2)->create(['reported_at' => now()]);
        RadiologyResult::factory()->count(3)->create(['reported_at' => null]);

        $results = RadiologyResult::reported()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->reported_at !== null));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        RadiologyResult::factory()->count(2)->create(['reported_at' => null]);
        RadiologyResult::factory()->count(3)->create(['reported_at' => now()]);

        $results = RadiologyResult::pending()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($result) => $result->reported_at === null));
    }

    #[Test]
    public function it_has_search_scope(): void
    {
        RadiologyResult::factory()->create(['conclusion' => 'Pneumonia detected']);
        RadiologyResult::factory()->create(['report_text' => 'Normal chest X-ray']);
        RadiologyResult::factory()->create(['recommendation' => 'Follow up in 2 weeks']);
        RadiologyResult::factory()->create(['conclusion' => 'Normal findings']);

        $results = RadiologyResult::search('Pneumonia')->get();
        $this->assertCount(1, $results);

        $results2 = RadiologyResult::search('Normal')->get();
        $this->assertCount(2, $results2);

        $results3 = RadiologyResult::search('Follow up')->get();
        $this->assertCount(1, $results3);
    }

    #[Test]
    public function it_returns_is_reported_attribute(): void
    {
        $pending = new RadiologyResult(['reported_at' => null]);
        $reported = new RadiologyResult(['reported_at' => now()]);

        $this->assertFalse($pending->is_reported);
        $this->assertTrue($reported->is_reported);
    }

    #[Test]
    public function it_returns_image_urls_attribute(): void
    {
        $result = new RadiologyResult([
            'result_images' => ['images/xray1.jpg', 'images/xray2.jpg'],
        ]);

        $urls = $result->image_urls;

        $this->assertCount(2, $urls);
        $this->assertStringContainsString('images/xray1.jpg', $urls[0]);
        $this->assertStringContainsString('images/xray2.jpg', $urls[1]);
    }

    #[Test]
    public function it_returns_empty_image_urls_when_no_images(): void
    {
        $result = new RadiologyResult(['result_images' => []]);
        $resultNull = new RadiologyResult(['result_images' => null]);

        $this->assertEquals([], $result->image_urls);
        $this->assertEquals([], $resultNull->image_urls);
    }

    #[Test]
    public function it_returns_first_image_url_attribute(): void
    {
        $result = new RadiologyResult([
            'result_images' => ['images/xray1.jpg', 'images/xray2.jpg'],
        ]);
        $emptyResult = new RadiologyResult(['result_images' => []]);

        $this->assertNotNull($result->first_image_url);
        $this->assertStringContainsString('images/xray1.jpg', $result->first_image_url);
        $this->assertNull($emptyResult->first_image_url);
    }

    #[Test]
    public function it_returns_formatted_report_attribute(): void
    {
        $result = new RadiologyResult([
            'report_text' => 'Chest X-ray shows clear lungs',
            'conclusion' => 'No acute findings',
            'recommendation' => 'Routine follow-up',
        ]);

        $formatted = $result->formatted_report;

        $this->assertStringContainsString('HASIL PEMERIKSAAN:', $formatted);
        $this->assertStringContainsString('Chest X-ray shows clear lungs', $formatted);
        $this->assertStringContainsString('KESIMPULAN:', $formatted);
        $this->assertStringContainsString('No acute findings', $formatted);
        $this->assertStringContainsString('SARAN:', $formatted);
        $this->assertStringContainsString('Routine follow-up', $formatted);
    }

    #[Test]
    public function it_returns_radiologist_name_attribute(): void
    {
        $radiologist = Employee::factory()->create(['name' => 'Dr. Radiologist']);
        $result = new RadiologyResult(['radiologist_id' => $radiologist->id]);
        $result->setRelation('radiologist', $radiologist);

        $this->assertEquals('Dr. Radiologist', $result->radiologist_name);
    }

    #[Test]
    public function it_returns_dash_for_radiologist_name_when_no_radiologist(): void
    {
        $result = new RadiologyResult(['radiologist_id' => null]);

        $this->assertEquals('-', $result->radiologist_name);
    }

    #[Test]
    public function it_returns_report_date_formatted_attribute(): void
    {
        $reportedAt = now();
        $result = new RadiologyResult(['reported_at' => $reportedAt]);
        $pendingResult = new RadiologyResult(['reported_at' => null]);

        $this->assertEquals($reportedAt->format('d M Y H:i'), $result->report_date_formatted);
        $this->assertEquals('-', $pendingResult->report_date_formatted);
    }

    #[Test]
    public function it_can_create_with_report(): void
    {
        $result = RadiologyResult::factory()->withReport(
            'Normal findings',
            'No abnormalities detected',
            'Routine follow-up'
        )->create();

        $this->assertEquals('Normal findings', $result->report_text);
        $this->assertEquals('No abnormalities detected', $result->conclusion);
        $this->assertEquals('Routine follow-up', $result->recommendation);
    }

    #[Test]
    public function it_can_create_reported_result(): void
    {
        $radiologist = Employee::factory()->create();
        $result = RadiologyResult::factory()->reported($radiologist->id)->create();

        $this->assertNotNull($result->reported_at);
        $this->assertEquals($radiologist->id, $result->radiologist_id);
        $this->assertTrue($result->is_reported);
    }

    #[Test]
    public function it_can_create_with_images(): void
    {
        $images = ['radiology/xray1.jpg', 'radiology/xray2.jpg'];
        $result = RadiologyResult::factory()->withImages($images)->create();

        $this->assertEquals($images, $result->result_images);
        $this->assertCount(2, $result->image_urls);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $result = RadiologyResult::factory()->create();

        $this->assertDatabaseHas('radiology_results', ['id' => $result->id]);

        $result->delete();

        $this->assertSoftDeleted('radiology_results', ['id' => $result->id]);
    }
}
