<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Inpatient Seeder.
 *
 * Creates inpatient data with:
 * - 20 admitted patients
 * - Various room classes
 * - Different bed assignments
 * - Different length of stay (LOS)
 * - Admission and discharge tracking
 *
 * @package Database\Seeders\Demo
 */
class InpatientDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Get visits with rawat_inap type
        $inpatientVisits = Visit::where('visit_type', 'rawat_inap')
            ->where('status', '!=', 'registered')
            ->get();

        $rooms = Room::all();
        $beds = Bed::where('status', 'kosong')->orWhereNull('current_visit_id')->get();

        if ($inpatientVisits->isEmpty()) {
            $this->command->warn('  ! No inpatient visits found. Skipping inpatient seeding.');
            return;
        }

        if ($rooms->isEmpty()) {
            $this->command->warn('  ! No rooms found. Skipping inpatient seeding.');
            return;
        }

        $admissions = [];
        $now = now();
        $limit = min(20, $inpatientVisits->count());

        foreach ($inpatientVisits->take($limit) as $index => $visit) {
            $admissions[] = $this->generateAdmissionData($index, $visit, $rooms, $now);

            // Update visit bed assignment
            $room = $rooms->random();
            Bed::where('room_id', $room->id)
                ->where('status', 'kosong')
                ->first()?->update([
                    'status' => 'terisi',
                    'current_visit_id' => $visit->id,
                    'occupied_at' => $visit->visit_date,
                ]);
        }

        // Insert admissions
        foreach (array_chunk($admissions, 20) as $chunk) {
            DB::table('inpatient_admissions')->insert($chunk);
        }

        $this->command->info('  ✓ Created ' . count($admissions) . ' demo inpatient admissions');
    }

    /**
     * Generate admission data.
     *
     * @param int $index
     * @param Visit $visit
     * @param $rooms
     * @param Carbon $now
     * @return array
     */
    protected function generateAdmissionData(int $index, Visit $visit, $rooms, Carbon $now): array
    {
        $room = $rooms->random();
        $admissionDate = $visit->visit_date->copy();
        $dischargeDate = $this->getRandomDischargeDate($admissionDate);

        $lengthOfStay = $dischargeDate
            ? $admissionDate->diffInDays($dischargeDate)
            : $admissionDate->diffInDays(now());

        return [
            'admission_number' => $this->generateAdmissionNumber($index),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'room_id' => $room->id,
            'bed_id' => null, // Will be set by bed assignment
            'admission_date' => $admissionDate,
            'admission_time' => $admissionDate->copy()->setTime(rand(8, 22), rand(0, 59)),
            'discharge_date' => $dischargeDate,
            'discharge_time' => $dischargeDate?->copy()->setTime(rand(8, 18), rand(0, 59)),
            'discharge_status' => $dischargeDate ? $this->getRandomDischargeStatus() : null,
            'length_of_stay' => $lengthOfStay,
            'admission_diagnosis' => $this->getRandomDiagnosis(),
            'discharge_diagnosis' => $dischargeDate ? $this->getRandomDiagnosis() : null,
            'attending_doctor_id' => $visit->doctor_id,
            'referring_doctor_id' => rand(0, 100) > 70 ? $visit->doctor_id : null,
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => $this->generatePhone(),
            'is_active' => $dischargeDate === null,
            'notes' => rand(0, 100) > 85 ? 'Pasien membutuhkan perhatian khusus' : null,
            'created_at' => $admissionDate,
            'updated_at' => $now,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate admission number.
     *
     * @param int $index
     * @return string
     */
    protected function generateAdmissionNumber(int $index): string
    {
        return 'RI-' . date('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate phone number.
     *
     * @return string
     */
    protected function generatePhone(): string
    {
        $prefixes = ['0812', '0813', '0821', '0822', '0852', '0853', '0811', '0814', '0815', '0816'];
        $prefix = $prefixes[array_rand($prefixes)];

        return $prefix . substr(str_shuffle('0123456789'), 0, 8);
    }

    /**
     * Get random discharge date.
     *
     * @param Carbon $admissionDate
     * @return Carbon|null
     */
    protected function getRandomDischargeDate(Carbon $admissionDate): ?Carbon
    {
        // 30% still admitted, 70% discharged
        if (rand(0, 100) <= 30) {
            return null;
        }

        // LOS between 1-14 days
        $los = rand(1, 14);

        return $admissionDate->copy()->addDays($los);
    }

    /**
     * Get random discharge status.
     *
     * @return string
     */
    protected function getRandomDischargeStatus(): string
    {
        $statuses = [
            'pulang' => 70,
            'rujuk' => 15,
            'meninggal' => 5,
            'paksa' => 10,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random diagnosis.
     *
     * @return string
     */
    protected function getRandomDiagnosis(): string
    {
        $diagnoses = [
            'Pneumonia',
            'Dengue Fever',
            'Typhoid Fever',
            'Acute Appendicitis',
            'Fracture Femur',
            'Cerebral Infarction',
            'Acute Myocardial Infarction',
            'Diabetic Ketoacidosis',
            'Acute Gastritis',
            'Bronchial Asthma',
            'Congestive Heart Failure',
            'Urinary Tract Infection',
            'Sepsis',
            'Pre-eclampsia',
            'Labor Pain',
        ];

        return $diagnoses[array_rand($diagnoses)];
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
