<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Cppt;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CpptTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $cppt = new Cppt();

        $expectedFillable = [
            'medical_record_id',
            'patient_id',
            'visit_id',
            'cppt_date',
            'cppt_time',
            'subjective',
            'objective',
            'assessment',
            'plan',
            'instruction',
            'progress_notes',
            'verified_by',
            'verified_at',
            'is_verified',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $cppt->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $cppt = new Cppt();
        $casts = $cppt->getCasts();

        $this->assertArrayHasKey('cppt_date', $casts);
        $this->assertArrayHasKey('cppt_time', $casts);
        $this->assertArrayHasKey('verified_at', $casts);
        $this->assertArrayHasKey('is_verified', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_medical_record(): void
    {
        $cppt = new Cppt();
        $relation = $cppt->medicalRecord();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $cppt = new Cppt();
        $relation = $cppt->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $cppt = new Cppt();
        $relation = $cppt->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_verified_by_employee(): void
    {
        $cppt = new Cppt();
        $relation = $cppt->verifiedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('verified_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_verified_scope(): void
    {
        Cppt::factory()->count(2)->verified()->create();
        Cppt::factory()->count(3)->unverified()->create();

        $results = Cppt::verified()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($cppt) => $cppt->is_verified === true));
    }

    #[Test]
    public function it_has_unverified_scope(): void
    {
        Cppt::factory()->count(2)->verified()->create();
        Cppt::factory()->count(3)->unverified()->create();

        $results = Cppt::unverified()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($cppt) => $cppt->is_verified === false));
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayCppt = Cppt::factory()->create(['cppt_date' => $today]);
        Cppt::factory()->create(['cppt_date' => $yesterday]);

        $results = Cppt::onDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($todayCppt));
    }

    #[Test]
    public function it_has_by_creator_scope(): void
    {
        $cppt1 = Cppt::factory()->create(['created_by' => 1]);
        Cppt::factory()->create(['created_by' => 2]);

        $results = Cppt::byCreator(1)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($cppt1));
    }

    #[Test]
    public function it_returns_soap_array_attribute(): void
    {
        $cppt = Cppt::factory()->create([
            'subjective' => 'Patient feels better',
            'objective' => 'Vital signs stable',
            'assessment' => 'Improving condition',
            'plan' => 'Continue treatment',
        ]);

        $soapArray = $cppt->soap_array;

        $this->assertIsArray($soapArray);
        $this->assertArrayHasKey('subjective', $soapArray);
        $this->assertArrayHasKey('objective', $soapArray);
        $this->assertArrayHasKey('assessment', $soapArray);
        $this->assertArrayHasKey('plan', $soapArray);
        $this->assertEquals('Patient feels better', $soapArray['subjective']);
        $this->assertEquals('Vital signs stable', $soapArray['objective']);
        $this->assertEquals('Improving condition', $soapArray['assessment']);
        $this->assertEquals('Continue treatment', $soapArray['plan']);
    }

    #[Test]
    public function it_returns_full_soap_note_attribute(): void
    {
        $cppt = Cppt::factory()->create([
            'subjective' => 'Patient feels better',
            'objective' => 'Vital signs stable',
            'assessment' => 'Improving condition',
            'plan' => 'Continue treatment',
        ]);

        $fullSoapNote = $cppt->full_soap_note;

        $this->assertStringContainsString('**Subjective:**', $fullSoapNote);
        $this->assertStringContainsString('Patient feels better', $fullSoapNote);
        $this->assertStringContainsString('**Objective:**', $fullSoapNote);
        $this->assertStringContainsString('Vital signs stable', $fullSoapNote);
        $this->assertStringContainsString('**Assessment:**', $fullSoapNote);
        $this->assertStringContainsString('Improving condition', $fullSoapNote);
        $this->assertStringContainsString('**Plan:**', $fullSoapNote);
        $this->assertStringContainsString('Continue treatment', $fullSoapNote);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $cppt = Cppt::factory()->create();

        $this->assertDatabaseHas('cppts', ['id' => $cppt->id]);

        $cppt->delete();

        $this->assertSoftDeleted('cppts', ['id' => $cppt->id]);
    }

    #[Test]
    public function it_can_create_verified_cppt(): void
    {
        $cppt = Cppt::factory()->verified()->create();

        $this->assertTrue($cppt->is_verified);
        $this->assertNotNull($cppt->verified_at);
        $this->assertNotNull($cppt->verified_by);
    }

    #[Test]
    public function it_can_create_unverified_cppt(): void
    {
        $cppt = Cppt::factory()->unverified()->create();

        $this->assertFalse($cppt->is_verified);
        $this->assertNull($cppt->verified_at);
        $this->assertNull($cppt->verified_by);
    }

    #[Test]
    public function it_can_create_with_soap_content(): void
    {
        $cppt = Cppt::factory()->withSOAP(
            'Subjective content',
            'Objective content',
            'Assessment content',
            'Plan content'
        )->create();

        $this->assertEquals('Subjective content', $cppt->subjective);
        $this->assertEquals('Objective content', $cppt->objective);
        $this->assertEquals('Assessment content', $cppt->assessment);
        $this->assertEquals('Plan content', $cppt->plan);
    }

    #[Test]
    public function it_can_create_today_cppt(): void
    {
        $cppt = Cppt::factory()->today()->create();

        $this->assertEquals(today()->format('Y-m-d'), $cppt->cppt_date->format('Y-m-d'));
    }
}
