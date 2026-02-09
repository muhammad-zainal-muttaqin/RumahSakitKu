<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\Surgery;
use App\Models\Clinical\SurgeryImplant;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SurgeryFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $surgeonUser;
    private User $anesthesiologistUser;
    private User $nurseUser;
    private Employee $surgeon;
    private Employee $assistantSurgeon;
    private Employee $anesthesiologist;
    private Employee $scrubNurse;
    private Employee $circulatingNurse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'surgeon', 'guard_name' => 'web']);
        Role::create(['name' => 'anesthesiologist', 'guard_name' => 'web']);
        Role::create(['name' => 'nurse', 'guard_name' => 'web']);

        // Create polyclinic
        $polyclinic = Polyclinic::factory()->create();

        // Create employees
        $this->surgeon = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialization' => 'Bedah Umum',
            'specialist_polyclinic_id' => $polyclinic->id,
        ]);

        $this->assistantSurgeon = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialization' => 'Bedah Umum',
        ]);

        $this->anesthesiologist = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialization' => 'Anestesi',
        ]);

        $this->scrubNurse = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_nurse' => true,
        ]);

        $this->circulatingNurse = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_nurse' => true,
        ]);

        // Create users
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->surgeonUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->surgeon->id,
        ]);
        $this->surgeonUser->assignRole('surgeon');

        $this->anesthesiologistUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->anesthesiologist->id,
        ]);
        $this->anesthesiologistUser->assignRole('anesthesiologist');

        $this->nurseUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->scrubNurse->id,
        ]);
        $this->nurseUser->assignRole('nurse');
    }

    #[Test]
    public function it_can_schedule_elective_surgery(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $scheduleData = [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'scheduled_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => now()->addDays(3)->setTime(8, 0),
            'estimated_end_time' => now()->addDays(3)->setTime(10, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Apendiktomi',
            'procedure_code' => '47.0',
            'surgery_type' => 'elektif',
            'pre_diagnosis' => 'Appendisitis akut',
            'anesthesia_type' => 'umum',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/surgeries', $scheduleData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('surgeries', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ]);
    }

    #[Test]
    public function it_prevents_double_booking_same_operating_room(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $scheduledDate = now()->addDays(3);

        Surgery::factory()->create([
            'patient_id' => $patient1->id,
            'operating_room' => 'OK1',
            'scheduled_date' => $scheduledDate,
            'start_time' => $scheduledDate->copy()->setTime(8, 0),
            'estimated_end_time' => $scheduledDate->copy()->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/surgeries', [
                'patient_id' => $patient2->id,
                'surgeon_id' => $this->surgeon->id,
                'scheduled_date' => $scheduledDate->format('Y-m-d'),
                'start_time' => $scheduledDate->copy()->setTime(9, 0),
                'estimated_end_time' => $scheduledDate->copy()->setTime(11, 0),
                'operating_room' => 'OK1',
                'procedure_name' => 'Herniorafi',
                'surgery_type' => 'elektif',
            ]);

        $response->assertSessionHasErrors('operating_room');
    }

    #[Test]
    public function it_completes_safety_checklist_sign_in(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'scheduled',
        ]);

        $signInData = [
            'patient_identity_verified' => true,
            'site_marked' => true,
            'anesthesia_safety_check' => true,
            'allergy_checked' => true,
            'airway_risk_assessed' => true,
            'blood_loss_risk_assessed' => true,
        ];

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/surgeries/{$surgery->id}/safety-checklist/sign-in", $signInData);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_sign_in);
        $this->assertNotNull($surgery->safety_checklist_sign_in_at);
        $this->assertEquals('preparation', $surgery->status);
    }

    #[Test]
    public function it_marks_surgery_as_started(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'preparation',
            'safety_checklist_sign_in' => true,
        ]);

        $response = $this->actingAs($this->surgeonUser)
            ->post("/admin/surgeries/{$surgery->id}/start", [
                'actual_start' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertEquals('in_progress', $surgery->status);
        $this->assertNotNull($surgery->actual_start);
    }

    #[Test]
    public function it_completes_safety_checklist_time_out(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'in_progress',
            'actual_start' => now(),
        ]);

        $timeOutData = [
            'team_introduction' => true,
            'procedure_verified' => true,
            'site_verified' => true,
            'antibiotic_prophylaxis_given' => true,
            'antibiotic_time' => now()->format('H:i'),
            'essential_imaging_displayed' => true,
        ];

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/surgeries/{$surgery->id}/safety-checklist/time-out", $timeOutData);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_time_out);
        $this->assertNotNull($surgery->safety_checklist_time_out_at);
    }

    #[Test]
    public function it_completes_safety_checklist_sign_out(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'in_progress',
            'safety_checklist_time_out' => true,
        ]);

        $signOutData = [
            'procedure_completed' => true,
            'specimens_labeled' => true,
            'equipment_count_complete' => true,
            'equipment_issues_addressed' => true,
            'post_op_concerns_reviewed' => true,
        ];

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/surgeries/{$surgery->id}/safety-checklist/sign-out", $signOutData);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_sign_out);
        $this->assertNotNull($surgery->safety_checklist_sign_out_at);
    }

    #[Test]
    public function it_completes_surgery_and_generates_report(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'assistant_surgeon_id' => $this->assistantSurgeon->id,
            'anesthesiologist_id' => $this->anesthesiologist->id,
            'nurse_id' => $this->scrubNurse->id,
            'circulating_nurse_id' => $this->circulatingNurse->id,
            'status' => 'in_progress',
            'safety_checklist_sign_out' => true,
            'actual_start' => now()->subHours(2),
        ]);

        $completionData = [
            'actual_end' => now()->format('Y-m-d H:i:s'),
            'post_diagnosis' => 'Appendisitis supurativa',
            'procedure_notes' => 'Apendiktomi terbuka dengan insisi McBurney',
            'findings' => 'Appendiks inflamasi dengan pus',
            'complications' => 'Tidak ada',
            'specimens' => 'Jaringan appendiks untuk pemeriksaan histopatologi',
        ];

        $response = $this->actingAs($this->surgeonUser)
            ->post("/admin/surgeries/{$surgery->id}/complete", $completionData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $surgery->refresh();
        $this->assertEquals('completed', $surgery->status);
        $this->assertNotNull($surgery->actual_end);
        $this->assertNotNull($surgery->duration);
    }

    #[Test]
    public function it_records_implants_used_in_surgery(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'in_progress',
        ]);

        $implantsData = [
            'implants' => [
                [
                    'implant_name' => 'Titanium Plate 4.5mm',
                    'implant_code' => 'TP-4501',
                    'manufacturer' => 'Stryker',
                    'batch_number' => 'BATCH123456',
                    'quantity' => 2,
                    'notes' => 'Untuk fiksasi fraktur',
                ],
                [
                    'implant_name' => 'Screw 3.5mm x 40mm',
                    'implant_code' => 'SC-3540',
                    'manufacturer' => 'Stryker',
                    'batch_number' => 'BATCH789012',
                    'quantity' => 8,
                ],
            ],
        ];

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/surgeries/{$surgery->id}/implants", $implantsData);

        $response->assertRedirect();

        $this->assertDatabaseHas('surgery_implants', [
            'surgery_id' => $surgery->id,
            'implant_name' => 'Titanium Plate 4.5mm',
            'batch_number' => 'BATCH123456',
        ]);

        $this->assertDatabaseHas('surgery_implants', [
            'surgery_id' => $surgery->id,
            'implant_name' => 'Screw 3.5mm x 40mm',
        ]);
    }

    #[Test]
    public function it_can_cancel_surgery(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/surgeries/{$surgery->id}/cancel", [
                'cancellation_reason' => 'Pasien membatalkan operasi',
            ]);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertEquals('cancelled', $surgery->status);
        $this->assertNotNull($surgery->cancelled_at);
        $this->assertNotNull($surgery->cancelled_by);
    }

    #[Test]
    public function it_can_postpone_surgery(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/surgeries/{$surgery->id}/postpone", [
                'postponed_reason' => 'Hasil lab belum lengkap',
                'new_scheduled_date' => now()->addDays(5)->format('Y-m-d'),
            ]);

        $response->assertRedirect();

        $surgery->refresh();
        $this->assertTrue($surgery->is_postponed);
        $this->assertNotNull($surgery->postponed_at);
        $this->assertNotNull($surgery->postponed_reason);
    }

    #[Test]
    public function it_prioritizes_cito_emergency_surgery(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $scheduledDate = now()->addDays(2);

        $electiveSurgery = Surgery::factory()->create([
            'patient_id' => $patient1->id,
            'surgeon_id' => $this->surgeon->id,
            'surgery_type' => 'elektif',
            'scheduled_date' => $scheduledDate,
            'start_time' => $scheduledDate->copy()->setTime(8, 0),
            'status' => 'scheduled',
        ]);

        $citoSurgery = Surgery::factory()->create([
            'patient_id' => $patient2->id,
            'surgeon_id' => $this->surgeon->id,
            'surgery_type' => 'cito',
            'scheduled_date' => $scheduledDate,
            'start_time' => $scheduledDate->copy()->setTime(10, 0),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/surgeries?type=cito');

        $response->assertStatus(200);
        $this->assertEquals('cito', $citoSurgery->surgery_type);
        $this->assertEquals('Elektif', $electiveSurgery->surgery_type_label);
    }

    #[Test]
    public function it_requires_all_safety_checklists_for_completion(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'in_progress',
            'safety_checklist_sign_in' => false,
            'safety_checklist_time_out' => false,
            'safety_checklist_sign_out' => false,
        ]);

        $response = $this->actingAs($this->surgeonUser)
            ->post("/admin/surgeries/{$surgery->id}/complete", [
                'actual_end' => now()->format('Y-m-d H:i:s'),
            ]);

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function it_calculates_safety_checklist_progress(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'safety_checklist_sign_in' => true,
            'safety_checklist_time_out' => true,
            'safety_checklist_sign_out' => false,
        ]);

        $this->assertEquals(67, $surgery->safety_checklist_progress);
        $this->assertFalse($surgery->is_safety_checklist_complete);
    }

    #[Test]
    public function it_validates_mandatory_fields_for_surgery_scheduling(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/surgeries', [
                'patient_id' => $patient->id,
                'surgeon_id' => '',
                'scheduled_date' => '',
                'operating_room' => '',
                'procedure_name' => '',
            ]);

        $response->assertSessionHasErrors(['surgeon_id', 'scheduled_date', 'operating_room', 'procedure_name']);
    }

    #[Test]
    public function it_can_view_surgery_schedule(): void
    {
        Surgery::factory()->count(5)->create([
            'surgeon_id' => $this->surgeon->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/surgeries/schedule');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_tracks_surgery_duration(): void
    {
        $patient = Patient::factory()->create();
        $surgery = Surgery::factory()->create([
            'patient_id' => $patient->id,
            'surgeon_id' => $this->surgeon->id,
            'status' => 'completed',
            'actual_start' => now()->subHours(3),
            'actual_end' => now(),
        ]);

        $duration = $surgery->duration;
        $this->assertEquals(180, $duration);
    }
}
