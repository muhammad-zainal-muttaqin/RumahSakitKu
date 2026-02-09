<?php

declare(strict_types=1);

namespace Tests\Unit\Models\MasterData;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\Prescription;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $employee = new Employee();

        $expectedFillable = [
            'employee_code',
            'nip',
            'name',
            'gender',
            'birth_date',
            'address',
            'phone',
            'email',
            'employee_type',
            'is_doctor',
            'doctor_title',
            'sip_number',
            'sip_expiry_date',
            'str_number',
            'str_expiry_date',
            'specialist_polyclinic_id',
            'is_nurse',
            'sip_nurse_number',
            'profession',
            'certification_number',
            'join_date',
            'resign_date',
            'status',
            'photo_path',
        ];

        $this->assertEquals($expectedFillable, $employee->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $employee = new Employee();
        $casts = $employee->getCasts();

        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertArrayHasKey('sip_expiry_date', $casts);
        $this->assertArrayHasKey('str_expiry_date', $casts);
        $this->assertArrayHasKey('join_date', $casts);
        $this->assertArrayHasKey('resign_date', $casts);
        $this->assertArrayHasKey('is_doctor', $casts);
        $this->assertArrayHasKey('is_nurse', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_specialist_polyclinic(): void
    {
        $employee = new Employee();
        $relation = $employee->specialistPolyclinic();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('specialist_polyclinic_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Polyclinic::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_visits_relationship(): void
    {
        $employee = new Employee();
        $relation = $employee->visits();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('doctor_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_visits(): void
    {
        $employee = Employee::factory()->doctor()->create();
        Visit::factory()->count(3)->create(['doctor_id' => $employee->id]);

        $this->assertInstanceOf(Collection::class, $employee->visits);
        $this->assertCount(3, $employee->visits);
    }

    #[Test]
    public function it_has_assessments_relationship(): void
    {
        $employee = new Employee();
        $relation = $employee->assessments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('assessed_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Assessment::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_prescriptions_relationship(): void
    {
        $employee = new Employee();
        $relation = $employee->prescriptions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('prescribed_by', $relation->getForeignKeyName());
        $this->assertInstanceOf(Prescription::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        Employee::factory()->count(3)->create(['status' => 'aktif']);
        Employee::factory()->count(2)->inactive()->create();

        $results = Employee::active()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($employee) => $employee->status === 'aktif'));
    }

    #[Test]
    public function it_has_by_type_scope(): void
    {
        $permanent = Employee::factory()->permanent()->create();
        Employee::factory()->contract()->create();

        $results = Employee::byType('tetap')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($permanent));
    }

    #[Test]
    public function it_has_doctors_scope(): void
    {
        Employee::factory()->count(2)->doctor()->create();
        Employee::factory()->count(3)->nurse()->create();

        $results = Employee::doctors()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($employee) => $employee->is_doctor === true));
    }

    #[Test]
    public function it_has_nurses_scope(): void
    {
        Employee::factory()->count(2)->doctor()->create();
        Employee::factory()->count(3)->nurse()->create();

        $results = Employee::nurses()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($employee) => $employee->is_nurse === true));
    }

    #[Test]
    public function it_has_pharmacists_scope(): void
    {
        Employee::factory()->count(2)->doctor()->create();
        Employee::factory()->count(3)->pharmacist()->create();

        $results = Employee::pharmacists()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($employee) => str_contains(strtolower($employee->profession), 'farmasi')));
    }

    #[Test]
    public function it_has_in_polyclinic_scope(): void
    {
        $polyclinic1 = Polyclinic::factory()->create();
        $polyclinic2 = Polyclinic::factory()->create();

        $employee1 = Employee::factory()->doctor()->create(['specialist_polyclinic_id' => $polyclinic1->id]);
        Employee::factory()->doctor()->create(['specialist_polyclinic_id' => $polyclinic2->id]);

        $results = Employee::inPolyclinic($polyclinic1->id)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($employee1));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_name(): void
    {
        $employee1 = Employee::factory()->create(['name' => 'John Doe']);
        $employee2 = Employee::factory()->create(['name' => 'Jane Smith']);

        $results = Employee::search('John')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($employee1));
        $this->assertFalse($results->contains($employee2));
    }

    #[Test]
    public function it_has_search_scope_that_searches_by_nip(): void
    {
        $employee = Employee::factory()->create(['nip' => '123456789012345678']);
        Employee::factory()->create(['nip' => '876543210987654321']);

        $results = Employee::search('123456789012345678')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($employee));
    }

    #[Test]
    public function it_has_by_status_scope(): void
    {
        $active = Employee::factory()->create(['status' => 'aktif']);
        Employee::factory()->onLeave()->create();

        $results = Employee::byStatus('aktif')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($active));
    }

    #[Test]
    public function it_calculates_today_visit_count_attribute(): void
    {
        $employee = Employee::factory()->doctor()->create();
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        Visit::factory()->count(3)->create([
            'doctor_id' => $employee->id,
            'registration_date' => $today,
        ]);
        Visit::factory()->count(2)->create([
            'doctor_id' => $employee->id,
            'registration_date' => $yesterday,
        ]);

        $this->assertEquals(3, $employee->today_visit_count);
    }

    #[Test]
    public function it_calculates_total_patients_attribute(): void
    {
        $employee = Employee::factory()->doctor()->create();
        $patient1 = \App\Models\Patient\Patient::factory()->create();
        $patient2 = \App\Models\Patient\Patient::factory()->create();

        Visit::factory()->count(3)->create([
            'doctor_id' => $employee->id,
            'patient_id' => $patient1->id,
        ]);
        Visit::factory()->count(2)->create([
            'doctor_id' => $employee->id,
            'patient_id' => $patient2->id,
        ]);

        $this->assertEquals(2, $employee->total_patients);
    }

    #[Test]
    public function it_returns_sip_status_valid_when_not_expired(): void
    {
        $employee = Employee::factory()->doctor()->withValidLicenses()->create();

        $this->assertEquals('valid', $employee->sip_status);
    }

    #[Test]
    public function it_returns_sip_status_expired_when_past_expiry(): void
    {
        $employee = Employee::factory()->withExpiredSIP()->create();

        $this->assertEquals('expired', $employee->sip_status);
    }

    #[Test]
    public function it_returns_sip_status_expiring_soon_within_30_days(): void
    {
        $employee = Employee::factory()->withExpiringSIP()->create();

        $this->assertEquals('expiring_soon', $employee->sip_status);
    }

    #[Test]
    public function it_returns_sip_status_no_license_when_null(): void
    {
        $employee = Employee::factory()->create([
            'sip_number' => null,
            'sip_expiry_date' => null,
        ]);

        $this->assertEquals('no_license', $employee->sip_status);
    }

    #[Test]
    public function it_returns_str_status_valid_when_not_expired(): void
    {
        $employee = Employee::factory()->doctor()->withValidLicenses()->create();

        $this->assertEquals('valid', $employee->str_status);
    }

    #[Test]
    public function it_returns_str_status_expired_when_past_expiry(): void
    {
        $employee = Employee::factory()->doctor()->create([
            'str_expiry_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->assertEquals('expired', $employee->str_status);
    }

    #[Test]
    public function it_returns_license_expiry_warning_when_sip_expired(): void
    {
        $employee = Employee::factory()->withExpiredSIP()->create();

        $this->assertStringContainsString('SIP telah expired', $employee->license_expiry_warning);
    }

    #[Test]
    public function it_returns_license_expiry_warning_when_sip_expiring_soon(): void
    {
        $employee = Employee::factory()->withExpiringSIP()->create();

        $this->assertStringContainsString('SIP akan expired', $employee->license_expiry_warning);
    }

    #[Test]
    public function it_returns_null_license_expiry_warning_when_valid(): void
    {
        $employee = Employee::factory()->doctor()->withValidLicenses()->create();

        $this->assertNull($employee->license_expiry_warning);
    }

    #[Test]
    public function it_calculates_age_attribute(): void
    {
        $employee = Employee::factory()->create([
            'birth_date' => now()->subYears(30)->subMonths(3)->format('Y-m-d'),
        ]);

        $this->assertEquals(30, $employee->age);
    }

    #[Test]
    public function it_calculates_years_of_service_attribute(): void
    {
        $employee = Employee::factory()->create([
            'join_date' => now()->subYears(5)->subMonths(3)->format('Y-m-d'),
        ]);

        $this->assertEquals(5, $employee->years_of_service);
    }

    #[Test]
    public function it_returns_full_name_with_title_attribute(): void
    {
        $doctor = Employee::factory()->doctor()->create([
            'name' => 'John Doe',
            'doctor_title' => 'dr.',
        ]);
        $nurse = Employee::factory()->nurse()->create([
            'name' => 'Jane Smith',
        ]);

        $this->assertEquals('dr. John Doe', $doctor->full_name_with_title);
        $this->assertEquals('Jane Smith', $nurse->full_name_with_title);
    }

    #[Test]
    public function it_returns_status_color_attribute(): void
    {
        $active = Employee::factory()->create(['status' => 'aktif']);
        $onLeave = Employee::factory()->onLeave()->create();
        $inactive = Employee::factory()->inactive()->create();
        $retired = Employee::factory()->create(['status' => 'pensiun']);

        $this->assertEquals('success', $active->status_color);
        $this->assertEquals('warning', $onLeave->status_color);
        $this->assertEquals('danger', $inactive->status_color);
        $this->assertEquals('gray', $retired->status_color);
    }

    #[Test]
    public function it_returns_employee_type_label_attribute(): void
    {
        $permanent = Employee::factory()->permanent()->create();
        $contract = Employee::factory()->contract()->create();
        $honorer = Employee::factory()->create(['employee_type' => 'honorer']);

        $this->assertEquals('Tetap', $permanent->employee_type_label);
        $this->assertEquals('Kontrak', $contract->employee_type_label);
        $this->assertEquals('Honorer', $honorer->employee_type_label);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $employee = Employee::factory()->create();

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);

        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    #[Test]
    public function it_can_create_doctor(): void
    {
        $doctor = Employee::factory()->doctor()->create();

        $this->assertTrue($doctor->is_doctor);
        $this->assertNotNull($doctor->sip_number);
        $this->assertNotNull($doctor->str_number);
        $this->assertNotNull($doctor->specialist_polyclinic_id);
    }

    #[Test]
    public function it_can_create_nurse(): void
    {
        $nurse = Employee::factory()->nurse()->create();

        $this->assertTrue($nurse->is_nurse);
        $this->assertNotNull($nurse->sip_nurse_number);
    }

    #[Test]
    public function it_can_create_pharmacist(): void
    {
        $pharmacist = Employee::factory()->pharmacist()->create();

        $this->assertStringContainsString('farmasi', strtolower($pharmacist->profession));
    }

    #[Test]
    public function it_can_create_permanent_employee(): void
    {
        $employee = Employee::factory()->permanent()->create();

        $this->assertEquals('tetap', $employee->employee_type);
    }

    #[Test]
    public function it_can_create_contract_employee(): void
    {
        $employee = Employee::factory()->contract()->create();

        $this->assertEquals('kontrak', $employee->employee_type);
    }
}
