<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Clinical\Surgery;
use App\Models\MasterData\Employee;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Surgery Seeder.
 *
 * Creates surgery schedules with:
 * - Different surgery types
 * - Various statuses (scheduled, in_progress, completed, cancelled)
 * - Surgical team assignments
 * - Safety checklist tracking
 *
 * @package Database\Seeders\Demo
 */
class SurgeryDemoSeeder extends Seeder
{
    /**
     * Surgery procedures.
     *
     * @var array
     */
    protected array $procedures = [
        ['name' => 'Appendectomy', 'code' => '47.0', 'type' => 'cito'],
        ['name' => 'Cesarean Section', 'code' => '74.1', 'type' => 'urgent'],
        ['name' => 'Cholecystectomy', 'code' => '51.2', 'type' => 'elektif'],
        ['name' => 'Hernia Repair', 'code' => '53.0', 'type' => 'elektif'],
        ['name' => 'Laparotomy', 'code' => '54.1', 'type' => 'urgent'],
        ['name' => 'Hysterectomy', 'code' => '68.4', 'type' => 'elektif'],
        ['name' => 'Mastectomy', 'code' => '85.4', 'type' => 'elektif'],
        ['name' => 'Thyroidectomy', 'code' => '06.4', 'type' => 'elektif'],
        ['name' => 'Tonsillectomy', 'code' => '28.2', 'type' => 'elektif'],
        ['name' => 'Hemorrhoidectomy', 'code' => '49.5', 'type' => 'elektif'],
        ['name' => 'Wound Debridement', 'code' => '86.2', 'type' => 'urgent'],
        ['name' => 'Fracture Fixation', 'code' => '79.3', 'type' => 'urgent'],
        ['name' => 'Amputation', 'code' => '84.1', 'type' => 'emergency'],
        ['name' => 'Tracheostomy', 'code' => '31.1', 'type' => 'emergency'],
        ['name' => 'Cataract Extraction', 'code' => '13.4', 'type' => 'elektif'],
    ];

    /**
     * Operating rooms.
     *
     * @var array
     */
    protected array $operatingRooms = ['OK1', 'OK2', 'OK3', 'OK4', 'OK5', 'OK_CITO'];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $visits = Visit::whereIn('visit_type', ['rawat_inap', 'igd'])->get();
        $doctors = Employee::where('is_doctor', true)->get();
        $nurses = Employee::where('is_nurse', true)->get();

        if ($visits->isEmpty()) {
            $this->command->warn('  ! No suitable visits found. Skipping surgery seeding.');
            return;
        }

        $surgeries = [];
        $now = now();

        // Create 15-25 surgeries
        $surgeryCount = min(rand(15, 25), $visits->count());

        foreach ($visits->take($surgeryCount) as $index => $visit) {
            $surgeries[] = $this->generateSurgeryData($index, $visit, $doctors, $nurses, $now);
        }

        // Insert surgeries
        foreach (array_chunk($surgeries, 25) as $chunk) {
            DB::table('surgeries')->insert($chunk);
        }

        $this->command->info('  ✓ Created ' . count($surgeries) . ' demo surgeries');
    }

    /**
     * Generate surgery data.
     *
     * @param int $index
     * @param Visit $visit
     * @param $doctors
     * @param $nurses
     * @param Carbon $now
     * @return array
     */
    protected function generateSurgeryData(int $index, Visit $visit, $doctors, $nurses, Carbon $now): array
    {
        $procedure = $this->procedures[array_rand($this->procedures)];
        $status = $this->getRandomStatus();
        $scheduledDate = $this->getScheduledDate($visit->visit_date, $procedure['type']);

        $surgeon = $doctors->isNotEmpty() ? $doctors->random() : null;
        $assistantSurgeon = $doctors->isNotEmpty() && rand(0, 100) > 50 ? $doctors->random() : null;
        $anesthesiologist = $doctors->isNotEmpty() && rand(0, 100) > 30 ? $doctors->random() : null;
        $nurse = $nurses->isNotEmpty() ? $nurses->random() : null;
        $circulatingNurse = $nurses->isNotEmpty() && rand(0, 100) > 40 ? $nurses->random() : null;

        $startTime = $scheduledDate->copy()->setTime(rand(8, 16), rand(0, 59));
        $estimatedDuration = rand(60, 240); // minutes
        $estimatedEndTime = $startTime->copy()->addMinutes($estimatedDuration);

        $actualStart = in_array($status, ['in_progress', 'completed']) ? $startTime : null;
        $actualEnd = $status === 'completed' ? $startTime->copy()->addMinutes(rand(45, $estimatedDuration + 30)) : null;

        // Safety checklist
        $safetyChecklist = $this->generateSafetyChecklist($status);

        return [
            'surgery_number' => $this->generateSurgeryNumber($index),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'scheduled_date' => $scheduledDate,
            'start_time' => $startTime,
            'estimated_end_time' => $estimatedEndTime,
            'actual_start' => $actualStart,
            'actual_end' => $actualEnd,
            'operating_room' => $this->operatingRooms[array_rand($this->operatingRooms)],
            'surgeon_id' => $surgeon?->id,
            'assistant_surgeon_id' => $assistantSurgeon?->id,
            'anesthesiologist_id' => $anesthesiologist?->id,
            'anesthesia_type' => $this->getRandomAnesthesiaType(),
            'nurse_id' => $nurse?->id,
            'circulating_nurse_id' => $circulatingNurse?->id,
            'pre_diagnosis' => $this->getRandomDiagnosis(),
            'post_diagnosis' => $status === 'completed' ? $this->getRandomDiagnosis() : null,
            'procedure_name' => $procedure['name'],
            'procedure_code' => $procedure['code'],
            'surgery_type' => $procedure['type'],
            'status' => $status,
            'safety_checklist_sign_in' => $safetyChecklist['sign_in'],
            'safety_checklist_sign_in_at' => $safetyChecklist['sign_in_at'],
            'safety_checklist_time_out' => $safetyChecklist['time_out'],
            'safety_checklist_time_out_at' => $safetyChecklist['time_out_at'],
            'safety_checklist_sign_out' => $safetyChecklist['sign_out'],
            'safety_checklist_sign_out_at' => $safetyChecklist['sign_out_at'],
            'procedure_notes' => $status === 'completed' ? $this->generateProcedureNotes() : null,
            'findings' => $status === 'completed' ? $this->generateFindings() : null,
            'complications' => $status === 'completed' && rand(0, 100) > 90 ? 'Pendarahan minimal' : null,
            'specimens' => rand(0, 100) > 70 ? 'Dikirim ke patologi' : null,
            'is_postponed' => false,
            'cancelled_at' => $status === 'cancelled' ? $startTime : null,
            'cancellation_reason' => $status === 'cancelled' ? 'Kondisi pasien tidak memungkinkan' : null,
            'notes' => rand(0, 100) > 85 ? 'Pembuluh darah varian' : null,
            'created_at' => $scheduledDate->copy()->subDays(rand(1, 7)),
            'updated_at' => $now,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate surgery number.
     *
     * @param int $index
     * @return string
     */
    protected function generateSurgeryNumber(int $index): string
    {
        return 'OK-' . date('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get scheduled date based on surgery type.
     *
     * @param Carbon $visitDate
     * @param string $type
     * @return Carbon
     */
    protected function getScheduledDate(Carbon $visitDate, string $type): Carbon
    {
        return match ($type) {
            'emergency', 'cito' => $visitDate->copy(),
            'urgent' => $visitDate->copy()->addHours(rand(1, 12)),
            default => $visitDate->copy()->addDays(rand(1, 14)),
        };
    }

    /**
     * Generate safety checklist status.
     *
     * @param string $status
     * @return array
     */
    protected function generateSafetyChecklist(string $status): array
    {
        if ($status === 'scheduled') {
            return [
                'sign_in' => false,
                'sign_in_at' => null,
                'time_out' => false,
                'time_out_at' => null,
                'sign_out' => false,
                'sign_out_at' => null,
            ];
        }

        if ($status === 'cancelled') {
            return [
                'sign_in' => rand(0, 100) > 50,
                'sign_in_at' => rand(0, 100) > 50 ? now() : null,
                'time_out' => false,
                'time_out_at' => null,
                'sign_out' => false,
                'sign_out_at' => null,
            ];
        }

        if ($status === 'in_progress') {
            return [
                'sign_in' => true,
                'sign_in_at' => now(),
                'time_out' => rand(0, 100) > 50,
                'time_out_at' => rand(0, 100) > 50 ? now() : null,
                'sign_out' => false,
                'sign_out_at' => null,
            ];
        }

        // Completed
        return [
            'sign_in' => true,
            'sign_in_at' => now(),
            'time_out' => true,
            'time_out_at' => now(),
            'sign_out' => true,
            'sign_out_at' => now(),
        ];
    }

    /**
     * Get random status.
     *
     * @return string
     */
    protected function getRandomStatus(): string
    {
        $statuses = [
            'scheduled' => 30,
            'preparation' => 10,
            'in_progress' => 15,
            'completed' => 40,
            'cancelled' => 5,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random anesthesia type.
     *
     * @return string
     */
    protected function getRandomAnesthesiaType(): string
    {
        $types = ['umum', 'spinal', 'lokal', 'blok', 'sedasi', 'tiva'];

        return $types[array_rand($types)];
    }

    /**
     * Get random diagnosis.
     *
     * @return string
     */
    protected function getRandomDiagnosis(): string
    {
        $diagnoses = [
            'Acute Appendicitis',
            'Appendicitis Perforata',
            'Uterine Myoma',
            'Inguinal Hernia',
            'Cholelithiasis',
            'Breast Cancer',
            'Thyroid Adenoma',
            'Chronic Tonsillitis',
            'Hemorrhoids Grade IV',
            'Compound Fracture',
            'Cataract Senilis',
            'Ectopic Pregnancy',
            'Ovarian Cyst',
            'Perforated Peptic Ulcer',
        ];

        return $diagnoses[array_rand($diagnoses)];
    }

    /**
     * Generate procedure notes.
     *
     * @return string
     */
    protected function generateProcedureNotes(): string
    {
        $notes = [
            'Operasi berjalan lancar. Perdarahan minimal.',
            'Prosedur selesai dalam waktu estimasi.',
            'Ditemukan adherensi, dilakukan adhesiolisis.',
            'Pembuluh darah besar dijaga dengan baik.',
            'Spesimen dikirim ke laboratorium patologi.',
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * Generate findings.
     *
     * @return string
     */
    protected function generateFindings(): string
    {
        $findings = [
            'Apendiks vermiformis membengkak dan hiperemis.',
            'Tumor uterus ukuran 8x6 cm.',
            'Defek dinding perut ukuran 3x2 cm.',
            'Batu empedu multiple.',
            'Massa payudara kanan ukuran 4x3 cm.',
            'Nodus tiroid bilateral.',
            'Tonsila hipertrofi grade III.',
            'Hemoroid prolaps grade IV.',
        ];

        return $findings[array_rand($findings)];
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
