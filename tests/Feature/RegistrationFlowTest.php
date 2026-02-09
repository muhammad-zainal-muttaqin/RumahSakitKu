<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $registrationUser;
    protected User $doctorUser;
    protected Polyclinic $polyclinic;
    protected Employee $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);

        // Create users
        $this->registrationUser = User::factory()->create(['is_active' => true]);
        $this->registrationUser->assignRole('registration');

        $this->doctorUser = User::factory()->create(['is_active' => true]);
        $this->doctorUser->assignRole('doctor');

        // Create polyclinic
        $this->polyclinic = Polyclinic::factory()->create([
            'name' => 'Poli Umum',
            'queue_prefix' => 'A',
            'is_active' => true,
        ]);

        // Create doctor
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $this->polyclinic->id,
            'status' => 'aktif',
        ]);
    }

    /**
     * Test registration staff can register patient to polyclinic.
     */
    public function test_registration_staff_can_register_patient_to_polyclinic(): void
    {
        $patient = Patient::factory()->create();

        $registrationData = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now()->format('Y-m-d'),
            'visit_type' => 'rawat_jalan',
            'registration_type' => 'baru',
            'priority' => 'normal',
            'complaint' => 'Sakit kepala dan demam',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', $registrationData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'rawat_jalan',
            'status' => 'waiting',
        ]);
    }

    /**
     * Test registration generates queue number.
     */
    public function test_registration_generates_queue_number(): void
    {
        $patient = Patient::factory()->create();

        $registrationData = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now()->format('Y-m-d'),
            'visit_type' => 'rawat_jalan',
            'registration_type' => 'baru',
            'priority' => 'normal',
            'complaint' => 'Sakit perut',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', $registrationData);

        $response->assertRedirect();

        $visit = Visit::where('patient_id', $patient->id)->first();
        $this->assertNotNull($visit);

        $queue = VisitQueue::where('visit_id', $visit->id)->first();
        $this->assertNotNull($queue);
        $this->assertNotNull($queue->queue_number);
        $this->assertNotNull($queue->display_number);
    }

    /**
     * Test queue number follows polyclinic prefix format.
     */
    public function test_queue_number_follows_polyclinic_prefix_format(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'registration_type' => 'baru',
                'priority' => 'normal',
                'complaint' => 'Sakit gigi',
            ]);

        $queue = VisitQueue::first();
        $this->assertStringStartsWith('A', $queue->display_number);
    }

    /**
     * Test queue numbers increment sequentially.
     */
    public function test_queue_numbers_increment_sequentially(): void
    {
        $patients = Patient::factory()->count(3)->create();

        foreach ($patients as $index => $patient) {
            $this->actingAs($this->registrationUser)
                ->post('/admin/visits', [
                    'patient_id' => $patient->id,
                    'polyclinic_id' => $this->polyclinic->id,
                    'doctor_id' => $this->doctor->id,
                    'visit_date' => now()->format('Y-m-d'),
                    'visit_type' => 'rawat_jalan',
                    'registration_type' => 'baru',
                    'priority' => 'normal',
                    'complaint' => 'Keluhan ' . ($index + 1),
                ]);
        }

        $queues = VisitQueue::orderBy('queue_number')->get();
        $this->assertEquals(1, $queues[0]->queue_number);
        $this->assertEquals(2, $queues[1]->queue_number);
        $this->assertEquals(3, $queues[2]->queue_number);
    }

    /**
     * Test registration requires patient_id.
     */
    public function test_registration_requires_patient_id(): void
    {
        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => '',
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'complaint' => 'Sakit',
            ]);

        $response->assertSessionHasErrors('patient_id');
    }

    /**
     * Test registration requires polyclinic_id.
     */
    public function test_registration_requires_polyclinic_id(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => '',
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'complaint' => 'Sakit',
            ]);

        $response->assertSessionHasErrors('polyclinic_id');
    }

    /**
     * Test registration requires valid visit type.
     */
    public function test_registration_requires_valid_visit_type(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'invalid_type',
                'complaint' => 'Sakit',
            ]);

        $response->assertSessionHasErrors('visit_type');
    }

    /**
     * Test registration requires complaint.
     */
    public function test_registration_requires_complaint(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'complaint' => '',
            ]);

        $response->assertSessionHasErrors('complaint');
    }

    /**
     * Test emergency priority generates urgent queue.
     */
    public function test_emergency_priority_generates_urgent_queue(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient->id,
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'registration_type' => 'baru',
                'priority' => 'emergency',
                'complaint' => 'Sesak napas berat',
            ]);

        $visit = Visit::where('patient_id', $patient->id)->first();
        $this->assertEquals('emergency', $visit->priority);
    }

    /**
     * Test can view queue display.
     */
    public function test_can_view_queue_display(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);
        VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'queue_number' => 1,
            'display_number' => 'A001',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/queues/display');

        $response->assertStatus(200);
        $response->assertSee('A001');
    }

    /**
     * Test can view queue by polyclinic.
     */
    public function test_can_view_queue_by_polyclinic(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
        ]);
        VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'queue_number' => 1,
            'display_number' => 'A001',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get("/admin/queues/polyclinic/{$this->polyclinic->id}");

        $response->assertStatus(200);
    }

    /**
     * Test doctor can call next queue.
     */
    public function test_doctor_can_call_next_queue(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'waiting',
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'queue_number' => 1,
            'display_number' => 'A001',
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/call", [
                'counter_number' => '1',
            ]);

        $response->assertRedirect();

        $queue->refresh();
        $this->assertEquals('called', $queue->status);
        $this->assertNotNull($queue->called_at);
        $this->assertEquals('1', $queue->counter_number);
    }

    /**
     * Test queue status changes to in_progress when called.
     */
    public function test_queue_status_changes_to_in_progress(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'called',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/start");

        $response->assertRedirect();

        $queue->refresh();
        $this->assertEquals('in_progress', $queue->status);
    }

    /**
     * Test queue can be marked as completed.
     */
    public function test_queue_can_be_marked_as_completed(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/complete");

        $response->assertRedirect();

        $queue->refresh();
        $visit->refresh();
        $this->assertEquals('completed', $queue->status);
        $this->assertNotNull($queue->completed_at);
        $this->assertTrue($visit->is_completed);
    }

    /**
     * Test visit is marked complete when queue completes.
     */
    public function test_visit_is_marked_complete_when_queue_completes(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'is_completed' => false,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/complete");

        $visit->refresh();
        $this->assertTrue($visit->is_completed);
        $this->assertNotNull($visit->check_out_at);
    }

    /**
     * Test queue can be skipped.
     */
    public function test_queue_can_be_skipped(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/skip");

        $response->assertRedirect();

        $queue->refresh();
        $this->assertEquals('skipped', $queue->status);
    }

    /**
     * Test skipped queue can be recalled.
     */
    public function test_skipped_queue_can_be_recalled(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'skipped',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/queues/{$queue->id}/call");

        $response->assertRedirect();

        $queue->refresh();
        $this->assertEquals('called', $queue->status);
    }

    /**
     * Test today's visit list.
     */
    public function test_can_view_todays_visit_list(): void
    {
        $patient = Patient::factory()->create();
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'visit_date' => now(),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/visits/today');

        $response->assertStatus(200);
    }

    /**
     * Test visit details can be viewed.
     */
    public function test_visit_details_can_be_viewed(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'complaint' => 'Sakit kepala',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get("/admin/visits/{$visit->id}");

        $response->assertStatus(200);
        $response->assertSee('Sakit kepala');
    }

    /**
     * Test visit can be cancelled.
     */
    public function test_visit_can_be_cancelled(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'waiting',
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'waiting',
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->post("/admin/visits/{$visit->id}/cancel", [
                'reason' => 'Pasien membatalkan',
            ]);

        $response->assertRedirect();

        $queue->refresh();
        $this->assertEquals('cancelled', $queue->status);
    }

    /**
     * Test BPJS patient registration includes SEP number.
     */
    public function test_bpjs_patient_registration_includes_sep_number(): void
    {
        $patient = Patient::factory()->create([
            'insurance_type' => 'bpjs',
            'bpjs_card_number' => '0001234567890',
        ]);

        $registrationData = [
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now()->format('Y-m-d'),
            'visit_type' => 'rawat_jalan',
            'registration_type' => 'bpjs',
            'priority' => 'normal',
            'complaint' => 'Sakit punggung',
            'bpjs_sep_number' => '0123R0010124V000001',
        ];

        $response = $this->actingAs($this->registrationUser)
            ->post('/admin/visits', $registrationData);

        $response->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'bpjs_sep_number' => '0123R0010124V000001',
        ]);
    }

    /**
     * Test queue waiting time calculation.
     */
    public function test_queue_waiting_time_calculation(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
        ]);
        $queue = VisitQueue::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'status' => 'waiting',
            'created_at' => now()->subMinutes(15),
        ]);

        $waitingTime = $queue->waiting_time;
        $this->assertGreaterThanOrEqual(15, $waitingTime);
    }

    /**
     * Test multiple polyclinics have separate queue sequences.
     */
    public function test_multiple_polyclinics_have_separate_queue_sequences(): void
    {
        $polyclinic2 = Polyclinic::factory()->create([
            'name' => 'Poli Gigi',
            'queue_prefix' => 'B',
            'is_active' => true,
        ]);

        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient1->id,
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'registration_type' => 'baru',
                'priority' => 'normal',
                'complaint' => 'Sakit',
            ]);

        $this->actingAs($this->registrationUser)
            ->post('/admin/visits', [
                'patient_id' => $patient2->id,
                'polyclinic_id' => $polyclinic2->id,
                'doctor_id' => $this->doctor->id,
                'visit_date' => now()->format('Y-m-d'),
                'visit_type' => 'rawat_jalan',
                'registration_type' => 'baru',
                'priority' => 'normal',
                'complaint' => 'Sakit gigi',
            ]);

        $queue1 = VisitQueue::where('polyclinic_id', $this->polyclinic->id)->first();
        $queue2 = VisitQueue::where('polyclinic_id', $polyclinic2->id)->first();

        $this->assertStringStartsWith('A', $queue1->display_number);
        $this->assertStringStartsWith('B', $queue2->display_number);
    }
}
