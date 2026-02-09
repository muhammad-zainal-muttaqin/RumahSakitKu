<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Patient;

use App\Models\Clinical\MedicalRecord;
use App\Models\Financial\Invoice;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $visit = new Visit();

        $expectedFillable = [
            'visit_number',
            'patient_id',
            'polyclinic_id',
            'doctor_id',
            'registration_date',
            'visit_type',
            'visit_status',
            'payment_type',
            'priority',
            'bpjs_sep_number',
            'registered_by',
            'queue_number',
            'notes',
            'completed_at',
            'examination_at',
            'arrived_at',
            'triage_at',
            'assessment_at',
            'prescription_at',
            'payment_at',
            'admission_date',
            'discharge_date',
            'room_id',
            'bed_id',
            'insurance_name',
            'insurance_number',
            'bpjs_rujukan_number',
            'bpjs_rujukan_date',
        ];

        $this->assertEquals($expectedFillable, $visit->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $visit = new Visit();
        $casts = $visit->getCasts();

        $this->assertArrayHasKey('registration_date', $casts);
        $this->assertArrayHasKey('completed_at', $casts);
        $this->assertArrayHasKey('examination_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $visit = new Visit();
        $relation = $visit->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_polyclinic(): void
    {
        $visit = new Visit();
        $relation = $visit->polyclinic();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('polyclinic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Polyclinic::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_doctor(): void
    {
        $visit = new Visit();
        $relation = $visit->doctor();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('doctor_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_one_medical_record(): void
    {
        $visit = new Visit();
        $relation = $visit->medicalRecord();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_medical_record(): void
    {
        $visit = Visit::factory()->create();
        MedicalRecord::factory()->create(['visit_id' => $visit->id]);

        $this->assertInstanceOf(MedicalRecord::class, $visit->medicalRecord);
    }

    #[Test]
    public function it_has_one_invoice(): void
    {
        $visit = new Visit();
        $relation = $visit->invoice();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Invoice::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_invoice(): void
    {
        $visit = Visit::factory()->create();
        Invoice::factory()->create(['visit_id' => $visit->id]);

        $this->assertInstanceOf(Invoice::class, $visit->invoice);
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayVisit = Visit::factory()->create(['registration_date' => $today]);
        Visit::factory()->create(['registration_date' => $yesterday]);

        $results = Visit::onDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($todayVisit));
    }

    #[Test]
    public function it_has_with_status_scope(): void
    {
        $registeredVisit = Visit::factory()->create(['visit_status' => 'pendaftaran']);
        $completedVisit = Visit::factory()->create(['visit_status' => 'selesai']);

        $results = Visit::withStatus('pendaftaran')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($registeredVisit));
        $this->assertFalse($results->contains($completedVisit));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        Visit::factory()->create(['registration_date' => today()]);
        Visit::factory()->create(['registration_date' => now()->subDay()]);

        $results = Visit::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Visit::factory()->create(['visit_status' => 'pendaftaran']);
        Visit::factory()->create(['visit_status' => 'selesai']);

        $results = Visit::active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('pendaftaran', $results->first()->visit_status);
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        $outpatient = Visit::factory()->create(['visit_type' => 'rawat_jalan']);
        $inpatient = Visit::factory()->create(['visit_type' => 'rawat_inap']);

        $results = Visit::byType('rawat_jalan')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($outpatient));
        $this->assertFalse($results->contains($inpatient));
    }

    #[Test]
    public function it_has_by_priority_scope(): void
    {
        $normalVisit = Visit::factory()->create(['priority' => 'normal']);
        $urgentVisit = Visit::factory()->create(['priority' => 'darurat']);

        $results = Visit::byPriority('darurat')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($urgentVisit));
        $this->assertFalse($results->contains($normalVisit));
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $visit = Visit::factory()->create();

        $this->assertDatabaseHas('visits', ['id' => $visit->id]);

        $visit->delete();

        $this->assertSoftDeleted('visits', ['id' => $visit->id]);
    }

    #[Test]
    public function it_generates_unique_visit_numbers(): void
    {
        $visit1 = Visit::factory()->create();
        $visit2 = Visit::factory()->create();

        $this->assertNotEquals($visit1->visit_number, $visit2->visit_number);
        $this->assertStringStartsWith('VIS', $visit1->visit_number);
        $this->assertStringStartsWith('VIS', $visit2->visit_number);
    }

    #[Test]
    public function it_can_create_completed_visit(): void
    {
        $visit = Visit::factory()->completed()->create();

        $this->assertEquals('selesai', $visit->visit_status);
        $this->assertNotNull($visit->completed_at);
    }

    #[Test]
    public function it_can_create_emergency_visit(): void
    {
        $visit = Visit::factory()->emergency()->create();

        $this->assertEquals('igd', $visit->visit_type);
        $this->assertEquals('darurat', $visit->priority);
    }

    #[Test]
    public function it_can_create_inpatient_visit(): void
    {
        $visit = Visit::factory()->inpatient()->create();

        $this->assertEquals('rawat_inap', $visit->visit_type);
    }
}
