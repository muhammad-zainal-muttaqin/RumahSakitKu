<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Medical Record Seeder.
 *
 * Creates medical records for visits with:
 * - SOAP format documentation
 * - ICD10 diagnoses
 * - Primary and secondary diagnoses
 * - Finalized and draft statuses
 * - Various medical conditions
 *
 * @package Database\Seeders\Demo
 */
class MedicalRecordDemoSeeder extends Seeder
{
    /**
     * ICD10 codes with descriptions.
     *
     * @var array
     */
    protected array $icd10Codes = [
        ['code' => 'J06.9', 'description' => 'Acute upper respiratory infection, unspecified'],
        ['code' => 'R50.9', 'description' => 'Fever, unspecified'],
        ['code' => 'R51', 'description' => 'Headache'],
        ['code' => 'R10.1', 'description' => 'Pain localized to upper abdomen'],
        ['code' => 'J44.9', 'description' => 'Chronic obstructive pulmonary disease, unspecified'],
        ['code' => 'E11.9', 'description' => 'Type 2 diabetes mellitus without complications'],
        ['code' => 'I10', 'description' => 'Essential (primary) hypertension'],
        ['code' => 'K29.7', 'description' => 'Gastritis, unspecified'],
        ['code' => 'M25.5', 'description' => 'Pain in joint'],
        ['code' => 'L50.9', 'description' => 'Urticaria, unspecified'],
        ['code' => 'H10.9', 'description' => 'Conjunctivitis, unspecified'],
        ['code' => 'S93.4', 'description' => 'Sprain of ankle'],
        ['code' => 'A09', 'description' => 'Gastroenteritis and colitis of unspecified origin'],
        ['code' => 'N39.0', 'description' => 'Urinary tract infection, site not specified'],
        ['code' => 'K30', 'description' => 'Functional dyspepsia'],
        ['code' => 'J20.9', 'description' => 'Acute bronchitis, unspecified'],
        ['code' => 'B34.9', 'description' => 'Viral infection, unspecified'],
        ['code' => 'F41.9', 'description' => 'Anxiety disorder, unspecified'],
        ['code' => 'G44.2', 'description' => 'Tension-type headache'],
        ['code' => 'H66.9', 'description' => 'Otitis media, unspecified'],
        ['code' => 'M54.5', 'description' => 'Low back pain'],
        ['code' => 'K02.9', 'description' => 'Dental caries, unspecified'],
        ['code' => 'S40.0', 'description' => 'Contusion of shoulder and upper arm'],
        ['code' => 'T14.1', 'description' => 'Open wound of unspecified body region'],
        ['code' => 'Z00.0', 'description' => 'General medical examination'],
        ['code' => 'Z51.9', 'description' => 'Care involving use of unspecified rehabilitation procedure'],
        ['code' => 'O80', 'description' => 'Single spontaneous delivery'],
        ['code' => 'P07.0', 'description' => 'Extremely low birth weight'],
        ['code' => 'E66.9', 'description' => 'Obesity, unspecified'],
        ['code' => 'J45.9', 'description' => 'Asthma, unspecified'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $visits = Visit::all();
        $doctors = Employee::where('is_doctor', true)->get();

        if ($visits->isEmpty()) {
            $this->command->warn('  ! No visits found. Skipping medical record seeding.');
            return;
        }

        $records = [];
        $now = now();

        foreach ($visits as $index => $visit) {
            // Only create medical records for visits with status in_progress or completed
            if (!in_array($visit->status, ['in_progress', 'completed'])) {
                continue;
            }

            $records[] = $this->generateRecordData($index, $visit, $doctors, $now);
        }

        // Insert in chunks
        foreach (array_chunk($records, 50) as $chunk) {
            DB::table('medical_records')->insert($chunk);
        }

        $this->command->info('  ✓ Created ' . count($records) . ' demo medical records');
    }

    /**
     * Generate medical record data.
     *
     * @param int $index
     * @param Visit $visit
     * @param $doctors
     * @param Carbon $now
     * @return array
     */
    protected function generateRecordData(int $index, Visit $visit, $doctors, Carbon $now): array
    {
        $icd10 = $this->icd10Codes[array_rand($this->icd10Codes)];
        $isFinalized = $visit->status === 'completed' || (rand(0, 100) > 30);
        $doctorId = $doctors->isNotEmpty() ? $doctors->random()->id : $visit->doctor_id;

        $soap = $this->generateSOAP($visit->complaint, $icd10);

        return [
            'record_number' => $this->generateRecordNumber($index, $visit),
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'visit_date' => $visit->visit_date,
            'subjective' => $soap['subjective'],
            'objective' => $soap['objective'],
            'assessment' => $soap['assessment'],
            'plan' => $soap['plan'],
            'diagnosis_primary' => $icd10['description'],
            'diagnosis_secondary' => rand(0, 100) > 70 ? $this->icd10Codes[array_rand($this->icd10Codes)]['description'] : null,
            'icd10_code' => $icd10['code'],
            'icd10_description' => $icd10['description'],
            'procedure_code' => rand(0, 100) > 80 ? $this->generateProcedureCode() : null,
            'procedure_description' => rand(0, 100) > 80 ? $this->generateProcedureDescription() : null,
            'notes' => rand(0, 100) > 85 ? fake()->sentence() : null,
            'is_finalized' => $isFinalized,
            'finalized_at' => $isFinalized ? $visit->visit_date->copy()->addMinutes(rand(30, 120)) : null,
            'finalized_by' => $isFinalized ? $doctorId : null,
            'created_at' => $visit->created_at,
            'updated_at' => $visit->updated_at,
            'created_by' => $doctorId ?? 1,
            'updated_by' => $doctorId ?? 1,
        ];
    }

    /**
     * Generate record number.
     *
     * @param int $index
     * @param Visit $visit
     * @return string
     */
    protected function generateRecordNumber(int $index, Visit $visit): string
    {
        return 'RM-' . $visit->visit_date->format('Ymd') . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate SOAP notes.
     *
     * @param string $complaint
     * @param array $icd10
     * @return array
     */
    protected function generateSOAP(string $complaint, array $icd10): array
    {
        $subjectives = [
            "Pasien mengeluh {$complaint} sejak " . rand(1, 7) . " hari yang lalu.",
            "Pasien datang dengan keluhan utama {$complaint}. Riwayat penyakit serupa sebelumnya (-).",
            "Keluhan {$complaint} dirasakan pasien. Memburuk pada malam hari.",
            "Pasien mengeluh {$complaint}. Riwayat alergi obat (-).",
        ];

        $objectives = [
            "Keadaan umum: Baik, Kesadaran: Composmentis. TD: " . rand(110, 140) . "/" . rand(70, 90) . " mmHg, HR: " . rand(70, 100) . "x/menit, RR: " . rand(16, 22) . "x/menit, Suhu: " . rand(36, 38) . ",°C.",
            "Keadaan umum: Sedang, Kesadaran: Composmentis. TD: " . rand(100, 130) . "/" . rand(60, 85) . " mmHg, HR: " . rand(80, 110) . "x/menit, SpO2: " . rand(95, 99) . "%.",
            "Keadaan umum: Tampak sakit sedang. Pemeriksaan fisik dalam batas normal.",
        ];

        $assessments = [
            "Diasumsikan {$icd10['description']} ({$icd10['code']}).",
            "Diagnosis kerja: {$icd10['description']}.",
            "Sesuai dengan gejala klinis, diagnosa: {$icd10['description']}.",
        ];

        $plans = [
            "1. Terapi simptomatik\n2. Istirahat cukup\n3. Kontrol ulang 3 hari\n4. Edukasi diet sehat",
            "1. Pemberian obat sesuai protokol\n2. Observasi perkembangan gejala\n3. Kontrol ulang jika tidak membaik",
            "1. Terapi medicamentosa\n2. Rujuk ke spesialis jika tidak membaik",
            "1. Konsumsi obat teratur\n2. Hindari pemicu\n3. Kontrol 1 minggu",
        ];

        return [
            'subjective' => $subjectives[array_rand($subjectives)],
            'objective' => $objectives[array_rand($objectives)],
            'assessment' => $assessments[array_rand($assessments)],
            'plan' => $plans[array_rand($plans)],
        ];
    }

    /**
     * Generate procedure code.
     *
     * @return string
     */
    protected function generateProcedureCode(): string
    {
        $codes = ['89.03', '89.05', '87.03', '99.04', '88.72', '99.15', '99.17', '99.18'];

        return $codes[array_rand($codes)];
    }

    /**
     * Generate procedure description.
     *
     * @return string
     */
    protected function generateProcedureDescription(): string
    {
        $procedures = [
            'EKG',
            'Rontgen thorax',
            'Pemeriksaan laboratorium darah lengkap',
            'Nebulizer',
            'Infus',
            'Injeksi IM',
            'Pemeriksaan urinalisis',
            'Pemeriksaan gula darah',
        ];

        return $procedures[array_rand($procedures)];
    }
}
