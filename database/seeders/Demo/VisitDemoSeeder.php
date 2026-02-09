<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Visit Seeder.
 *
 * Creates 200 sample visits with:
 * - Different polyclinics
 * - Various statuses (registered, waiting, in_progress, completed)
 * - Date range: last 30 days
 * - Visit types: rawat_jalan, igd, rawat_inap
 * - Associated doctors and complaints
 *
 * @package Database\Seeders\Demo
 */
class VisitDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $patients = Patient::all();
        $polyclinics = Polyclinic::all();
        $doctors = Employee::where('is_doctor', true)->get();

        if ($patients->isEmpty()) {
            $this->command->warn('  ! No patients found. Skipping visit seeding.');
            return;
        }

        if ($polyclinics->isEmpty()) {
            $this->command->warn('  ! No polyclinics found. Skipping visit seeding.');
            return;
        }

        if ($doctors->isEmpty()) {
            $this->command->warn('  ! No doctors found. Using null doctor_id.');
        }

        $visits = [];
        $now = now();

        for ($i = 1; $i <= 200; $i++) {
            $visits[] = $this->generateVisitData($i, $patients, $polyclinics, $doctors, $now);
        }

        // Insert in chunks for better performance
        foreach (array_chunk($visits, 50) as $chunk) {
            DB::table('visits')->insert($chunk);
        }

        $this->command->info('  ✓ Created 200 demo visits');
    }

    /**
     * Generate visit data for a single visit.
     *
     * @param int $index
     * @param $patients
     * @param $polyclinics
     * @param $doctors
     * @param Carbon $now
     * @return array
     */
    protected function generateVisitData(
        int $index,
        $patients,
        $polyclinics,
        $doctors,
        Carbon $now
    ): array {
        $visitType = $this->getRandomVisitType();
        $status = $this->getRandomStatus();
        $visitDate = $now->copy()->subDays(rand(0, 30));
        $patient = $patients->random();

        // Add some randomness to time
        $visitDate->setTime(rand(7, 20), rand(0, 59));

        $checkInAt = in_array($status, ['waiting', 'in_progress', 'completed']) ? $visitDate->copy() : null;
        $checkOutAt = $status === 'completed' ? $visitDate->copy()->addMinutes(rand(15, 180)) : null;

        return [
            'visit_number' => $this->generateVisitNumber($index, $visitDate),
            'patient_id' => $patient->id,
            'polyclinic_id' => $polyclinics->random()->id,
            'doctor_id' => $doctors->isNotEmpty() ? $doctors->random()->id : null,
            'visit_date' => $visitDate->format('Y-m-d'),
            'visit_type' => $visitType,
            'registration_type' => $this->getRandomRegistrationType(),
            'priority' => $this->getRandomPriority(),
            'status' => $status,
            'complaint' => $this->getRandomComplaint(),
            'referral_from' => rand(0, 100) > 80 ? $this->getRandomReferral() : null,
            'referral_number' => rand(0, 100) > 80 ? 'R/' . date('Y') . '/' . str_pad((string) $index, 5, '0', STR_PAD_LEFT) : null,
            'bpjs_sep_number' => $patient->insurance_type === 'bpjs' && rand(0, 100) > 30
                ? $this->generateSEPNumber($index)
                : null,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'is_completed' => $status === 'completed',
            'notes' => rand(0, 100) > 90 ? fake()->sentence() : null,
            'created_at' => $visitDate,
            'updated_at' => $visitDate,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate visit number.
     *
     * @param int $index
     * @param Carbon $date
     * @return string
     */
    protected function generateVisitNumber(int $index, Carbon $date): string
    {
        return 'V-' . $date->format('Ymd') . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate BPJS SEP number.
     *
     * @param int $index
     * @return string
     */
    protected function generateSEPNumber(int $index): string
    {
        return date('Y') . str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT) . str_pad((string) $index, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get random visit type.
     *
     * @return string
     */
    protected function getRandomVisitType(): string
    {
        $types = [
            'rawat_jalan' => 70,  // 70% outpatient
            'igd' => 20,          // 20% emergency
            'rawat_inap' => 10,   // 10% inpatient
        ];

        return $this->weightedRandom($types);
    }

    /**
     * Get random registration type.
     *
     * @return string
     */
    protected function getRandomRegistrationType(): string
    {
        $types = [
            'walkin' => 60,
            'online' => 25,
            'referral' => 10,
            'emergency' => 5,
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
            'normal' => 70,
            'urgent' => 20,
            'emergency' => 10,
        ];

        return $this->weightedRandom($priorities);
    }

    /**
     * Get random status.
     *
     * @return string
     */
    protected function getRandomStatus(): string
    {
        $statuses = [
            'registered' => 10,
            'waiting' => 20,
            'in_progress' => 25,
            'completed' => 45,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random complaint.
     *
     * @return string
     */
    protected function getRandomComplaint(): string
    {
        $complaints = [
            'Demam dan batuk',
            'Sakit kepala',
            'Sakit perut',
            'Nyeri dada',
            'Sesak napas',
            'Mual dan muntah',
            'Sakit tenggorokan',
            'Nyeri punggung',
            'Gatal-gatal',
            'Pusing dan lemas',
            'Diare',
            'Sakit gigi',
            'Cedera olahraga',
            'Luka memar',
            'Sakit sendi',
            'Batuk berdahak',
            'Nyeri perut bagian bawah',
            'Sakit mata merah',
            'Telinga berdenging',
            'Kontrol rutin',
            'Konsultasi kesehatan',
            'Check-up tahunan',
            'Sakit leher',
            'Nyeri otot',
            'Bengkak di kaki',
        ];

        return $complaints[array_rand($complaints)];
    }

    /**
     * Get random referral source.
     *
     * @return string
     */
    protected function getRandomReferral(): string
    {
        $referrals = [
            'Puskesmas Sehat',
            'Klinik Medika',
            'RSUD Kota',
            'Dokter Umum Dr. Budi',
            'Klinik Pratama Anugerah',
            'Puskesmas Harapan',
            'Klinik Sehat Sentosa',
        ];

        return $referrals[array_rand($referrals)];
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
