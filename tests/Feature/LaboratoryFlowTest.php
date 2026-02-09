<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\LaboratoryResult;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\MasterData\LabTest;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LaboratoryFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private User $labStaffUser;
    private User $validatorUser;
    private Employee $doctor;
    private Employee $labStaff;
    private Employee $validator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'lab_staff', 'guard_name' => 'web']);
        Role::create(['name' => 'lab_validator', 'guard_name' => 'web']);

        // Create polyclinic
        $polyclinic = Polyclinic::factory()->create();

        // Create employees
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $polyclinic->id,
        ]);

        $this->labStaff = Employee::factory()->create([
            'employee_type' => 'tetap',
            'status' => 'aktif',
        ]);

        $this->validator = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialization' => 'Patologi Klinik',
        ]);

        // Create users
        $this->doctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->doctor->id,
        ]);
        $this->doctorUser->assignRole('doctor');

        $this->labStaffUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->labStaff->id,
        ]);
        $this->labStaffUser->assignRole('lab_staff');

        $this->validatorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->validator->id,
        ]);
        $this->validatorUser->assignRole('lab_validator');
    }

    #[Test]
    public function it_can_create_lab_order(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $labTests = LabTest::factory()->count(3)->create();

        $orderData = [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'order_date' => now()->format('Y-m-d H:i:s'),
            'priority' => 'normal',
            'diagnosis_notes' => 'Suspek diabetes mellitus',
            'clinical_notes' => 'Puasa 8 jam',
            'lab_tests' => $labTests->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/laboratory/orders', $orderData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('laboratory_orders', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_auto_generates_order_number(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $labTest = LabTest::factory()->create();

        $this->actingAs($this->doctorUser)
            ->post('/admin/laboratory/orders', [
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctor->id,
                'lab_tests' => [$labTest->id],
            ]);

        $order = LaboratoryOrder::where('visit_id', $visit->id)->first();
        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('LAB', $order->order_number);
    }

    #[Test]
    public function it_can_enter_lab_results(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $labTest = LabTest::factory()->create([
            'test_name' => 'Glukosa Puasa',
            'reference_range' => '70-100',
            'unit' => 'mg/dL',
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'pending',
        ]);

        $resultData = [
            'laboratory_order_id' => $order->id,
            'results' => [
                [
                    'lab_test_id' => $labTest->id,
                    'result_value' => 145.5,
                    'unit' => 'mg/dL',
                    'reference_range' => '70-100',
                    'flag' => 'high',
                    'notes' => 'Hasil di atas normal',
                ],
            ],
        ];

        $response = $this->actingAs($this->labStaffUser)
            ->post("/admin/laboratory/orders/{$order->id}/results", $resultData);

        $response->assertRedirect();

        $this->assertDatabaseHas('laboratory_results', [
            'laboratory_order_id' => $order->id,
            'lab_test_id' => $labTest->id,
            'result_value' => 145.5,
            'flag' => 'high',
        ]);

        $order->refresh();
        $this->assertEquals('in_progress', $order->status);
    }

    #[Test]
    public function it_flags_abnormal_values(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        $labTest = LabTest::factory()->create([
            'test_name' => 'Hemoglobin',
            'reference_range' => '12.0-16.0',
            'unit' => 'g/dL',
        ]);

        // Low value should be flagged
        $this->actingAs($this->labStaffUser)
            ->post("/admin/laboratory/orders/{$order->id}/results", [
                'laboratory_order_id' => $order->id,
                'results' => [
                    [
                        'lab_test_id' => $labTest->id,
                        'result_value' => 9.5,
                        'unit' => 'g/dL',
                        'reference_range' => '12.0-16.0',
                        'flag' => 'low',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('laboratory_results', [
            'laboratory_order_id' => $order->id,
            'flag' => 'low',
        ]);
    }

    #[Test]
    public function it_flags_critical_values(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        $labTest = LabTest::factory()->create([
            'test_name' => 'Kalium',
            'reference_range' => '3.5-5.0',
            'unit' => 'mEq/L',
        ]);

        // Critical high value
        $this->actingAs($this->labStaffUser)
            ->post("/admin/laboratory/orders/{$order->id}/results", [
                'laboratory_order_id' => $order->id,
                'results' => [
                    [
                        'lab_test_id' => $labTest->id,
                        'result_value' => 7.2,
                        'unit' => 'mEq/L',
                        'reference_range' => '3.5-5.0',
                        'flag' => 'critical',
                        'notes' => 'NILAI KRITIS - Segera hubungi dokter',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('laboratory_results', [
            'laboratory_order_id' => $order->id,
            'flag' => 'critical',
        ]);
    }

    #[Test]
    public function it_validates_lab_results_before_validation(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        // Try to validate without entering all results
        $response = $this->actingAs($this->validatorUser)
            ->post("/admin/laboratory/orders/{$order->id}/validate", [
                'action' => 'approve',
            ]);

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function it_can_validate_approved_results(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
        ]);

        $labTest = LabTest::factory()->create();

        LaboratoryResult::factory()->create([
            'laboratory_order_id' => $order->id,
            'lab_test_id' => $labTest->id,
            'result_value' => 120,
        ]);

        $response = $this->actingAs($this->validatorUser)
            ->post("/admin/laboratory/orders/{$order->id}/validate", [
                'action' => 'approve',
                'validator_notes' => 'Hasil valid',
            ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('validated', $order->status);

        $result = LaboratoryResult::where('laboratory_order_id', $order->id)->first();
        $this->assertEquals($this->validator->id, $result->validated_by);
        $this->assertNotNull($result->validated_at);
    }

    #[Test]
    public function it_can_reject_results_for_correction(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->validatorUser)
            ->post("/admin/laboratory/orders/{$order->id}/validate", [
                'action' => 'reject',
                'rejection_reason' => 'Hasil tidak konsisten, perlu diperiksa ulang',
            ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('in_progress', $order->status);
    }

    #[Test]
    public function it_can_print_lab_results(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'validated',
        ]);

        $response = $this->actingAs($this->labStaffUser)
            ->get("/admin/laboratory/orders/{$order->id}/print");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function it_integrates_results_with_emr(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $medicalRecord = MedicalRecord::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'medical_record_id' => $medicalRecord->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'validated',
        ]);

        $labTest = LabTest::factory()->create([
            'test_name' => 'Hemoglobin',
        ]);

        LaboratoryResult::factory()->create([
            'laboratory_order_id' => $order->id,
            'lab_test_id' => $labTest->id,
            'result_value' => 13.5,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/admin/medical-records/{$medicalRecord->id}");

        $response->assertStatus(200);
        $response->assertSee('Hemoglobin');
        $response->assertSee('13.5');
    }

    #[Test]
    public function it_filters_orders_by_status(): void
    {
        $patient = Patient::factory()->create();

        LaboratoryOrder::factory()->count(2)->create([
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        LaboratoryOrder::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'status' => 'validated',
        ]);

        $response = $this->actingAs($this->labStaffUser)
            ->get('/admin/laboratory/orders?status=pending');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_prioritizes_cito_orders(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $normalOrder = LaboratoryOrder::factory()->create([
            'patient_id' => $patient1->id,
            'priority' => 'normal',
            'is_cito' => false,
            'created_at' => now()->subHour(),
        ]);

        $citoOrder = LaboratoryOrder::factory()->create([
            'patient_id' => $patient2->id,
            'priority' => 'cito',
            'is_cito' => true,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->labStaffUser)
            ->get('/admin/laboratory/orders?priority=cito');

        $response->assertStatus(200);
        $this->assertTrue($citoOrder->is_cito);
    }

    #[Test]
    public function it_tracks_result_entry_timestamp(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = LaboratoryOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $labTest = LabTest::factory()->create();

        $this->actingAs($this->labStaffUser)
            ->post("/admin/laboratory/orders/{$order->id}/results", [
                'laboratory_order_id' => $order->id,
                'results' => [
                    [
                        'lab_test_id' => $labTest->id,
                        'result_value' => 100,
                    ],
                ],
            ]);

        $result = LaboratoryResult::where('laboratory_order_id', $order->id)->first();
        $this->assertNotNull($result->created_at);
    }
}
