<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Clinical;

use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\Clinical\PrescriptionItem;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $prescription = new Prescription();

        $expectedFillable = [
            'prescription_number',
            'patient_id',
            'visit_id',
            'medical_record_id',
            'prescription_date',
            'prescription_type',
            'priority',
            'status',
            'clinical_indication',
            'allergies',
            'prescribed_by',
            'verified_by_pharmacist',
            'verified_at',
            'dispensed_at',
            'dispensed_by',
            'notes',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $prescription->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $prescription = new Prescription();
        $casts = $prescription->getCasts();

        $this->assertArrayHasKey('prescription_date', $casts);
        $this->assertArrayHasKey('verified_at', $casts);
        $this->assertArrayHasKey('dispensed_at', $casts);
        $this->assertArrayHasKey('verified_by_pharmacist', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_medical_record(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->medicalRecord();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('medical_record_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_prescribed_by_employee(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->prescribedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('prescribed_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_dispensed_by_employee(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->dispensedBy();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('dispensed_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_items(): void
    {
        $prescription = new Prescription();
        $relation = $prescription->items();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('prescription_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(PrescriptionItem::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_items(): void
    {
        $prescription = Prescription::factory()->create();
        PrescriptionItem::factory()->count(3)->create(['prescription_id' => $prescription->id]);

        $this->assertInstanceOf(Collection::class, $prescription->items);
        $this->assertCount(3, $prescription->items);
        $this->assertTrue($prescription->items->every(fn ($item) => $item instanceof PrescriptionItem));
    }

    #[Test]
    public function it_has_with_status_scope(): void
    {
        $pending = Prescription::factory()->pending()->create();
        Prescription::factory()->completed()->create();

        $results = Prescription::withStatus('pending')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($pending));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        Prescription::factory()->count(2)->pending()->create();
        Prescription::factory()->count(3)->completed()->create();

        $results = Prescription::pending()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($p) => $p->status === 'pending'));
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        Prescription::factory()->count(2)->pending()->create();
        Prescription::factory()->count(3)->completed()->create();

        $results = Prescription::completed()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($p) => $p->status === 'dispensed'));
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        $regular = Prescription::factory()->create(['prescription_type' => 'regular']);
        Prescription::factory()->create(['prescription_type' => 'compound']);

        $results = Prescription::byType('regular')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($regular));
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayPrescription = Prescription::factory()->create(['prescription_date' => $today]);
        Prescription::factory()->create(['prescription_date' => $yesterday]);

        $results = Prescription::onDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($todayPrescription));
    }

    #[Test]
    public function it_has_by_doctor_scope(): void
    {
        $doctor1 = Employee::factory()->doctor()->create();
        $doctor2 = Employee::factory()->doctor()->create();

        $prescription1 = Prescription::factory()->create(['prescribed_by' => $doctor1->id]);
        Prescription::factory()->create(['prescribed_by' => $doctor2->id]);

        $results = Prescription::byDoctor($doctor1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($prescription1));
    }

    #[Test]
    public function it_calculates_total_estimated_cost_attribute(): void
    {
        $prescription = Prescription::factory()->create();
        PrescriptionItem::factory()->create([
            'prescription_id' => $prescription->id,
            'quantity' => 10,
            'unit_price' => 5000,
        ]);
        PrescriptionItem::factory()->create([
            'prescription_id' => $prescription->id,
            'quantity' => 5,
            'unit_price' => 10000,
        ]);

        // (10 * 5000) + (5 * 10000) = 50000 + 50000 = 100000
        $this->assertEquals(100000, $prescription->total_estimated_cost);
    }

    #[Test]
    public function it_returns_zero_total_cost_when_no_items(): void
    {
        $prescription = Prescription::factory()->create();

        $this->assertEquals(0, $prescription->total_estimated_cost);
    }

    #[Test]
    public function it_calculates_total_items_attribute(): void
    {
        $prescription = Prescription::factory()->create();
        PrescriptionItem::factory()->count(5)->create(['prescription_id' => $prescription->id]);

        $this->assertEquals(5, $prescription->total_items);
    }

    #[Test]
    public function it_returns_is_ready_for_dispensing_when_pending_and_verified(): void
    {
        $prescription = Prescription::factory()->readyForDispensing()->create();

        $this->assertTrue($prescription->is_ready_for_dispensing);
    }

    #[Test]
    public function it_returns_not_ready_for_dispensing_when_not_verified(): void
    {
        $prescription = Prescription::factory()->pending()->create();

        $this->assertFalse($prescription->is_ready_for_dispensing);
    }

    #[Test]
    public function it_returns_not_ready_for_dispensing_when_already_dispensed(): void
    {
        $prescription = Prescription::factory()->completed()->create();

        $this->assertFalse($prescription->is_ready_for_dispensing);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $prescription = Prescription::factory()->create();

        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id]);

        $prescription->delete();

        $this->assertSoftDeleted('prescriptions', ['id' => $prescription->id]);
    }

    #[Test]
    public function it_generates_unique_prescription_numbers(): void
    {
        $prescription1 = Prescription::factory()->create();
        $prescription2 = Prescription::factory()->create();

        $this->assertNotEquals($prescription1->prescription_number, $prescription2->prescription_number);
        $this->assertStringStartsWith('RX', $prescription1->prescription_number);
        $this->assertStringStartsWith('RX', $prescription2->prescription_number);
    }

    #[Test]
    public function it_can_create_emergency_prescription(): void
    {
        $prescription = Prescription::factory()->emergency()->create();

        $this->assertEquals('emergency', $prescription->prescription_type);
        $this->assertEquals('urgent', $prescription->priority);
    }

    #[Test]
    public function it_can_create_verified_prescription(): void
    {
        $prescription = Prescription::factory()->verified()->create();

        $this->assertTrue($prescription->verified_by_pharmacist);
        $this->assertNotNull($prescription->verified_at);
    }

    #[Test]
    public function it_can_create_completed_prescription(): void
    {
        $prescription = Prescription::factory()->completed()->create();

        $this->assertEquals('dispensed', $prescription->status);
        $this->assertNotNull($prescription->dispensed_at);
        $this->assertNotNull($prescription->dispensed_by);
    }
}
