<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PolyclinicTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $polyclinic = new Polyclinic();

        $expectedFillable = [
            'code',
            'name',
            'category',
            'queue_prefix',
            'bpjs_poli_code',
            'bpjs_poli_name',
            'description',
            'is_active',
            'max_queue_per_day',
            'open_time',
            'close_time',
        ];

        $this->assertEquals($expectedFillable, $polyclinic->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $polyclinic = new Polyclinic();
        $casts = $polyclinic->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('max_queue_per_day', $casts);
        $this->assertArrayHasKey('open_time', $casts);
        $this->assertArrayHasKey('close_time', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_has_visits_relationship(): void
    {
        $polyclinic = new Polyclinic();
        $relation = $polyclinic->visits();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('polyclinic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_visits(): void
    {
        $polyclinic = Polyclinic::factory()->create();
        Visit::factory()->count(3)->create(['polyclinic_id' => $polyclinic->id]);

        $this->assertInstanceOf(Collection::class, $polyclinic->visits);
        $this->assertCount(3, $polyclinic->visits);
        $this->assertTrue($polyclinic->visits->every(fn ($visit) => $visit instanceof Visit));
    }

    #[Test]
    public function it_has_employees_relationship(): void
    {
        $polyclinic = new Polyclinic();
        $relation = $polyclinic->employees();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('polyclinic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_employees(): void
    {
        $polyclinic = Polyclinic::factory()->create();
        Employee::factory()->count(2)->create(['specialist_polyclinic_id' => $polyclinic->id]);

        $this->assertInstanceOf(Collection::class, $polyclinic->employees);
        $this->assertCount(2, $polyclinic->employees);
    }

    #[Test]
    public function it_has_doctors_relationship(): void
    {
        $polyclinic = Polyclinic::factory()->create();
        Employee::factory()->doctor()->create(['specialist_polyclinic_id' => $polyclinic->id]);
        Employee::factory()->nurse()->create(['specialist_polyclinic_id' => $polyclinic->id]);

        $this->assertCount(1, $polyclinic->doctors);
        $this->assertTrue($polyclinic->doctors->every(fn ($emp) => $emp->is_doctor === true));
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Polyclinic::factory()->count(3)->create(['is_active' => true]);
        Polyclinic::factory()->count(2)->create(['is_active' => false]);

        $results = Polyclinic::active()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($polyclinic) => $polyclinic->is_active === true));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_name(): void
    {
        $polyclinic1 = Polyclinic::factory()->create(['name' => 'Poliklinik Umum']);
        $polyclinic2 = Polyclinic::factory()->create(['name' => 'Poliklinik Gigi']);

        $results = Polyclinic::search('Umum')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($polyclinic1));
        $this->assertFalse($results->contains($polyclinic2));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_code(): void
    {
        $polyclinic = Polyclinic::factory()->create(['code' => 'POL001']);
        Polyclinic::factory()->create(['code' => 'POL002']);

        $results = Polyclinic::search('POL001')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($polyclinic));
    }

    #[Test]
    public function it_calculates_today_visit_count_attribute(): void
    {
        $polyclinic = Polyclinic::factory()->create();
        Visit::factory()->count(3)->create([
            'polyclinic_id' => $polyclinic->id,
            'visit_date' => today()->format('Y-m-d'),
        ]);
        Visit::factory()->count(2)->create([
            'polyclinic_id' => $polyclinic->id,
            'visit_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->assertEquals(3, $polyclinic->today_visit_count);
    }

    #[Test]
    public function it_returns_has_reached_quota_true_when_quota_reached(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'max_queue_per_day' => 10,
        ]);
        Visit::factory()->count(10)->create([
            'polyclinic_id' => $polyclinic->id,
            'visit_date' => today()->format('Y-m-d'),
        ]);

        $this->assertTrue($polyclinic->has_reached_quota);
    }

    #[Test]
    public function it_returns_has_reached_quota_false_when_quota_not_set(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'max_queue_per_day' => null,
        ]);

        $this->assertFalse($polyclinic->has_reached_quota);
    }

    #[Test]
    public function it_returns_formatted_operating_hours_attribute(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'open_time' => '08:00',
            'close_time' => '16:00',
        ]);

        $this->assertEquals('08:00 - 16:00', $polyclinic->formatted_operating_hours);
    }

    #[Test]
    public function it_returns_dash_when_operating_hours_not_set(): void
    {
        $polyclinic = Polyclinic::factory()->create([
            'open_time' => null,
            'close_time' => null,
        ]);

        $this->assertEquals('-', $polyclinic->formatted_operating_hours);
    }

    #[Test]
    public function it_returns_category_label_attribute(): void
    {
        $umum = Polyclinic::factory()->umum()->create();
        $spesialis = Polyclinic::factory()->spesialis()->create();
        $gigi = Polyclinic::factory()->gigi()->create();

        $this->assertEquals('Umum', $umum->category_label);
        $this->assertEquals('Spesialis', $spesialis->category_label);
        $this->assertEquals('Gigi', $gigi->category_label);
    }

    #[Test]
    public function it_returns_category_color_attribute(): void
    {
        $umum = Polyclinic::factory()->umum()->create();
        $spesialis = Polyclinic::factory()->spesialis()->create();
        $gigi = Polyclinic::factory()->gigi()->create();
        $bedah = Polyclinic::factory()->create(['category' => 'bedah']);

        $this->assertEquals('gray', $umum->category_color);
        $this->assertEquals('primary', $spesialis->category_color);
        $this->assertEquals('success', $gigi->category_color);
        $this->assertEquals('danger', $bedah->category_color);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $polyclinic = Polyclinic::factory()->create();

        $this->assertDatabaseHas('polyclinics', ['id' => $polyclinic->id]);

        $polyclinic->delete();

        $this->assertSoftDeleted('polyclinics', ['id' => $polyclinic->id]);
    }

    #[Test]
    public function it_can_create_umum_polyclinic(): void
    {
        $polyclinic = Polyclinic::factory()->umum()->create();

        $this->assertEquals('umum', $polyclinic->category);
        $this->assertEquals('Poliklinik Umum', $polyclinic->name);
        $this->assertEquals('U', $polyclinic->queue_prefix);
    }

    #[Test]
    public function it_can_create_spesialis_polyclinic(): void
    {
        $polyclinic = Polyclinic::factory()->spesialis()->create();

        $this->assertEquals('spesialis', $polyclinic->category);
        $this->assertStringStartsWith('Poliklinik Spesialis', $polyclinic->name);
        $this->assertEquals('S', $polyclinic->queue_prefix);
    }

    #[Test]
    public function it_can_create_with_quota(): void
    {
        $polyclinic = Polyclinic::factory()->withQuota(100)->create();

        $this->assertEquals(100, $polyclinic->max_queue_per_day);
    }
}
