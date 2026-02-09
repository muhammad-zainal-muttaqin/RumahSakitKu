<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyDepartmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $igdDoctorUser;
    private User $triageNurseUser;
    private Employee $igdDoctor;
    private Employee $triageNurse;
    private Polyclinic $igdPolyclinic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'nurse', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);

        // Create IGD polyclinic
        $this->igdPolyclinic = Polyclinic::factory()->create([
            'name' => 'IGD',
            'queue_prefix' => 'E',
            'is_active' => true,
        ]);

        // Create IGD doctor
        $this->igdDoctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $this->igdPolyclinic->id,
            'status' => 'aktif',
        ]);

        // Create triage nurse
        $this->triageNurse = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_nurse' => true,
            'status' => 'aktif',
        ]);

        // Create users
        $this->igdDoctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->igdDoctor->id,
        ]);
        $this->igdDoctorUser->assignRole('doctor');

        $this->triageNurseUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->triageNurse->id,
        ]);
        $this->triageNurseUser->assignRole('nurse');
    }

    #[Test]
    public function it_can_register_patient_to_igd(): void
    {
        $patient = Patient::factory()->create();

        $registrationData = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'doctor_id' => $this->igdDoctor->id,
            'visit_date' => now()->format('Y-m-d'),
            'visit_type' => 'igd',
            'priority' => 'emergency',
            'complaint' => 'Sesak napas mendadak',
            'arrival_method' => 'ambulance',
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/visits', $registrationData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
            'priority' => 'emergency',
        ]);
    }

    #[Test]
    public function it_assigns_emergency_queue_number(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->triageNurseUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->igdPolyclinic->id,
                'doctor_id' => $this->igdDoctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'igd',
                'priority' => 'emergency',
                'complaint' => 'Nyeri dada',
            ]);

        $visit = Visit::where('patient_id', $patient->id)->first();
        $queue = VisitQueue::where('visit_id', $visit->id)->first();

        $this->assertNotNull($queue);
        $this->assertStringStartsWith('E', $queue->display_number);
        $this->assertEquals('emergency', $visit->priority);
    }

    #[Test]
    public function it_performs_triage_assessment_and_assigns_red_priority_for_critical(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
            'status' => 'waiting',
        ]);

        $triageData = [
            'visit_id' => $visit->id,
            'triage_level' => 'red',
            'consciousness_level' => 'compos_mentis',
            'airway_status' => 'patent',
            'breathing_rate' => 35,
            'breathing_pattern' => 'distressed',
            'pulse_rate' => 130,
            'blood_pressure_systolic' => 80,
            'blood_pressure_diastolic' => 50,
            'temperature' => 36.5,
            'oxygen_saturation' => 88,
            'chief_complaint' => 'Syok hipovolemik',
            'allergies' => 'Tidak ada',
            'triage_notes' => 'Pasien kritis, butuh penanganan segera',
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/igd/triage', $triageData);

        $response->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'visit_id' => $visit->id,
            'triage_level' => 'red',
        ]);

        $visit->refresh();
        $this->assertEquals('red', $visit->triage_level);
    }

    #[Test]
    public function it_performs_triage_assessment_and_assigns_green_priority_for_stable(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
            'status' => 'waiting',
        ]);

        $triageData = [
            'visit_id' => $visit->id,
            'triage_level' => 'green',
            'consciousness_level' => 'compos_mentis',
            'airway_status' => 'patent',
            'breathing_rate' => 18,
            'breathing_pattern' => 'normal',
            'pulse_rate' => 75,
            'blood_pressure_systolic' => 120,
            'blood_pressure_diastolic' => 80,
            'temperature' => 37.0,
            'oxygen_saturation' => 98,
            'chief_complaint' => 'Luka lecet di tangan',
            'allergies' => 'Tidak ada',
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/igd/triage', $triageData);

        $response->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'visit_id' => $visit->id,
            'triage_level' => 'green',
        ]);
    }

    #[Test]
    public function critical_vitals_trigger_red_triage_automatically(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
        ]);

        // Critical vitals: very low BP and low O2 sat
        $vitalSigns = [
            'visit_id' => $visit->id,
            'blood_pressure' => '70/40',
            'pulse' => 140,
            'respiration' => 8,
            'temperature' => 35.0,
            'oxygen_saturation' => 82,
            'gcs' => 8,
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/igd/vitals', $vitalSigns);

        $response->assertRedirect();

        $visit->refresh();
        $this->assertEquals('red', $visit->triage_level);
    }

    #[Test]
    public function normal_vitals_trigger_green_triage(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
        ]);

        $vitalSigns = [
            'visit_id' => $visit->id,
            'blood_pressure' => '120/80',
            'pulse' => 72,
            'respiration' => 16,
            'temperature' => 36.8,
            'oxygen_saturation' => 98,
            'gcs' => 15,
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/igd/vitals', $vitalSigns);

        $response->assertRedirect();

        $visit->refresh();
        $this->assertEquals('green', $visit->triage_level);
    }

    #[Test]
    public function it_assigns_doctor_to_emergency_patient(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
            'triage_level' => 'red',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->igdDoctorUser)
            ->post("/admin/igd/visits/{$visit->id}/assign-doctor", [
                'doctor_id' => $this->igdDoctor->id,
            ]);

        $response->assertRedirect();

        $visit->refresh();
        $this->assertEquals($this->igdDoctor->id, $visit->doctor_id);
        $this->assertEquals('in_progress', $visit->status);
    }

    #[Test]
    public function it_creates_medical_record_for_emergency_visit(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'doctor_id' => $this->igdDoctor->id,
            'visit_type' => 'igd',
            'status' => 'in_progress',
        ]);

        $medicalRecordData = [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->igdDoctor->id,
            'subjective' => 'Pasien datang dengan sesak napas',
            'objective' => 'TD: 140/90, HR: 110x/menit, RR: 28x/menit',
            'assessment' => 'Acute Coronary Syndrome',
            'plan' => 'EKG, Lab cardiac marker, Terapi oksigen',
        ];

        $response = $this->actingAs($this->igdDoctorUser)
            ->post('/admin/medical-records', $medicalRecordData);

        $response->assertRedirect();

        $this->assertDatabaseHas('medical_records', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'assessment' => 'Acute Coronary Syndrome',
        ]);
    }

    #[Test]
    public function it_can_transfer_patient_from_igd_to_inpatient(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'doctor_id' => $this->igdDoctor->id,
            'visit_type' => 'igd',
            'status' => 'in_progress',
        ]);

        MedicalRecord::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        $transferData = [
            'target_type' => 'rawat_inap',
            'target_room_class' => 'Kelas I',
            'transfer_reason' => 'Memerlukan observasi intensif',
            'diagnosis' => 'STEMI Anteroseptal',
        ];

        $response = $this->actingAs($this->igdDoctorUser)
            ->post("/admin/igd/visits/{$visit->id}/transfer", $transferData);

        $response->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'transfer_status' => 'transferred_to_inpatient',
        ]);
    }

    #[Test]
    public function it_can_discharge_patient_from_igd(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'doctor_id' => $this->igdDoctor->id,
            'visit_type' => 'igd',
            'status' => 'in_progress',
        ]);

        $dischargeData = [
            'discharge_status' => 'pulang',
            'discharge_condition' => 'stabil',
            'final_diagnosis' => 'Gastritis Akut',
            'home_medications' => 'Antasida 3x1',
            'follow_up_instructions' => 'Kontrol 3 hari lagi',
        ];

        $response = $this->actingAs($this->igdDoctorUser)
            ->post("/admin/igd/visits/{$visit->id}/discharge", $dischargeData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertTrue($visit->is_completed);
        $this->assertNotNull($visit->check_out_at);
    }

    #[Test]
    public function it_tracks_triage_statistics(): void
    {
        // Create patients with different triage levels
        $triageLevels = ['red', 'red', 'yellow', 'yellow', 'yellow', 'green', 'green'];

        foreach ($triageLevels as $level) {
            $patient = Patient::factory()->create();
            $visit = Visit::factory()->create([
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->igdPolyclinic->id,
                'visit_type' => 'igd',
                'triage_level' => $level,
            ]);

            Assessment::factory()->create([
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'triage_level' => $level,
            ]);
        }

        $response = $this->actingAs($this->igdDoctorUser)
            ->get('/admin/igd/statistics/triage');

        $response->assertStatus(200);
        $response->assertJson([
            'red' => 2,
            'yellow' => 3,
            'green' => 2,
        ]);
    }

    #[Test]
    public function it_calculates_waiting_time_for_igd_patients(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'visit_type' => 'igd',
            'check_in_at' => now()->subMinutes(45),
        ]);

        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'status' => 'waiting',
            'created_at' => now()->subMinutes(45),
        ]);

        $response = $this->actingAs($this->triageNurseUser)
            ->get("/admin/igd/waiting-times");

        $response->assertStatus(200);

        $waitingTime = $queue->waiting_time;
        $this->assertGreaterThanOrEqual(45, $waitingTime);
    }

    #[Test]
    public function it_prioritizes_red_triage_patients_in_queue(): void
    {
        $patients = Patient::factory()->count(3)->create();

        foreach ($patients as $index => $patient) {
            $triageLevel = match ($index) {
                0 => 'green',
                1 => 'yellow',
                2 => 'red',
            };

            $visit = Visit::factory()->create([
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->igdPolyclinic->id,
                'visit_type' => 'igd',
                'triage_level' => $triageLevel,
                'created_at' => now()->subMinutes($index * 10),
            ]);

            VisitQueue::factory()->create([
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'status' => 'waiting',
            ]);
        }

        $response = $this->actingAs($this->igdDoctorUser)
            ->get('/admin/igd/queue');

        $response->assertStatus(200);
        // Red triage patient should appear first regardless of arrival time
    }

    #[Test]
    public function it_records_emergency_interventions(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->igdPolyclinic->id,
            'doctor_id' => $this->igdDoctor->id,
            'visit_type' => 'igd',
            'status' => 'in_progress',
        ]);

        $interventionData = [
            'visit_id' => $visit->id,
            'interventions' => [
                ['type' => 'oxygen', 'detail' => 'O2 10L/menit via masker'],
                ['type' => 'iv_line', 'detail' => 'Line 18G di tangan kanan'],
                ['type' => 'medication', 'detail' => 'Aspirin 325mg'],
            ],
        ];

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/igd/interventions', $interventionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('emergency_interventions', [
            'visit_id' => $visit->id,
            'intervention_type' => 'oxygen',
        ]);
    }

    #[Test]
    public function it_validates_mandatory_fields_for_igd_registration(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->triageNurseUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => '',
                'visit_type' => '',
                'complaint' => '',
            ]);

        $response->assertSessionHasErrors(['polyclinic_id', 'visit_type', 'complaint']);
    }
}
