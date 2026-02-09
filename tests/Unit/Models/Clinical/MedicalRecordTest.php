<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\Cppt;
use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $record = new MedicalRecord();

        $expectedFillable = [
            'record_number',
            'patient_id',
            'visit_id',
            'visit_date',
            'subjective',
            'objective',
            'assessment',
            'plan',
            'diagnosis_primary',
            'diagnosis_secondary',
            'icd10_code',
            'icd10_description',
            'procedure_code',
            'procedure_description',
            'notes',
            'is_finalized',
            'finalized_at',
            'finalized_by',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $record->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $record = new MedicalRecord();
        $casts = $record->getCasts();

        $this->assertArrayHasKey('visit_date', $casts);
        $this->assertArrayHasKey('finalized_at', $casts);
        $this->assertArrayHasKey('is_finalized', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $record = new MedicalRecord();
        $relation = $record->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $record = new MedicalRecord();
        $relation = $record->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_cppts(): void
    {
        $record = new MedicalRecord();
        $relation = $record->cppts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Cppt::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_cppts(): void
    {
        $record = MedicalRecord::factory()->create();
        Cppt::factory()->count(3)->create(['medical_record_id' => $record->id]);

        $this->assertInstanceOf(Collection::class, $record->cppts);
        $this->assertCount(3, $record->cppts);
        $this->assertTrue($record->cppts->every(fn ($cppt) => $cppt instanceof Cppt));
    }

    #[Test]
    public function it_has_many_assessments(): void
    {
        $record = new MedicalRecord();
        $relation = $record->assessments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Assessment::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_assessments(): void
    {
        $record = MedicalRecord::factory()->create();
        Assessment::factory()->count(2)->create(['medical_record_id' => $record->id]);

        $this->assertInstanceOf(Collection::class, $record->assessments);
        $this->assertCount(2, $record->assessments);
    }

    #[Test]
    public function it_has_many_prescriptions(): void
    {
        $record = new MedicalRecord();
        $relation = $record->prescriptions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Prescription::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_prescriptions(): void
    {
        $record = MedicalRecord::factory()->create();
        Prescription::factory()->count(2)->create(['medical_record_id' => $record->id]);

        $this->assertInstanceOf(Collection::class, $record->prescriptions);
        $this->assertCount(2, $record->prescriptions);
    }

    #[Test]
    public function it_has_finalized_scope(): void
    {
        MedicalRecord::factory()->count(2)->finalized()->create();
        MedicalRecord::factory()->count(3)->draft()->create();

        $results = MedicalRecord::finalized()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($record) => $record->is_finalized === true));
    }

    #[Test]
    public function it_has_draft_scope(): void
    {
        MedicalRecord::factory()->count(2)->finalized()->create();
        MedicalRecord::factory()->count(3)->draft()->create();

        $results = MedicalRecord::draft()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($record) => $record->is_finalized === false));
    }

    #[Test]
    public function it_has_search_diagnosis_scope(): void
    {
        $record1 = MedicalRecord::factory()->create(['diagnosis_primary' => 'Hypertension']);
        $record2 = MedicalRecord::factory()->create(['diagnosis_secondary' => 'Diabetes Mellitus']);
        MedicalRecord::factory()->create(['diagnosis_primary' => 'Common Cold']);

        $results = MedicalRecord::searchDiagnosis('Hypertension')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($record1));

        $results2 = MedicalRecord::searchDiagnosis('Diabetes')->get();
        $this->assertCount(1, $results2);
        $this->assertTrue($results2->contains($record2));
    }

    #[Test]
    public function it_has_search_diagnosis_scope_that_searches_by_icd10_code(): void
    {
        $record = MedicalRecord::factory()->create(['icd10_code' => 'I10']);
        MedicalRecord::factory()->create(['icd10_code' => 'E11']);

        $results = MedicalRecord::searchDiagnosis('I10')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($record));
    }

    #[Test]
    public function it_has_by_icd10_scope(): void
    {
        $record = MedicalRecord::factory()->create(['icd10_code' => 'I10']);
        MedicalRecord::factory()->create(['icd10_code' => 'E11']);

        $results = MedicalRecord::byIcd10('I10')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($record));
    }

    #[Test]
    public function it_generates_soap_note_attribute(): void
    {
        $record = MedicalRecord::factory()->create([
            'subjective' => 'Patient complains of headache',
            'objective' => 'BP: 120/80',
            'assessment' => 'Tension headache',
            'plan' => 'Prescribe analgesics',
        ]);

        $soapNote = $record->soap_note;

        $this->assertStringContainsString('S: Patient complains of headache', $soapNote);
        $this->assertStringContainsString('O: BP: 120/80', $soapNote);
        $this->assertStringContainsString('A: Tension headache', $soapNote);
        $this->assertStringContainsString('P: Prescribe analgesics', $soapNote);
    }

    #[Test]
    public function it_excludes_empty_soap_fields(): void
    {
        $record = MedicalRecord::factory()->create([
            'subjective' => 'Patient complains of headache',
            'objective' => null,
            'assessment' => 'Tension headache',
            'plan' => null,
        ]);

        $soapNote = $record->soap_note;

        $this->assertStringContainsString('S:', $soapNote);
        $this->assertStringContainsString('A:', $soapNote);
        $this->assertStringNotContainsString('O:', $soapNote);
        $this->assertStringNotContainsString('P:', $soapNote);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $record = MedicalRecord::factory()->create();

        $this->assertDatabaseHas('medical_records', ['id' => $record->id]);

        $record->delete();

        $this->assertSoftDeleted('medical_records', ['id' => $record->id]);
    }

    #[Test]
    public function it_generates_unique_record_numbers(): void
    {
        $record1 = MedicalRecord::factory()->create();
        $record2 = MedicalRecord::factory()->create();

        $this->assertNotEquals($record1->record_number, $record2->record_number);
        $this->assertStringStartsWith('MR', $record1->record_number);
        $this->assertStringStartsWith('MR', $record2->record_number);
    }

    #[Test]
    public function it_can_create_finalized_record(): void
    {
        $record = MedicalRecord::factory()->finalized()->create();

        $this->assertTrue($record->is_finalized);
        $this->assertNotNull($record->finalized_at);
        $this->assertNotNull($record->finalized_by);
    }

    #[Test]
    public function it_can_create_draft_record(): void
    {
        $record = MedicalRecord::factory()->draft()->create();

        $this->assertFalse($record->is_finalized);
        $this->assertNull($record->finalized_at);
        $this->assertNull($record->finalized_by);
    }

    #[Test]
    public function it_can_create_record_with_diagnosis(): void
    {
        $record = MedicalRecord::factory()
            ->withDiagnosis('Primary Hypertension', 'Type 2 Diabetes')
            ->create();

        $this->assertEquals('Primary Hypertension', $record->diagnosis_primary);
        $this->assertEquals('Type 2 Diabetes', $record->diagnosis_secondary);
    }

    #[Test]
    public function it_can_create_record_with_icd10(): void
    {
        $record = MedicalRecord::factory()
            ->withICD10('I10', 'Essential (primary) hypertension')
            ->create();

        $this->assertEquals('I10', $record->icd10_code);
        $this->assertEquals('Essential (primary) hypertension', $record->icd10_description);
    }
}
