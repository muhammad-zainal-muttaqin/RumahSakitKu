<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $assessment = new Assessment();

        $expectedFillable = [
            'medical_record_id',
            'patient_id',
            'visit_id',
            'assessed_by',
            'systolic_bp',
            'diastolic_bp',
            'pulse_rate',
            'respiratory_rate',
            'body_temperature',
            'oxygen_saturation',
            'blood_glucose',
            'weight',
            'height',
            'bmi',
            'pain_scale',
            'pain_location',
            'pain_description',
            'consciousness',
            'gcs_eye',
            'gcs_verbal',
            'gcs_motor',
            'gcs_total',
            'fall_risk',
            'fall_risk_factors',
            'allergy_history',
            'drug_allergy',
            'food_allergy',
            'chief_complaint',
            'present_illness_history',
            'past_medical_history',
            'family_history',
            'social_history',
            'general_condition',
            'head_examination',
            'neck_examination',
            'thorax_examination',
            'heart_examination',
            'lung_examination',
            'abdomen_examination',
            'extremities_examination',
            'neurological_examination',
            'skin_examination',
            'primary_diagnosis_code',
            'primary_diagnosis_name',
            'secondary_diagnoses',
            'diagnosis_type',
            'assessed_at',
        ];

        $this->assertEquals($expectedFillable, $assessment->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $assessment = new Assessment();
        $casts = $assessment->getCasts();

        $this->assertArrayHasKey('systolic_bp', $casts);
        $this->assertArrayHasKey('diastolic_bp', $casts);
        $this->assertArrayHasKey('weight', $casts);
        $this->assertArrayHasKey('height', $casts);
        $this->assertArrayHasKey('bmi', $casts);
        $this->assertArrayHasKey('assessed_at', $casts);
        $this->assertArrayHasKey('fall_risk_factors', $casts);
        $this->assertArrayHasKey('secondary_diagnoses', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_medical_record(): void
    {
        $assessment = new Assessment();
        $relation = $assessment->medicalRecord();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $assessment = new Assessment();
        $relation = $assessment->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $assessment = new Assessment();
        $relation = $assessment->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_assessed_by_employee(): void
    {
        $assessment = new Assessment();
        $relation = $assessment->assessedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('assessed_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        $initial = Assessment::factory()->initial()->create();
        Assessment::factory()->followUp()->create();

        $results = Assessment::byType('primer')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($initial));
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayAssessment = Assessment::factory()->create(['assessed_at' => $today]);
        Assessment::factory()->create(['assessed_at' => $yesterday]);

        $results = Assessment::onDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($todayAssessment));
    }

    #[Test]
    public function it_has_by_assessor_scope(): void
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        $assessment1 = Assessment::factory()->create(['assessed_by' => $employee1->id]);
        Assessment::factory()->create(['assessed_by' => $employee2->id]);

        $results = Assessment::byAssessor($employee1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($assessment1));
    }

    #[Test]
    public function it_calculates_bmi_attribute_correctly(): void
    {
        $assessment = Assessment::factory()->create([
            'weight' => 70,
            'height' => 175,
            'bmi' => 22.86,
        ]);

        $this->assertEquals(22.86, $assessment->bmi);
    }

    #[Test]
    public function it_returns_null_blood_pressure_status_when_bp_missing(): void
    {
        $assessment = Assessment::factory()->create([
            'systolic_bp' => null,
            'diastolic_bp' => null,
        ]);

        $this->assertNull($assessment->blood_pressure_status);
    }

    #[Test]
    public function it_returns_normal_blood_pressure_status(): void
    {
        $assessment = Assessment::factory()->withNormalBP()->create();

        $this->assertEquals('normal', $assessment->blood_pressure_status);
    }

    #[Test]
    public function it_returns_elevated_blood_pressure_status(): void
    {
        $assessment = Assessment::factory()->withElevatedBP()->create();

        $this->assertEquals('elevated', $assessment->blood_pressure_status);
    }

    #[Test]
    public function it_returns_stage1_blood_pressure_status(): void
    {
        $assessment = Assessment::factory()->withStage1Hypertension()->create();

        $this->assertEquals('stage1', $assessment->blood_pressure_status);
    }

    #[Test]
    public function it_returns_stage2_blood_pressure_status(): void
    {
        $assessment = Assessment::factory()->withStage2Hypertension()->create();

        $this->assertEquals('stage2', $assessment->blood_pressure_status);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $assessment = Assessment::factory()->create();

        $this->assertDatabaseHas('assessments', ['id' => $assessment->id]);

        $assessment->delete();

        $this->assertSoftDeleted('assessments', ['id' => $assessment->id]);
    }

    #[Test]
    public function it_can_create_initial_assessment(): void
    {
        $assessment = Assessment::factory()->initial()->create();

        $this->assertEquals('primer', $assessment->diagnosis_type);
    }

    #[Test]
    public function it_can_create_follow_up_assessment(): void
    {
        $assessment = Assessment::factory()->followUp()->create();

        $this->assertEquals('sekunder', $assessment->diagnosis_type);
    }

    #[Test]
    public function it_can_create_with_custom_vital_signs(): void
    {
        $assessment = Assessment::factory()->withVitalSigns([
            'pulse_rate' => 80,
            'body_temperature' => 37.5,
        ])->create();

        $this->assertEquals(80, $assessment->pulse_rate);
        $this->assertEquals(37.5, $assessment->body_temperature);
    }
}
