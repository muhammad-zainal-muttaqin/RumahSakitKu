<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Financial\Invoice;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InpatientFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $doctorUser;
    private User $nurseUser;
    private Employee $doctor;
    private Room $room;
    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'nurse', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);

        // Create users
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->doctorUser = User::factory()->create(['is_active' => true]);
        $this->doctorUser->assignRole('doctor');

        $this->nurseUser = User::factory()->create(['is_active' => true]);
        $this->nurseUser->assignRole('nurse');

        // Create doctor
        $polyclinic = Polyclinic::factory()->create();
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $polyclinic->id,
            'status' => 'aktif',
        ]);

        // Create room and bed
        $this->room = Room::factory()->create([
            'room_class' => 'Kelas I',
            'is_active' => true,
        ]);

        $this->bed = Bed::factory()->create([
            'room_id' => $this->room->id,
            'status' => 'kosong',
            'current_visit_id' => null,
        ]);
    }

    #[Test]
    public function it_can_admit_patient_to_inpatient(): void
    {
        $patient = Patient::factory()->create();

        $admissionData = [
            'patient_id' => $patient->id,
            'room_id' => $this->room->id,
            'bed_id' => $this->bed->id,
            'doctor_id' => $this->doctor->id,
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->format('Y-m-d'),
            'complaint' => 'Sakit perut berat',
            'diagnosis' => 'Appendicitis',
            'deposit_amount' => 5000000,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/inpatients', $admissionData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('beds', [
            'id' => $this->bed->id,
            'status' => 'terisi',
        ]);

        $this->assertDatabaseHas('visits', [
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
        ]);
    }

    #[Test]
    public function it_updates_bed_status_to_occupied_on_admission(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->adminUser)
            ->post('/admin/inpatients', [
                'patient_id' => $patient->id,
                'room_id' => $this->room->id,
                'bed_id' => $this->bed->id,
                'doctor_id' => $this->doctor->id,
                'visit_type' => 'rawat_inap',
                'admission_date' => now()->format('Y-m-d'),
                'complaint' => 'Demam tinggi',
            ]);

        $this->bed->refresh();
        $this->assertEquals('terisi', $this->bed->status);
        $this->assertNotNull($this->bed->occupied_at);
        $this->assertNotNull($this->bed->current_visit_id);
    }

    #[Test]
    public function it_can_transfer_patient_to_different_bed(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'doctor_id' => $this->doctor->id,
        ]);

        $this->bed->update([
            'status' => 'terisi',
            'current_visit_id' => $visit->id,
            'occupied_at' => now()->subDay(),
        ]);

        $newBed = Bed::factory()->create([
            'room_id' => $this->room->id,
            'status' => 'kosong',
            'current_visit_id' => null,
        ]);

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/inpatients/{$visit->id}/transfer", [
                'new_bed_id' => $newBed->id,
                'transfer_reason' => 'Permintaan pasien untuk kamar dengan pemandangan',
            ]);

        $response->assertRedirect();

        // Old bed should be vacated
        $this->bed->refresh();
        $this->assertEquals('kosong', $this->bed->status);
        $this->assertNull($this->bed->current_visit_id);
        $this->assertNotNull($this->bed->vacated_at);

        // New bed should be occupied
        $newBed->refresh();
        $this->assertEquals('terisi', $newBed->status);
        $this->assertEquals($visit->id, $newBed->current_visit_id);
    }

    #[Test]
    public function it_cannot_assign_occupied_bed(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $visit1 = Visit::factory()->create([
            'patient_id' => $patient1->id,
            'visit_type' => 'rawat_inap',
        ]);

        $this->bed->update([
            'status' => 'terisi',
            'current_visit_id' => $visit1->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/inpatients', [
                'patient_id' => $patient2->id,
                'room_id' => $this->room->id,
                'bed_id' => $this->bed->id,
                'doctor_id' => $this->doctor->id,
                'visit_type' => 'rawat_inap',
                'admission_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('bed_id');

        $this->assertDatabaseMissing('visits', [
            'patient_id' => $patient2->id,
            'visit_type' => 'rawat_inap',
        ]);
    }

    #[Test]
    public function it_can_discharge_patient_and_generate_invoice(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'doctor_id' => $this->doctor->id,
            'status' => 'in_progress',
        ]);

        $this->bed->update([
            'status' => 'terisi',
            'current_visit_id' => $visit->id,
            'occupied_at' => now()->subDays(3),
        ]);

        $dischargeData = [
            'discharge_date' => now()->format('Y-m-d'),
            'discharge_status' => 'sembuh',
            'final_diagnosis' => 'Typhoid, sudah sembuh',
            'notes' => 'Pasien diperbolehkan pulang',
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/inpatients/{$visit->id}/discharge", $dischargeData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Bed should be vacated
        $this->bed->refresh();
        $this->assertEquals('kosong', $this->bed->status);
        $this->assertNull($this->bed->current_visit_id);
        $this->assertNotNull($this->bed->vacated_at);

        // Invoice should be generated
        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
        ]);

        // Visit should be marked complete
        $visit->refresh();
        $this->assertTrue($visit->is_completed);
        $this->assertNotNull($visit->check_out_at);
    }

    #[Test]
    public function it_cannot_discharge_already_discharged_patient(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'status' => 'completed',
            'is_completed' => true,
            'check_out_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/inpatients/{$visit->id}/discharge", [
                'discharge_date' => now()->format('Y-m-d'),
                'discharge_status' => 'sembuh',
            ]);

        $response->assertStatus(422)->assertSessionHasErrors();
    }

    #[Test]
    public function it_calculates_length_of_stay_correctly(): void
    {
        $patient = Patient::factory()->create();
        $admissionDate = now()->subDays(5);

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'visit_date' => $admissionDate,
            'check_in_at' => $admissionDate,
            'status' => 'in_progress',
        ]);

        $this->bed->update([
            'status' => 'terisi',
            'current_visit_id' => $visit->id,
            'occupied_at' => $admissionDate,
        ]);

        $los = $this->bed->occupancy_duration;
        $this->assertGreaterThanOrEqual(120, $los); // At least 5 days in hours

        $visit->update([
            'check_out_at' => now(),
            'is_completed' => true,
        ]);

        $duration = $visit->duration;
        $this->assertNotNull($duration);
        $this->assertGreaterThan(0, $duration);
    }

    #[Test]
    public function it_manages_deposit_for_inpatient(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
        ]);

        // Initial deposit
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/inpatients/{$visit->id}/deposit", [
                'amount' => 5000000,
                'payment_method' => 'cash',
                'notes' => 'Deposit awal',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'visit_id' => $visit->id,
            'amount' => 5000000,
            'payment_type' => 'deposit',
        ]);

        // Additional deposit
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/inpatients/{$visit->id}/deposit", [
                'amount' => 3000000,
                'payment_method' => 'transfer',
                'notes' => 'Deposit tambahan',
            ]);

        $this->assertDatabaseHas('payments', [
            'visit_id' => $visit->id,
            'amount' => 3000000,
            'payment_type' => 'deposit',
        ]);
    }

    #[Test]
    public function it_tracks_bed_occupancy_history(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
        ]);

        $this->bed->update([
            'status' => 'terisi',
            'current_visit_id' => $visit->id,
            'occupied_at' => now()->subDays(2),
        ]);

        $this->assertDatabaseHas('beds', [
            'id' => $this->bed->id,
            'status' => 'terisi',
        ]);

        // Vacate bed
        $this->bed->vacate();

        $this->assertDatabaseHas('beds', [
            'id' => $this->bed->id,
            'status' => 'kosong',
            'current_visit_id' => null,
        ]);

        $this->assertNotNull($this->bed->vacated_at);
    }

    #[Test]
    public function it_validates_room_class_availability(): void
    {
        $patient = Patient::factory()->create();

        $vipRoom = Room::factory()->create([
            'room_class' => 'VIP',
            'available_beds' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/inpatients', [
                'patient_id' => $patient->id,
                'room_id' => $vipRoom->id,
                'bed_id' => $this->bed->id,
                'doctor_id' => $this->doctor->id,
                'visit_type' => 'rawat_inap',
                'admission_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('room_id');
    }

    #[Test]
    public function it_updates_room_availability_on_admission_and_discharge(): void
    {
        $room = Room::factory()->create([
            'room_class' => 'Kelas II',
            'total_beds' => 4,
            'available_beds' => 4,
        ]);

        $bed = Bed::factory()->create([
            'room_id' => $room->id,
            'status' => 'kosong',
        ]);

        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
        ]);

        // Admit patient
        $bed->occupy($visit->id);

        $room->refresh();
        $this->assertEquals(3, $room->available_beds);

        // Discharge patient
        $bed->vacate();

        $room->refresh();
        $this->assertEquals(4, $room->available_beds);
    }

    #[Test]
    public function it_prevents_admission_without_mandatory_fields(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/inpatients', [
                'patient_id' => '',
                'room_id' => '',
                'bed_id' => '',
                'doctor_id' => '',
                'admission_date' => '',
            ]);

        $response->assertSessionHasErrors(['patient_id', 'room_id', 'bed_id', 'doctor_id', 'admission_date']);
    }

    #[Test]
    public function it_can_view_inpatient_list(): void
    {
        $patients = Patient::factory()->count(3)->create();

        foreach ($patients as $patient) {
            $visit = Visit::factory()->create([
                'patient_id' => $patient->id,
                'visit_type' => 'rawat_inap',
                'status' => 'in_progress',
            ]);

            Bed::factory()->create([
                'room_id' => $this->room->id,
                'status' => 'terisi',
                'current_visit_id' => $visit->id,
            ]);
        }

        $response = $this->actingAs($this->nurseUser)
            ->get('/admin/inpatients');

        $response->assertStatus(200);
        $response->assertSee('Rawat Inap');
    }

    #[Test]
    public function it_calculates_bor_statistics(): void
    {
        // Create multiple rooms and beds
        $room1 = Room::factory()->create(['total_beds' => 10]);
        $room2 = Room::factory()->create(['total_beds' => 10]);

        // Create 15 occupied beds and 5 empty
        for ($i = 0; $i < 8; $i++) {
            Bed::factory()->create([
                'room_id' => $room1->id,
                'status' => 'terisi',
            ]);
        }

        for ($i = 0; $i < 7; $i++) {
            Bed::factory()->create([
                'room_id' => $room2->id,
                'status' => 'terisi',
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            Bed::factory()->create([
                'room_id' => $room1->id,
                'status' => 'kosong',
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/bed-occupancy');

        $response->assertStatus(200);
        // BOR = (15 occupied / 20 total) * 100 = 75%
    }
}
