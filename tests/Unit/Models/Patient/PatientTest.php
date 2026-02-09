<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Patient;

use App\Models\Clinical\MedicalRecord;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $patient = new Patient();

        $expectedFillable = [
            'medical_record_number',
            'name',
            'nik',
            'birth_place',
            'birth_date',
            'gender',
            'blood_type',
            'address',
            'phone',
            'phone_primary',
            'phone_secondary',
            'email',
            'emergency_name',
            'emergency_contact_name',
            'emergency_phone',
            'emergency_contact_phone',
            'marital_status',
            'occupation',
            'insurance_name',
            'insurance_type',
            'insurance_number',
            'bpjs_number',
            'bpjs_card_number',
            'bpjs_ppk_code',
            'bpjs_class',
            'photo_path',
            'is_active',
            'registered_at',
            'created_by',
            'updated_by',
            'education',
            'nationality',
            'religion',
            'rt',
            'rw',
            'village',
            'district',
            'city',
            'province',
            'postal_code',
            'emergency_relation',
            'emergency_address',
            'insurance_card_path',
            'mother_patient_id',
            'first_visit_at',
            'last_visit_at',
            'total_visits',
            'notes',
        ];

        $this->assertEquals($expectedFillable, $patient->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $patient = new Patient();
        $casts = $patient->getCasts();

        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertArrayHasKey('registered_at', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_has_visits_relationship(): void
    {
        $patient = new Patient();
        $relation = $patient->visits();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_visits(): void
    {
        $patient = Patient::factory()->create();
        Visit::factory()->count(3)->create(['patient_id' => $patient->id]);

        $this->assertInstanceOf(Collection::class, $patient->visits);
        $this->assertCount(3, $patient->visits);
        $this->assertTrue($patient->visits->every(fn ($visit) => $visit instanceof Visit));
    }

    #[Test]
    public function it_has_medical_records_relationship(): void
    {
        $patient = new Patient();
        $relation = $patient->medicalRecords();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(MedicalRecord::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_medical_records(): void
    {
        $patient = Patient::factory()->create();
        MedicalRecord::factory()->count(2)->create(['patient_id' => $patient->id]);

        $this->assertInstanceOf(Collection::class, $patient->medicalRecords);
        $this->assertCount(2, $patient->medicalRecords);
        $this->assertTrue($patient->medicalRecords->every(fn ($record) => $record instanceof MedicalRecord));
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Patient::factory()->count(3)->create(['is_active' => true]);
        Patient::factory()->count(2)->create(['is_active' => false]);

        $activePatients = Patient::active()->get();

        $this->assertCount(3, $activePatients);
        $this->assertTrue($activePatients->every(fn ($patient) => $patient->is_active === true));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_name(): void
    {
        $patient1 = Patient::factory()->create(['name' => 'John Doe']);
        $patient2 = Patient::factory()->create(['name' => 'Jane Smith']);

        $results = Patient::search('John')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($patient1));
        $this->assertFalse($results->contains($patient2));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_medical_record_number(): void
    {
        $patient = Patient::factory()->create(['medical_record_number' => '240101-01']);
        Patient::factory()->create(['medical_record_number' => '240101-02']);

        $results = Patient::search('240101-01')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($patient));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_nik(): void
    {
        $patient = Patient::factory()->create(['nik' => '1234567890123456']);
        Patient::factory()->create(['nik' => '6543210987654321']);

        $results = Patient::search('1234567890123456')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($patient));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_bpjs_number(): void
    {
        $patient = Patient::factory()->create(['bpjs_number' => '0001234567890']);
        Patient::factory()->create(['bpjs_number' => '0000987654321']);

        $results = Patient::search('0001234567890')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($patient));
    }

    #[Test]
    public function it_has_by_insurance_type_scope(): void
    {
        $bpjsPatient = Patient::factory()->create(['insurance_name' => 'BPJS Kesehatan']);
        $privatePatient = Patient::factory()->create(['insurance_name' => 'Allianz']);

        $bpjsResults = Patient::byInsuranceType('BPJS Kesehatan')->get();

        $this->assertCount(1, $bpjsResults);
        $this->assertTrue($bpjsResults->contains($bpjsPatient));
        $this->assertFalse($bpjsResults->contains($privatePatient));
    }

    #[Test]
    public function it_calculates_age_attribute_correctly(): void
    {
        $patient = Patient::factory()->create([
            'birth_date' => now()->subYears(25)->subMonths(3)->format('Y-m-d'),
        ]);

        $this->assertEquals(25, $patient->age);
    }

    #[Test]
    public function it_returns_zero_age_when_birth_date_is_null(): void
    {
        $patient = Patient::factory()->create(['birth_date' => null]);

        $this->assertEquals(0, $patient->age);
    }

    #[Test]
    public function it_has_full_address_attribute(): void
    {
        $patient = Patient::factory()->create(['address' => '123 Main Street, City']);

        $this->assertEquals('123 Main Street, City', $patient->full_address);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $patient = Patient::factory()->create();

        $this->assertDatabaseHas('patients', ['id' => $patient->id]);

        $patient->delete();

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    #[Test]
    public function it_can_be_created_with_factory(): void
    {
        $patient = Patient::factory()->create();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'medical_record_number' => $patient->medical_record_number,
        ]);
    }

    #[Test]
    public function it_generates_unique_medical_record_numbers(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $this->assertNotEquals($patient1->medical_record_number, $patient2->medical_record_number);
        $this->assertMatchesRegularExpression('/^\d{6}-\d{2}$/', $patient1->medical_record_number);
        $this->assertMatchesRegularExpression('/^\d{6}-\d{2}$/', $patient2->medical_record_number);
    }
}
