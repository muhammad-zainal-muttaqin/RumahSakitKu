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

class PharmacyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $pharmacyUser;
    protected User $doctorUser;
    protected Employee $pharmacist;
    protected Employee $doctor;
    protected Patient $patient;
    protected Visit $visit;
    protected MedicalRecord $medicalRecord;
    protected Prescription $prescription;
    protected Polyclinic $polyclinic;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'pharmacy', 'guard_name' => 'web']);

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

        // Create pharmacist
        $this->pharmacist = Employee::factory()->create([
            'employee_type' => 'tetap',
            'status' => 'aktif',
        ]);

        // Create users
        $this->doctorUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->doctor->id,
        ]);
        $this->doctorUser->assignRole('doctor');

        $this->pharmacyUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->pharmacist->id,
        ]);
        $this->pharmacyUser->assignRole('pharmacy');

        // Create patient and visit
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);

        // Create medical record
        $this->medicalRecord = MedicalRecord::factory()->create([
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
        ]);

        // Create medicines
        $this->medicine1 = Medicine::factory()->create([
            'name' => 'Paracetamol 500mg',
            'generic_name' => 'Paracetamol',
            'stock' => 100,
            'min_stock' => 10,
            'selling_price' => 5000,
            'is_active' => true,
        ]);

        $this->medicine2 = Medicine::factory()->create([
            'name' => 'Amoxicillin 500mg',
            'generic_name' => 'Amoxicillin',
            'stock' => 50,
            'min_stock' => 5,
            'selling_price' => 8000,
            'is_active' => true,
        ]);

        // Create prescription
        $this->prescription = Prescription::factory()->create([
            'prescription_number' => 'RX' . now()->format('Ymd') . '001',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'medical_record_id' => $this->medicalRecord->id,
            'prescription_date' => now(),
            'prescription_type' => 'non_racikan',
            'priority' => 'normal',
            'status' => 'pending',
            'clinical_indication' => 'Demam dan infeksi',
            'prescribed_by' => $this->doctor->id,
            'verified_by_pharmacist' => false,
        ]);

        // Create prescription items
        PrescriptionItem::factory()->create([
            'prescription_id' => $this->prescription->id,
            'medicine_id' => $this->medicine1->id,
            'generic_name' => 'Paracetamol',
            'dosage_form' => 'tablet',
            'strength' => '500mg',
            'quantity' => 10,
            'unit' => 'tablet',
            'unit_price' => 5000,
            'total_price' => 50000,
            'is_dispensed' => false,
        ]);

        PrescriptionItem::factory()->create([
            'prescription_id' => $this->prescription->id,
            'medicine_id' => $this->medicine2->id,
            'generic_name' => 'Amoxicillin',
            'dosage_form' => 'kapsul',
            'strength' => '500mg',
            'quantity' => 15,
            'unit' => 'kapsul',
            'unit_price' => 8000,
            'total_price' => 120000,
            'is_dispensed' => false,
        ]);
    }

    /**
     * Test pharmacy staff can view pending prescriptions.
     */
    public function test_pharmacy_staff_can_view_pending_prescriptions(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/prescriptions');

        $response->assertStatus(200);
        $response->assertSee($this->prescription->prescription_number);
    }

    /**
     * Test pharmacy staff can receive prescription.
     */
    public function test_pharmacy_staff_can_receive_prescription(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->get("/admin/pharmacy/prescriptions/{$this->prescription->id}");

        $response->assertStatus(200);
        $response->assertSee($this->prescription->prescription_number);
        $response->assertSee('Paracetamol');
        $response->assertSee('Amoxicillin');
    }

    /**
     * Test pharmacy staff can verify prescription.
     */
    public function test_pharmacy_staff_can_verify_prescription(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/verify", [
                'notes' => 'Resep telah diverifikasi',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->prescription->refresh();
        $this->assertTrue($this->prescription->verified_by_pharmacist);
        $this->assertNotNull($this->prescription->verified_at);
        $this->assertEquals($this->pharmacist->id, $this->prescription->dispensed_by);
    }

    /**
     * Test pharmacy staff can process medicines.
     */
    public function test_pharmacy_staff_can_process_medicines(): void
    {
        // First verify prescription
        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/process");

        $response->assertRedirect();

        $this->prescription->refresh();
        $this->assertEquals('processing', $this->prescription->status);
    }

    /**
     * Test pharmacy staff can dispense medicines to patient.
     */
    public function test_pharmacy_staff_can_dispense_medicines_to_patient(): void
    {
        // Verify and process prescription
        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10,
                    ],
                    [
                        'item_id' => $this->prescription->items[1]->id,
                        'dispensed_quantity' => 15,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->prescription->refresh();
        $this->assertEquals('completed', $this->prescription->status);
        $this->assertNotNull($this->prescription->dispensed_at);
        $this->assertEquals($this->pharmacist->id, $this->prescription->dispensed_by);

        foreach ($this->prescription->items as $item) {
            $item->refresh();
            $this->assertTrue($item->is_dispensed);
            $this->assertNotNull($item->dispensed_at);
        }
    }

    /**
     * Test stock is reduced when medicines are dispensed.
     */
    public function test_stock_is_reduced_when_medicines_are_dispensed(): void
    {
        $initialStock1 = $this->medicine1->stock;
        $initialStock2 = $this->medicine2->stock;

        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'status' => 'processing',
        ]);

        $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10,
                    ],
                    [
                        'item_id' => $this->prescription->items[1]->id,
                        'dispensed_quantity' => 15,
                    ],
                ],
            ]);

        $this->medicine1->refresh();
        $this->medicine2->refresh();

        $this->assertEquals($initialStock1 - 10, $this->medicine1->stock);
        $this->assertEquals($initialStock2 - 15, $this->medicine2->stock);
    }

    /**
     * Test cannot dispense more than available stock.
     */
    public function test_cannot_dispense_more_than_available_stock(): void
    {
        $this->medicine1->update(['stock' => 5]); // Only 5 available

        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10, // Trying to dispense 10
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();

        $this->medicine1->refresh();
        $this->assertEquals(5, $this->medicine1->stock); // Stock unchanged
    }

    /**
     * Test prescription must be verified before dispensing.
     */
    public function test_prescription_must_be_verified_before_dispensing(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10,
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test pharmacy staff can reject prescription with reason.
     */
    public function test_pharmacy_staff_can_reject_prescription_with_reason(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/reject", [
                'rejection_reason' => 'Obat tidak tersedia',
            ]);

        $response->assertRedirect();

        $this->prescription->refresh();
        $this->assertEquals('rejected', $this->prescription->status);
    }

    /**
     * Test pharmacy staff can substitute medicine with doctor approval.
     */
    public function test_pharmacy_staff_can_substitute_medicine_with_doctor_approval(): void
    {
        $substituteMedicine = Medicine::factory()->create([
            'name' => 'Ibuprofen 400mg',
            'generic_name' => 'Ibuprofen',
            'stock' => 50,
            'is_active' => true,
        ]);

        $this->prescription->items[0]->update([
            'is_substitutable' => true,
        ]);

        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/substitute", [
                'original_item_id' => $this->prescription->items[0]->id,
                'substitute_medicine_id' => $substituteMedicine->id,
                'substitution_notes' => 'Paracetamol habis, diganti dengan Ibuprofen',
            ]);

        $response->assertRedirect();

        $this->prescription->items[0]->refresh();
        $this->assertEquals($substituteMedicine->id, $this->prescription->items[0]->medicine_id);
        $this->assertEquals('Ibuprofen', $this->prescription->items[0]->generic_name);
        $this->assertNotNull($this->prescription->items[0]->substitution_notes);
    }

    /**
     * Test pharmacy staff can view medicine stock.
     */
    public function test_pharmacy_staff_can_view_medicine_stock(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/medicines');

        $response->assertStatus(200);
        $response->assertSee('Paracetamol 500mg');
        $response->assertSee('100'); // Stock quantity
    }

    /**
     * Test pharmacy staff can search medicines.
     */
    public function test_pharmacy_staff_can_search_medicines(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/medicines?search=Paracetamol');

        $response->assertStatus(200);
        $response->assertSee('Paracetamol 500mg');
    }

    /**
     * Test low stock medicines are flagged.
     */
    public function test_low_stock_medicines_are_flagged(): void
    {
        $this->medicine1->update([
            'stock' => 5,
            'min_stock' => 10,
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/medicines/low-stock');

        $response->assertStatus(200);
        $response->assertSee('Paracetamol 500mg');
    }

    /**
     * Test expired medicines cannot be dispensed.
     */
    public function test_expired_medicines_cannot_be_dispensed(): void
    {
        $this->medicine1->update([
            'expired_date' => now()->subDay(),
        ]);

        $this->assertTrue($this->medicine1->is_expired);

        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors();
    }

    /**
     * Test prescription total cost calculation.
     */
    public function test_prescription_total_cost_calculation(): void
    {
        $totalCost = $this->prescription->total_estimated_cost;

        $expectedTotal = (10 * 5000) + (15 * 8000); // 50,000 + 120,000 = 170,000
        $this->assertEquals($expectedTotal, $totalCost);
    }

    /**
     * Test pharmacy staff can view prescription history.
     */
    public function test_pharmacy_staff_can_view_prescription_history(): void
    {
        $this->prescription->update([
            'status' => 'completed',
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'dispensed_at' => now(),
            'dispensed_by' => $this->pharmacist->id,
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/prescriptions/history');

        $response->assertStatus(200);
        $response->assertSee($this->prescription->prescription_number);
    }

    /**
     * Test urgent prescriptions are prioritized.
     */
    public function test_urgent_prescriptions_are_prioritized(): void
    {
        $urgentPrescription = Prescription::factory()->create([
            'prescription_number' => 'RX' . now()->format('Ymd') . '002',
            'patient_id' => $this->patient->id,
            'visit_id' => $this->visit->id,
            'medical_record_id' => $this->medicalRecord->id,
            'prescription_date' => now(),
            'priority' => 'urgent',
            'status' => 'pending',
            'prescribed_by' => $this->doctor->id,
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->get('/admin/pharmacy/prescriptions?priority=urgent');

        $response->assertStatus(200);
        $response->assertSee($urgentPrescription->prescription_number);
    }

    /**
     * Test partial dispensing is recorded correctly.
     */
    public function test_partial_dispensing_is_recorded_correctly(): void
    {
        $this->prescription->update([
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 5, // Partial quantity
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->prescription->items[0]->refresh();
        $this->assertTrue($this->prescription->items[0]->is_partially_dispensed);
        $this->assertEquals(5, $this->prescription->items[0]->dispensed_quantity);
    }

    /**
     * Test prescription items show dosage instructions.
     */
    public function test_prescription_items_show_dosage_instructions(): void
    {
        $this->prescription->items[0]->update([
            'dosage_instructions' => '3 x 1 tablet',
            'frequency' => '3 kali sehari',
            'duration_days' => 3,
            'route_of_administration' => 'oral',
        ]);

        $formattedDosage = $this->prescription->items[0]->formatted_dosage;

        $this->assertStringContainsString('3 x 1 tablet', $formattedDosage);
        $this->assertStringContainsString('3 kali sehari', $formattedDosage);
        $this->assertStringContainsString('via oral', $formattedDosage);
        $this->assertStringContainsString('for 3 days', $formattedDosage);
    }

    /**
     * Test non-pharmacy staff cannot access pharmacy routes.
     */
    public function test_non_pharmacy_staff_cannot_access_pharmacy_routes(): void
    {
        $response = $this->actingAs($this->doctorUser)
            ->get('/admin/pharmacy/prescriptions');

        $response->assertStatus(403);
    }

    /**
     * Test pharmacy staff can update stock manually.
     */
    public function test_pharmacy_staff_can_update_stock_manually(): void
    {
        $response = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/medicines/{$this->medicine1->id}/update-stock", [
                'quantity' => 50,
                'type' => 'in',
                'reason' => 'Penerimaan dari supplier',
            ]);

        $response->assertRedirect();

        $this->medicine1->refresh();
        $this->assertEquals(150, $this->medicine1->stock); // 100 + 50
    }

    /**
     * Test complete pharmacy workflow.
     */
    public function test_complete_pharmacy_workflow(): void
    {
        // 1. View pending prescription
        $viewResponse = $this->actingAs($this->pharmacyUser)
            ->get("/admin/pharmacy/prescriptions/{$this->prescription->id}");
        $viewResponse->assertStatus(200);

        // 2. Verify prescription
        $verifyResponse = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/verify");
        $verifyResponse->assertRedirect();

        $this->prescription->refresh();
        $this->assertTrue($this->prescription->verified_by_pharmacist);

        // 3. Process prescription
        $processResponse = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/process");
        $processResponse->assertRedirect();

        // 4. Dispense medicines
        $initialStock1 = $this->medicine1->stock;
        $initialStock2 = $this->medicine2->stock;

        $dispenseResponse = $this->actingAs($this->pharmacyUser)
            ->post("/admin/pharmacy/prescriptions/{$this->prescription->id}/dispense", [
                'dispensed_items' => [
                    [
                        'item_id' => $this->prescription->items[0]->id,
                        'dispensed_quantity' => 10,
                    ],
                    [
                        'item_id' => $this->prescription->items[1]->id,
                        'dispensed_quantity' => 15,
                    ],
                ],
            ]);
        $dispenseResponse->assertRedirect();

        // 5. Verify stock reduction
        $this->medicine1->refresh();
        $this->medicine2->refresh();
        $this->assertEquals($initialStock1 - 10, $this->medicine1->stock);
        $this->assertEquals($initialStock2 - 15, $this->medicine2->stock);

        // 6. Verify prescription status
        $this->prescription->refresh();
        $this->assertEquals('completed', $this->prescription->status);
        $this->assertNotNull($this->prescription->dispensed_at);
    }
}
