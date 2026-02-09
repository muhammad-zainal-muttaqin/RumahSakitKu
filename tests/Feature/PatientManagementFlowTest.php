<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $registrationUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        // Create users
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->registrationUser = User::factory()->create(['is_active' => true]);
        $this->registrationUser->assignRole('registration');
    }

    /**
     * Test registration staff can create new patient.
     */
    public function test_registration_staff_can_create_new_patient(): void
    {
        $patientData = [
            'name' => 'Budi Santoso',
            'nik' => '3175091234567890',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'blood_type' => 'O',
            'address' => 'Jl. Sudirman No. 123, Jakarta',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'emergency_contact_name' => 'Ani Santoso',
            'emergency_contact_phone' => '081234567891',
            'marital_status' => 'married',
            'occupation' => 'Karyawan',
            'insurance_type' => 'bpjs',
            'insurance_number' => '0001234567890',
            'bpjs_card_number' => '0001234567890',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'name' => 'Budi Santoso',
            'nik' => '3175091234567890',
            'gender' => 'male',
        ]);
    }

    /**
     * Test patient creation generates medical record number.
     */
    public function test_patient_creation_generates_medical_record_number(): void
    {
        $patientData = [
            'name' => 'Siti Aminah',
            'nik' => '3175091234567891',
            'birth_place' => 'Bandung',
            'birth_date' => '1985-08-20',
            'gender' => 'female',
            'blood_type' => 'A',
            'address' => 'Jl. Merdeka No. 45',
            'phone' => '081234567892',
            'marital_status' => 'single',
            'insurance_type' => 'umum',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertRedirect();

        $patient = Patient::where('nik', '3175091234567891')->first();
        $this->assertNotNull($patient);
        $this->assertNotNull($patient->medical_record_number);
        $this->assertMatchesRegularExpression('/^\d{6}-\d{2}$/', $patient->medical_record_number);
    }

    /**
     * Test patient creation requires name.
     */
    public function test_patient_creation_requires_name(): void
    {
        $patientData = [
            'name' => '',
            'nik' => '3175091234567892',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'address' => 'Jl. Test',
            'phone' => '081234567893',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test patient creation requires valid NIK format.
     */
    public function test_patient_creation_requires_valid_nik_format(): void
    {
        $patientData = [
            'name' => 'Test Patient',
            'nik' => '12345', // Invalid NIK (too short)
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'address' => 'Jl. Test',
            'phone' => '081234567894',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('nik');
    }

    /**
     * Test patient creation requires unique NIK.
     */
    public function test_patient_creation_requires_unique_nik(): void
    {
        Patient::factory()->create(['nik' => '3175091234567895']);

        $patientData = [
            'name' => 'Test Patient',
            'nik' => '3175091234567895', // Duplicate NIK
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'address' => 'Jl. Test',
            'phone' => '081234567895',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('nik');
    }

    /**
     * Test patient creation requires valid birth date.
     */
    public function test_patient_creation_requires_valid_birth_date(): void
    {
        $patientData = [
            'name' => 'Test Patient',
            'nik' => '3175091234567896',
            'birth_date' => 'invalid-date',
            'gender' => 'male',
            'address' => 'Jl. Test',
            'phone' => '081234567896',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('birth_date');
    }

    /**
     * Test patient creation requires valid gender.
     */
    public function test_patient_creation_requires_valid_gender(): void
    {
        $patientData = [
            'name' => 'Test Patient',
            'nik' => '3175091234567897',
            'birth_date' => '1990-05-15',
            'gender' => 'invalid',
            'address' => 'Jl. Test',
            'phone' => '081234567897',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('gender');
    }

    /**
     * Test patient creation requires phone number.
     */
    public function test_patient_creation_requires_phone(): void
    {
        $patientData = [
            'name' => 'Test Patient',
            'nik' => '3175091234567898',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'address' => 'Jl. Test',
            'phone' => '',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/patients', $patientData);

        $response->assertSessionHasErrors('phone');
    }

    /**
     * Test search patient by NIK.
     */
    public function test_can_search_patient_by_nik(): void
    {
        $patient = Patient::factory()->create([
            'nik' => '3175091234567899',
            'name' => 'Ahmad Fauzi',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=3175091234567899');

        $response->assertStatus(200);
        $response->assertSee('Ahmad Fauzi');
    }

    /**
     * Test search patient by medical record number.
     */
    public function test_can_search_patient_by_medical_record_number(): void
    {
        $patient = Patient::factory()->create([
            'medical_record_number' => '240101-01',
            'name' => 'Dewi Kusuma',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=240101-01');

        $response->assertStatus(200);
        $response->assertSee('Dewi Kusuma');
    }

    /**
     * Test search patient by name.
     */
    public function test_can_search_patient_by_name(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Rina Wulandari',
            'nik' => '3175091234567800',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=Rina');

        $response->assertStatus(200);
        $response->assertSee('Rina Wulandari');
    }

    /**
     * Test search patient with partial name.
     */
    public function test_can_search_patient_with_partial_name(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Muhammad Rizky Pratama',
            'nik' => '3175091234567801',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=Rizky');

        $response->assertStatus(200);
        $response->assertSee('Muhammad Rizky Pratama');
    }

    /**
     * Test search patient by BPJS card number.
     */
    public function test_can_search_patient_by_bpjs_card_number(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Sri Wahyuni',
            'bpjs_card_number' => '0009876543210',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=0009876543210');

        $response->assertStatus(200);
        $response->assertSee('Sri Wahyuni');
    }

    /**
     * Test search returns empty when no matches found.
     */
    public function test_search_returns_empty_when_no_matches(): void
    {
        Patient::factory()->create(['name' => 'Existing Patient']);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients?search=NonExistentPatient123');

        $response->assertStatus(200);
        $response->assertDontSee('Existing Patient');
    }

    /**
     * Test update patient data.
     */
    public function test_can_update_patient_data(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Old Name',
            'address' => 'Old Address',
            'phone' => '081234567890',
        ]);

        $updateData = [
            'name' => 'New Name',
            'address' => 'New Address, Jakarta',
            'phone' => '089876543210',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->put("/admin/patients/{$patient->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'name' => 'New Name',
            'address' => 'New Address, Jakarta',
            'phone' => '089876543210',
        ]);
    }

    /**
     * Test update patient with validation errors.
     */
    public function test_update_patient_validates_required_fields(): void
    {
        $patient = Patient::factory()->create(['name' => 'Test Patient']);

        $response = $this->actingAs($this->registrationUser)
            ->put("/admin/patients/{$patient->id}", [
                'name' => '',
                'phone' => '',
            ]);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    /**
     * Test cannot update patient with duplicate NIK.
     */
    public function test_cannot_update_patient_with_duplicate_nik(): void
    {
        $patient1 = Patient::factory()->create(['nik' => '3175091234567802']);
        $patient2 = Patient::factory()->create(['nik' => '3175091234567803']);

        $response = $this->actingAs($this->registrationUser)
            ->put("/admin/patients/{$patient2->id}", [
                'name' => 'Updated Name',
                'nik' => '3175091234567802', // Duplicate NIK
                'phone' => '081234567890',
            ]);

        $response->assertSessionHasErrors('nik');
    }

    /**
     * Test medical record number format.
     */
    public function test_medical_record_number_follows_format(): void
    {
        $patient = Patient::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^\d{6}-\d{2}$/',
            $patient->medical_record_number
        );
    }

    /**
     * Test medical record number is unique.
     */
    public function test_medical_record_number_is_unique(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $this->assertNotEquals(
            $patient1->medical_record_number,
            $patient2->medical_record_number
        );
    }

    /**
     * Test medical record number contains date component.
     */
    public function test_medical_record_number_contains_date_component(): void
    {
        $patient = Patient::factory()->create([
            'registered_at' => now(),
        ]);

        $expectedDatePrefix = now()->format('ymd');
        $this->assertStringStartsWith(
            $expectedDatePrefix,
            $patient->medical_record_number
        );
    }

    /**
     * Test view patient details.
     */
    public function test_can_view_patient_details(): void
    {
        $patient = Patient::factory()->create([
            'name' => 'Detail Test Patient',
            'nik' => '3175091234567804',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get("/admin/patients/{$patient->id}");

        $response->assertStatus(200);
        $response->assertSee('Detail Test Patient');
        $response->assertSee('3175091234567804');
    }

    /**
     * Test soft delete patient.
     */
    public function test_can_soft_delete_patient(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/patients/{$patient->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    /**
     * Test non-admin cannot delete patient.
     */
    public function test_non_admin_cannot_delete_patient(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->registrationUser)
            ->delete("/admin/patients/{$patient->id}");

        $response->assertStatus(403);
    }

    /**
     * Test patient list pagination.
     */
    public function test_patient_list_is_paginated(): void
    {
        Patient::factory()->count(25)->create();

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/patients');

        $response->assertStatus(200);
    }

    /**
     * Test patient age calculation.
     */
    public function test_patient_age_is_calculated_correctly(): void
    {
        $patient = Patient::factory()->create([
            'birth_date' => now()->subYears(25)->subMonths(3),
        ]);

        $this->assertEquals(25, $patient->age);
    }

    /**
     * Test patient with visits can be retrieved.
     */
    public function test_patient_with_visits_can_be_retrieved(): void
    {
        $patient = Patient::factory()->create();
        $polyclinic = \App\Models\MasterData\Polyclinic::factory()->create();
        $doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
        ]);

        $patient->visits()->create([
            'visit_number' => 'V' . now()->format('Ymd') . '001',
            'polyclinic_id' => $polyclinic->id,
            'doctor_id' => $doctor->id,
            'visit_date' => now(),
            'visit_type' => 'rawat_jalan',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get("/admin/patients/{$patient->id}");

        $response->assertStatus(200);
        $this->assertEquals(1, $patient->fresh()->visits()->count());
    }
}
