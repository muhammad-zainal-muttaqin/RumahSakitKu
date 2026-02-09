<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Medicine;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Prescription Seeder.
 *
 * Creates prescriptions with:
 * - Various medicines
 * - Different statuses (pending, verified, processed, dispensed)
 * - Prescription items with dosages
 * - Various prescription types
 *
 * @package Database\Seeders\Demo
 */
class PrescriptionDemoSeeder extends Seeder
{
    /**
     * Sample medicine data for prescriptions.
     *
     * @var array
     */
    protected array $sampleMedicines = [
        ['name' => 'Paracetamol 500mg', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Setelah makan'],
        ['name' => 'Amoxicillin 500mg', 'unit' => 'kapsul', 'dosage' => '3x1', 'instruction' => 'Sebelum makan'],
        ['name' => 'Ibuprofen 400mg', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Sesudah makan'],
        ['name' => 'Cetirizine 10mg', 'unit' => 'tablet', 'dosage' => '1x1', 'instruction' => 'Sebelum tidur'],
        ['name' => 'Ranitidine 150mg', 'unit' => 'tablet', 'dosage' => '2x1', 'instruction' => 'Sebelum makan'],
        ['name' => 'Metformin 500mg', 'unit' => 'tablet', 'dosage' => '2x1', 'instruction' => 'Saat makan'],
        ['name' => 'Amlodipine 5mg', 'unit' => 'tablet', 'dosage' => '1x1', 'instruction' => 'Pagi hari'],
        ['name' => 'Simvastatin 20mg', 'unit' => 'tablet', 'dosage' => '1x1', 'instruction' => 'Malam hari'],
        ['name' => 'Salbutamol 2mg', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Saat sesak'],
        ['name' => 'Omeprazole 20mg', 'unit' => 'kapsul', 'dosage' => '1x1', 'instruction' => 'Sebelum makan pagi'],
        ['name' => 'Dexamethasone 0.5mg', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Setelah makan'],
        ['name' => 'Vitamin C 500mg', 'unit' => 'tablet', 'dosage' => '1x1', 'instruction' => 'Setelah makan'],
        ['name' => 'Multivitamin', 'unit' => 'tablet', 'dosage' => '1x1', 'instruction' => 'Setelah makan'],
        ['name' => 'Ambroxol 30mg', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Setelah makan'],
        ['name' => 'Antasida DOEN', 'unit' => 'tablet', 'dosage' => '3x1', 'instruction' => 'Sebelum makan'],
        ['name' => 'Loperamide 2mg', 'unit' => 'tablet', 'dosage' => '2x1', 'instruction' => 'Setelah BAB'],
        ['name' => 'Oralit', 'unit' => 'sachet', 'dosage' => 'Sesuai kebutuhan', 'instruction' => 'Larutkan dalam air'],
        ['name' => 'Betadine', 'unit' => 'botol', 'dosage' => '3x sehari', 'instruction' => 'Oleskan pada luka'],
        ['name' => 'Salep 88', 'unit' => 'tube', 'dosage' => '3x sehari', 'instruction' => 'Oles tipis-tipis'],
        ['name' => 'Salep mata chloramfenikol', 'unit' => 'tube', 'dosage' => '3x sehari', 'instruction' => 'Oleskan pada kelopak mata'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $visits = Visit::all();
        $medicalRecords = MedicalRecord::all();
        $doctors = Employee::where('is_doctor', true)->get();
        $medicines = Medicine::all();

        if ($visits->isEmpty()) {
            $this->command->warn('  ! No visits found. Skipping prescription seeding.');
            return;
        }

        $prescriptions = [];
        $prescriptionItems = [];
        $now = now();
        $itemIndex = 1;

        // Create prescriptions for visits with in_progress or completed status
        foreach ($visits as $index => $visit) {
            if (!in_array($visit->status, ['in_progress', 'completed'])) {
                continue;
            }

            // 80% chance to have a prescription
            if (rand(0, 100) > 80) {
                continue;
            }

            $prescription = $this->generatePrescriptionData($index, $visit, $medicalRecords, $doctors, $now);
            $prescriptions[] = $prescription;

            // Generate 1-5 prescription items
            $itemCount = rand(1, 5);
            for ($i = 0; $i < $itemCount; $i++) {
                $prescriptionItems[] = $this->generatePrescriptionItemData(
                    $itemIndex++,
                    $prescription['prescription_number'],
                    $medicines
                );
            }
        }

        // Insert prescriptions
        foreach (array_chunk($prescriptions, 50) as $chunk) {
            DB::table('prescriptions')->insert($chunk);
        }

        // Insert prescription items
        if (!empty($prescriptionItems)) {
            foreach (array_chunk($prescriptionItems, 50) as $chunk) {
                DB::table('prescription_items')->insert($chunk);
            }
        }

        $this->command->info('  ✓ Created ' . count($prescriptions) . ' demo prescriptions');
    }

    /**
     * Generate prescription data.
     *
     * @param int $index
     * @param Visit $visit
     * @param $medicalRecords
     * @param $doctors
     * @param Carbon $now
     * @return array
     */
    protected function generatePrescriptionData(
        int $index,
        Visit $visit,
        $medicalRecords,
        $doctors,
        Carbon $now
    ): array {
        $status = $this->getRandomStatus();
        $doctorId = $doctors->isNotEmpty() ? $doctors->random()->id : $visit->doctor_id;

        // Find related medical record
        $medicalRecord = $medicalRecords->where('visit_id', $visit->id)->first();

        $verifiedAt = in_array($status, ['verified', 'dispensed']) ? $visit->visit_date->copy()->addMinutes(rand(10, 60)) : null;
        $dispensedAt = $status === 'dispensed' ? $visit->visit_date->copy()->addMinutes(rand(30, 120)) : null;

        return [
            'prescription_number' => $this->generatePrescriptionNumber($index),
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'medical_record_id' => $medicalRecord?->id,
            'prescription_date' => $visit->visit_date,
            'prescription_type' => $this->getRandomPrescriptionType(),
            'priority' => $this->getRandomPriority(),
            'status' => $status,
            'clinical_indication' => rand(0, 100) > 70 ? $visit->complaint : null,
            'allergies' => rand(0, 100) > 90 ? 'Alergi Paracetamol' : null,
            'prescribed_by' => $doctorId,
            'verified_by_pharmacist' => in_array($status, ['verified', 'dispensed']),
            'verified_at' => $verifiedAt,
            'dispensed_at' => $dispensedAt,
            'dispensed_by' => $dispensedAt ? rand(1, 5) : null,
            'notes' => rand(0, 100) > 85 ? 'Obat harus dihabiskan' : null,
            'created_at' => $visit->visit_date,
            'updated_at' => $visit->visit_date,
            'created_by' => $doctorId ?? 1,
            'updated_by' => $doctorId ?? 1,
        ];
    }

    /**
     * Generate prescription item data.
     *
     * @param int $index
     * @param string $prescriptionNumber
     * @param $medicines
     * @return array
     */
    protected function generatePrescriptionItemData(int $index, string $prescriptionNumber, $medicines): array
    {
        $sampleMedicine = $this->sampleMedicines[array_rand($this->sampleMedicines)];

        $medicine = $medicines->isNotEmpty()
            ? $medicines->random()
            : null;

        $quantity = rand(1, 30);
        $unitPrice = $medicine?->selling_price ?? rand(500, 50000);

        return [
            'prescription_number' => $prescriptionNumber,
            'medicine_id' => $medicine?->id,
            'medicine_name' => $medicine?->name ?? $sampleMedicine['name'],
            'medicine_code' => $medicine?->code ?? 'MED' . str_pad((string) $index, 5, '0', STR_PAD_LEFT),
            'quantity' => $quantity,
            'unit' => $medicine?->unit ?? $sampleMedicine['unit'],
            'dosage' => $sampleMedicine['dosage'],
            'frequency' => $this->getRandomFrequency(),
            'instruction' => $sampleMedicine['instruction'],
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'notes' => rand(0, 100) > 90 ? 'Simpan di tempat sejuk' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate prescription number.
     *
     * @param int $index
     * @return string
     */
    protected function generatePrescriptionNumber(int $index): string
    {
        return 'R/' . date('Y') . '/' . str_pad((string) $index, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get random status.
     *
     * @return string
     */
    protected function getRandomStatus(): string
    {
        $statuses = [
            'pending' => 20,
            'verified' => 25,
            'dispensed' => 55,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random prescription type.
     *
     * @return string
     */
    protected function getRandomPrescriptionType(): string
    {
        $types = [
            'regular' => 70,
            'copy' => 10,
            'iter' => 20,
        ];

        return $this->weightedRandom($types);
    }

    /**
     * Get random priority.
     *
     * @return string
     */
    protected function getRandomPriority(): string
    {
        $priorities = [
            'normal' => 80,
            'urgent' => 15,
            'emergency' => 5,
        ];

        return $this->weightedRandom($priorities);
    }

    /**
     * Get random frequency.
     *
     * @return string
     */
    protected function getRandomFrequency(): string
    {
        $frequencies = ['1x sehari', '2x sehari', '3x sehari', '4x sehari', 'Sesuai kebutuhan'];

        return $frequencies[array_rand($frequencies)];
    }

    /**
     * Get weighted random value.
     *
     * @param array $items
     * @return string
     */
    protected function weightedRandom(array $items): string
    {
        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($items as $item => $probability) {
            $cumulative += $probability;
            if ($random <= $cumulative) {
                return $item;
            }
        }

        return array_key_first($items);
    }
}
