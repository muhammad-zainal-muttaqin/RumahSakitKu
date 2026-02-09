<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\RadiologyOrder;
use App\Models\Clinical\RadiologyResult;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RadiologyFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;
    private User $radiographerUser;
    private User $radiologistUser;
    private Employee $doctor;
    private Employee $radiographer;
    private Employee $radiologist;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'radiographer', 'guard_name' => 'web']);
        Role::create(['name' => 'radiologist', 'guard_name' => 'web']);

        // Create polyclinic
        $polyclinic = Polyclinic::factory()->create();

        // Create employees
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $polyclinic->id,
        ]);

        $this->radiographer = Employee::factory()->create([
            'employee_type' => 'tetap',
            'status' => 'aktif',
        ]);

        $this->radiologist = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialization' => 'Radiologi',
        ]);

        // Create users
        $this->doctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->doctor->id,
        ]);
        $this->doctorUser->assignRole('doctor');

        $this->radiographerUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->radiographer->id,
        ]);
        $this->radiographerUser->assignRole('radiographer');

        $this->radiologistUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->radiologist->id,
        ]);
        $this->radiologistUser->assignRole('radiologist');
    }

    #[Test]
    public function it_can_create_radiology_order(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $orderData = [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'examination_type' => 'xray',
            'body_area' => 'Thorax',
            'position' => 'PA',
            'clinical_indication' => 'Suspek pneumonia',
            'priority' => 'normal',
            'notes' => 'Pasien batuk dan demam 3 hari',
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/radiology/orders', $orderData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('radiology_orders', [
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'examination_type' => 'xray',
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_auto_generates_radiology_order_number(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $this->actingAs($this->doctorUser)
            ->post('/admin/radiology/orders', [
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctor->id,
                'examination_type' => 'ct_scan',
                'body_area' => 'Abdomen',
            ]);

        $order = RadiologyOrder::where('visit_id', $visit->id)->first();
        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('RAD', $order->order_number);
    }

    #[Test]
    public function it_can_schedule_radiology_examination(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'pending',
        ]);

        $scheduleData = [
            'scheduled_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'room' => 'Ruang CT Scan 1',
            'notes' => 'Puasa 4 jam sebelum pemeriksaan',
        ];

        $response = $this->actingAs($this->radiographerUser)
            ->post("/admin/radiology/orders/{$order->id}/schedule", $scheduleData);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('scheduled', $order->status);
        $this->assertNotNull($order->scheduled_date);
    }

    #[Test]
    public function it_can_upload_result_images(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'in_progress',
        ]);

        $images = [
            UploadedFile::fake()->image('xray1.jpg'),
            UploadedFile::fake()->image('xray2.jpg'),
        ];

        $response = $this->actingAs($this->radiographerUser)
            ->post("/admin/radiology/orders/{$order->id}/upload", [
                'images' => $images,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('radiology_results', [
            'radiology_order_id' => $order->id,
        ]);
    }

    #[Test]
    public function it_can_record_examination_completion(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->radiographerUser)
            ->post("/admin/radiology/orders/{$order->id}/complete", [
                'technician_notes' => 'Pemeriksaan berhasil dilakukan',
                'exposure_parameters' => 'kVp: 110, mAs: 5',
                'dose_info' => '0.1 mSv',
            ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    #[Test]
    public function it_can_create_radiology_report(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
        ]);

        $result = RadiologyResult::factory()->create([
            'radiology_order_id' => $order->id,
        ]);

        $reportData = [
            'report_text' => 'Terlihat infiltrat pada paru kanan basal, konsisten dengan pneumonia.',
            'conclusion' => 'Pneumonia kanan basal',
            'recommendation' => 'Kontrol 2 minggu setelah terapi',
            'radiologist_id' => $this->radiologist->id,
        ];

        $response = $this->actingAs($this->radiologistUser)
            ->post("/admin/radiology/results/{$result->id}/report", $reportData);

        $response->assertRedirect();

        $result->refresh();
        $this->assertNotNull($result->report_text);
        $this->assertNotNull($result->conclusion);
        $this->assertNotNull($result->reported_at);

        $order->refresh();
        $this->assertEquals('reported', $order->status);
    }

    #[Test]
    public function it_integrates_report_with_emr(): void
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

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'medical_record_id' => $medicalRecord->id,
            'status' => 'reported',
        ]);

        RadiologyResult::factory()->create([
            'radiology_order_id' => $order->id,
            'report_text' => 'Infiltrat pada paru kanan',
            'conclusion' => 'Pneumonia',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/admin/medical-records/{$medicalRecord->id}");

        $response->assertStatus(200);
        $response->assertSee('Pneumonia');
        $response->assertSee('Infiltrat pada paru kanan');
    }

    #[Test]
    public function it_notifies_doctor_when_report_ready(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'doctor_id' => $this->doctor->id,
            'status' => 'completed',
        ]);

        $result = RadiologyResult::factory()->create([
            'radiology_order_id' => $order->id,
        ]);

        $response = $this->actingAs($this->radiologistUser)
            ->post("/admin/radiology/results/{$result->id}/report", [
                'report_text' => 'Normal',
                'conclusion' => 'Tidak ada kelainan',
                'radiologist_id' => $this->radiologist->id,
            ]);

        $response->assertRedirect();

        // Notification should be created
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->doctorUser->id,
            'type' => 'App\\Notifications\\RadiologyReportReady',
        ]);
    }

    #[Test]
    public function it_validates_contrast_usage_for_ct_scan(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/radiology/orders', [
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctor->id,
                'examination_type' => 'ct_scan',
                'body_area' => 'Abdomen',
                'contrast' => true,
                'contrast_type' => '',
            ]);

        $response->assertSessionHasErrors('contrast_type');
    }

    #[Test]
    public function it_prevents_duplicate_scheduling_in_same_room(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $scheduledTime = now()->addDay()->setTime(10, 0);

        RadiologyOrder::factory()->create([
            'patient_id' => $patient1->id,
            'status' => 'scheduled',
            'scheduled_date' => $scheduledTime,
        ]);

        $order2 = RadiologyOrder::factory()->create([
            'patient_id' => $patient2->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->radiographerUser)
            ->post("/admin/radiology/orders/{$order2->id}/schedule", [
                'scheduled_date' => $scheduledTime->format('Y-m-d H:i:s'),
                'room' => 'Ruang CT Scan 1',
            ]);

        $response->assertSessionHasErrors('scheduled_date');
    }

    #[Test]
    public function it_tracks_examination_dose_information(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $order = RadiologyOrder::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'examination_type' => 'ct_scan',
            'status' => 'in_progress',
        ]);

        RadiologyResult::factory()->create([
            'radiology_order_id' => $order->id,
            'dose_info' => 'CTDIvol: 15 mGy, DLP: 450 mGy.cm',
        ]);

        $result = RadiologyResult::where('radiology_order_id', $order->id)->first();
        $this->assertNotNull($result->dose_info);
    }

    #[Test]
    public function it_prioritizes_emergency_examinations(): void
    {
        $patient1 = Patient::factory()->create();
        $patient2 = Patient::factory()->create();

        $normalOrder = RadiologyOrder::factory()->create([
            'patient_id' => $patient1->id,
            'priority' => 'normal',
            'status' => 'pending',
            'created_at' => now()->subHour(),
        ]);

        $emergencyOrder = RadiologyOrder::factory()->create([
            'patient_id' => $patient2->id,
            'priority' => 'emergency',
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->radiographerUser)
            ->get('/admin/radiology/orders?priority=emergency');

        $response->assertStatus(200);
        $this->assertEquals('emergency', $emergencyOrder->priority);
    }

    #[Test]
    public function it_validates_radiology_order_mandatory_fields(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/radiology/orders', [
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'doctor_id' => $this->doctor->id,
                'examination_type' => '',
                'body_area' => '',
            ]);

        $response->assertSessionHasErrors(['examination_type', 'body_area']);
    }

    #[Test]
    public function it_can_view_radiology_history_for_patient(): void
    {
        $patient = Patient::factory()->create();

        RadiologyOrder::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'status' => 'reported',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get("/admin/patients/{$patient->id}/radiology-history");

        $response->assertStatus(200);
    }
}
