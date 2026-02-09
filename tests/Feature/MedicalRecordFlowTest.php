<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\Cppt;
use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\Clinical\PrescriptionItem;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MedicalRecordFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $doctorUser;
    protected User $nurseUser;
    protected Employee $doctor;
    protected Employee $nurse;
    protected Patient $patient;
    protected Visit $visit;
    protected Polyclinic $polyclinic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'nurse', 'guard_name' => 'web']);

        // Create polyclinic
        $this->polyclinic = Polyclinic::factory()->create([
            'name' => 'Poli Umum',
            'is_active' => true,
        ]);

        // Create doctor
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $this->polyclinic->id,
            'status' => 'aktif',
        ]);

        // Create nurse
        $this->nurse = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_nurse' => true,
            'status' => 'aktif',
        ]);

        // Create users
        $this->doctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->doctor->id,
        ]);
        $this->doctorUser->assignRole('doctor');

        $this->nurseUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->nurse->id,
        ]);
        $this->nurseUser->assignRole('nurse');

        // Create patient and visit
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now(),
            'status' => 'in_progress',
            'is_completed' => false,
        ]);
    }

    /**
     * Test doctor can create medical record from visit.
     */
    public function test_doctor_can_create_medical_record_from_visit(): void
    {
        $recordData = [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'visit_date' => now()->format('Y-m-d'),
            'subjective' => 'Pasien mengeluh sakit kepala sejak 3 hari yang lalu',
            'objective' => 'TD: 120/80, HR: 80x/menit, RR: 20x/menit',
            'assessment' => 'Cephalgia',
            'plan' => 'Diberikan analgetik, istirahat cukup',
            'diagnosis_primary' => 'Cephalgia',
            'icd10_code' => 'R51',
            'icd10_description' => 'Headache',
            'notes' => 'Pasien dalam kondisi stabil',
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/medical-records', $recordData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'diagnosis_primary' => 'Cephalgia',
            'icd10_code' => 'R51',
            'is_finalized' => false,
        ]);
    }

    /**
     * Test medical record creation generates record number.
     */
    public function test_medical_record_creation_generates_record_number(): void
    {
        $recordData = [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'visit_date' => now()->format('Y-m-d'),
            'subjective' => 'Sakit perut',
            'objective' => 'TD normal',
            'assessment' => 'Gastritis',
            'plan' => 'Obat maag',
            'diagnosis_primary' => 'Gastritis',
        ];

        $this->actingAs($this->doctorUser)
            ->post('/admin/medical-records', $recordData);

        $medicalRecord = MedicalRecord::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($medicalRecord);
        $this->assertNotNull($medicalRecord->record_number);
    }

    /**
     * Test medical record requires visit_id.
     */
    public function test_medical_record_requires_visit_id(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/medical-records', [
                'patient_id' => $this->patient->id,
                'visit_id' => '',
                'visit_date' => now()->format('Y-m-d'),
                'subjective' => 'Sakit',
                'objective' => 'Normal',
                'assessment' => 'Diagnosis',
                'plan' => 'Treatment',
            ]);

        $response->assertSessionHasErrors('visit_id');
    }

    /**
     * Test nurse can input TTV assessment.
     */
    public function test_nurse_can_input_ttv_assessment(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $assessmentData = [
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'assessment_type' => 'ttv',
            'assessment_date' => now()->format('Y-m-d'),
            'chief_complaint' => 'Sakit kepala',
            'vital_signs' => [
                'blood_pressure' => '120/80',
                'heart_rate' => 80,
                'respiratory_rate' => 20,
                'temperature' => 36.5,
                'oxygen_saturation' => 98,
                'weight_kg' => 70,
                'height_cm' => 170,
            ],
            'physical_examination' => [
                'general_condition' => 'Baik',
                'consciousness' => 'Compos Mentis',
            ],
            'assessed_by' => $this->nurse->id,
        ];

        $response = $this->actingAs($this->nurseUser)
            ->post('/admin/assessments', $assessmentData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'medical_record_id' => $medicalRecord->id,
            'assessment_type' => 'ttv',
            'assessed_by' => $this->nurse->id,
        ]);
    }

    /**
     * Test TTV assessment calculates BMI.
     */
    public function test_ttv_assessment_calculates_bmi(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $assessment = Assessment::create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'assessment_type' => 'ttv',
            'assessment_date' => now(),
            'vital_signs' => [
                'weight_kg' => 70,
                'height_cm' => 170,
                'blood_pressure' => '120/80',
            ],
            'assessed_by' => $this->nurse->id,
        ]);

        $expectedBmi = round(70 / ((170 / 100) ** 2), 2);
        $this->assertEquals($expectedBmi, $assessment->bmi);
    }

    /**
     * Test TTV assessment determines blood pressure status.
     */
    public function test_ttv_assessment_determines_blood_pressure_status(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $assessment = Assessment::create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'assessment_type' => 'ttv',
            'assessment_date' => now(),
            'vital_signs' => [
                'blood_pressure' => '140/90',
            ],
            'assessed_by' => $this->nurse->id,
        ]);

        $this->assertEquals('stage1', $assessment->blood_pressure_status);
    }

    /**
     * Test doctor can create CPPT with SOAP format.
     */
    public function test_doctor_can_create_cppt_with_soap_format(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $cpptData = [
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'cppt_date' => now()->format('Y-m-d'),
            'cppt_time' => now()->format('H:i:s'),
            'subjective' => 'Pasien mengeluh mual dan muntah sejak pagi',
            'objective' => 'Muka pucat, TD: 110/70, Nadi: 88x/menit',
            'assessment' => 'Gastroenteritis akut',
            'plan' => '1. IVF NaCl 0.9% 20 tpm\n2. Injeksi Ondansetron 4 mg\n3. Observasi',
            'instruction' => 'Puasa makanan padat, minum oralit',
            'created_by' => $this->doctorUser->id,
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/cppts', $cpptData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cppts', [
            'medical_record_id' => $medicalRecord->id,
            'subjective' => 'Pasien mengeluh mual dan muntah sejak pagi',
            'assessment' => 'Gastroenteritis akut',
            'is_verified' => false,
        ]);
    }

    /**
     * Test CPPT requires all SOAP components.
     */
    public function test_cppt_requires_all_soap_components(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/cppts', [
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'cppt_date' => now()->format('Y-m-d'),
                'subjective' => '',
                'objective' => '',
                'assessment' => '',
                'plan' => '',
            ]);

        $response->assertSessionHasErrors(['subjective', 'objective', 'assessment', 'plan']);
    }

    /**
     * Test doctor can create prescription from medical record.
     */
    public function test_doctor_can_create_prescription_from_medical_record(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $medicine1 = Medicine::factory()->create([
            'name' => 'Paracetamol 500mg',
            'stock' => 100,
            'is_active' => true,
        ]);
        $medicine2 = Medicine::factory()->create([
            'name' => 'Amoxicillin 500mg',
            'stock' => 50,
            'is_active' => true,
        ]);

        $prescriptionData = [
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'medical_record_id' => $medicalRecord->id,
            'prescription_date' => now()->format('Y-m-d'),
            'prescription_type' => 'non_racikan',
            'priority' => 'normal',
            'clinical_indication' => 'Demam dan infeksi bakteri',
            'allergies' => 'Tidak ada alergi known',
            'prescribed_by' => $this->doctor->id,
            'items' => [
                [
                    'medicine_id' => $medicine1->id,
                    'generic_name' => 'Paracetamol',
                    'dosage_form' => 'tablet',
                    'strength' => '500mg',
                    'quantity' => 10,
                    'unit' => 'tablet',
                    'dosage_instructions' => '3 x 1 tablet',
                    'frequency' => '3 kali sehari',
                    'duration_days' => 3,
                    'route_of_administration' => 'oral',
                    'instructions' => 'Setelah makan',
                ],
                [
                    'medicine_id' => $medicine2->id,
                    'generic_name' => 'Amoxicillin',
                    'dosage_form' => 'kapsul',
                    'strength' => '500mg',
                    'quantity' => 15,
                    'unit' => 'kapsul',
                    'dosage_instructions' => '3 x 1 kapsul',
                    'frequency' => '3 kali sehari',
                    'duration_days' => 5,
                    'route_of_administration' => 'oral',
                    'instructions' => 'Sebelum makan',
                ],
            ],
        ];

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/prescriptions', $prescriptionData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('prescriptions', [
            'patient_id' => $this->patient->id,
            'medical_record_id' => $medicalRecord->id,
            'prescribed_by' => $this->doctor->id,
            'status' => 'pending',
        ]);

        $prescription = Prescription::where('medical_record_id', $medicalRecord->id)->first();
        $this->assertEquals(2, $prescription->items()->count());
    }

    /**
     * Test prescription requires at least one item.
     */
    public function test_prescription_requires_at_least_one_item(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post('/admin/prescriptions', [
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'medical_record_id' => $medicalRecord->id,
                'prescription_date' => now()->format('Y-m-d'),
                'prescription_type' => 'non_racikan',
                'clinical_indication' => 'Sakit kepala',
                'prescribed_by' => $this->doctor->id,
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');
    }

    /**
     * Test doctor can finalize medical record.
     */
    public function test_doctor_can_finalize_medical_record(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->post("/admin/medical-records/{$medicalRecord->id}/finalize");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $medicalRecord->refresh();
        $this->assertTrue($medicalRecord->is_finalized);
        $this->assertNotNull($medicalRecord->finalized_at);
        $this->assertEquals($this->doctorUser->id, $medicalRecord->finalized_by);
    }

    /**
     * Test finalized medical record cannot be edited.
     */
    public function test_finalized_medical_record_cannot_be_edited(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $this->doctorUser->id,
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->put("/admin/medical-records/{$medicalRecord->id}", [
                'subjective' => 'Updated subjective',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test nurse cannot finalize medical record.
     */
    public function test_nurse_cannot_finalize_medical_record(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'is_finalized' => false,
        ]);

        $response = $this->actingAs($this->nurseUser)
            ->post("/admin/medical-records/{$medicalRecord->id}/finalize");

        $response->assertStatus(403);
    }

    /**
     * Test medical record shows SOAP note.
     */
    public function test_medical_record_shows_soap_note(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'subjective' => 'Sakit kepala',
            'objective' => 'TD normal',
            'assessment' => 'Cephalgia',
            'plan' => 'Analgetik',
        ]);

        $soapNote = $medicalRecord->soap_note;

        $this->assertStringContainsString('S: Sakit kepala', $soapNote);
        $this->assertStringContainsString('O: TD normal', $soapNote);
        $this->assertStringContainsString('A: Cephalgia', $soapNote);
        $this->assertStringContainsString('P: Analgetik', $soapNote);
    }

    /**
     * Test CPPT can be verified by senior doctor.
     */
    public function test_cppt_can_be_verified_by_senior_doctor(): void
    {
        $seniorDoctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'status' => 'aktif',
        ]);
        $seniorDoctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $seniorDoctor->id,
        ]);
        $seniorDoctorUser->assignRole('doctor');

        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $cppt = Cppt::factory()->create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($seniorDoctorUser)
            ->post("/admin/cppts/{$cppt->id}/verify");

        $response->assertRedirect();

        $cppt->refresh();
        $this->assertTrue($cppt->is_verified);
        $this->assertNotNull($cppt->verified_at);
        $this->assertEquals($seniorDoctor->id, $cppt->verified_by);
    }

    /**
     * Test medical record lists all related CPPTs.
     */
    public function test_medical_record_lists_all_related_cppts(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        Cppt::factory()->count(3)->create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $this->assertEquals(3, $medicalRecord->cppts()->count());
    }

    /**
     * Test medical record lists all related assessments.
     */
    public function test_medical_record_lists_all_related_assessments(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        Assessment::factory()->count(2)->create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $this->assertEquals(2, $medicalRecord->assessments()->count());
    }

    /**
     * Test medical record lists all related prescriptions.
     */
    public function test_medical_record_lists_all_related_prescriptions(): void
    {
        $medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        Prescription::factory()->count(2)->create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        $this->assertEquals(2, $medicalRecord->prescriptions()->count());
    }

    /**
     * Test complete medical record workflow.
     */
    public function test_complete_medical_record_workflow(): void
    {
        // 1. Create medical record
        $recordResponse = $this->actingAs($this->doctorUser)
            ->post('/admin/medical-records', [
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'visit_date' => now()->format('Y-m-d'),
                'subjective' => 'Demam sejak 2 hari',
                'objective' => 'Temperatur 38.5C',
                'assessment' => 'Demam viral',
                'plan' => 'Symptomatic treatment',
                'diagnosis_primary' => 'Demam viral',
                'icd10_code' => 'R50.9',
            ]);

        $recordResponse->assertRedirect();
        $medicalRecord = MedicalRecord::where('patient_id', $this->patient->id)->first();

        // 2. Add TTV assessment by nurse
        $assessmentResponse = $this->actingAs($this->nurseUser)
            ->post('/admin/assessments', [
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'assessment_type' => 'ttv',
                'assessment_date' => now()->format('Y-m-d'),
                'chief_complaint' => 'Demam',
                'vital_signs' => [
                    'temperature' => 38.5,
                    'blood_pressure' => '120/80',
                    'heart_rate' => 90,
                ],
                'assessed_by' => $this->nurse->id,
            ]);

        $assessmentResponse->assertRedirect();

        // 3. Add CPPT
        $cpptResponse = $this->actingAs($this->doctorUser)
            ->post('/admin/cppts', [
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'cppt_date' => now()->format('Y-m-d'),
                'subjective' => 'Demam',
                'objective' => 'Temperatur tinggi',
                'assessment' => 'Demam viral',
                'plan' => 'Rest and hydration',
                'created_by' => $this->doctorUser->id,
            ]);

        $cpptResponse->assertRedirect();

        // 4. Create prescription
        $medicine = Medicine::factory()->create(['stock' => 100]);
        $prescriptionResponse = $this->actingAs($this->doctorUser)
            ->post('/admin/prescriptions', [
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'medical_record_id' => $medicalRecord->id,
                'prescription_date' => now()->format('Y-m-d'),
                'prescription_type' => 'non_racikan',
                'clinical_indication' => 'Demam',
                'prescribed_by' => $this->doctor->id,
                'items' => [
                    [
                        'medicine_id' => $medicine->id,
                        'generic_name' => 'Paracetamol',
                        'quantity' => 10,
                        'unit' => 'tablet',
                        'dosage_instructions' => '3 x 1',
                    ],
                ],
            ]);

        $prescriptionResponse->assertRedirect();

        // 5. Finalize medical record
        $finalizeResponse = $this->actingAs($this->doctorUser)
            ->post("/admin/medical-records/{$medicalRecord->id}/finalize");

        $finalizeResponse->assertRedirect();

        // Verify all records exist
        $medicalRecord->refresh();
        $this->assertTrue($medicalRecord->is_finalized);
        $this->assertEquals(1, $medicalRecord->assessments()->count());
        $this->assertEquals(1, $medicalRecord->cppts()->count());
        $this->assertEquals(1, $medicalRecord->prescriptions()->count());
    }

    /**
     * Test medical record search by diagnosis.
     */
    public function test_can_search_medical_record_by_diagnosis(): void
    {
        MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'diagnosis_primary' => 'Diabetes Mellitus',
            'icd10_code' => 'E11',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get('/admin/medical-records?search=Diabetes');

        $response->assertStatus(200);
        $response->assertSee('Diabetes Mellitus');
    }

    /**
     * Test medical record search by ICD10 code.
     */
    public function test_can_search_medical_record_by_icd10_code(): void
    {
        MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'diagnosis_primary' => 'Hypertension',
            'icd10_code' => 'I10',
        ]);

        $response = $this->actingAs($this->doctorUser)
            ->get('/admin/medical-records?search=I10');

        $response->assertStatus(200);
        $response->assertSee('Hypertension');
    }
}
