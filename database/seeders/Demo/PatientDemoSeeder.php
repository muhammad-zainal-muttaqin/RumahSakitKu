<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Patient\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Patient Seeder.
 *
 * Creates 100 sample patients with various demographics:
 * - Different age groups (children, adults, elderly)
 * - Insurance types (BPJS, Asuransi, Mandiri)
 * - Genders (male, female)
 * - Various locations across Indonesia
 *
 * @package Database\Seeders\Demo
 */
class PatientDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Clear existing patients (optional, keeps demo data fresh)
        // DB::table('patients')->where('is_demo', true)->delete();

        $patients = [];
        $now = now();

        for ($i = 1; $i <= 100; $i++) {
            $patients[] = $this->generatePatientData($i, $now);
        }

        // Insert in chunks for better performance
        foreach (array_chunk($patients, 50) as $chunk) {
            DB::table('patients')->insert($chunk);
        }

        $this->command->info('  ✓ Created 100 demo patients');
    }

    /**
     * Generate patient data for a single patient.
     *
     * @param int $index
     * @param Carbon $now
     * @return array
     */
    protected function generatePatientData(int $index, Carbon $now): array
    {
        $gender = $this->getRandomGender();
        $ageGroup = $this->getRandomAgeGroup();
        $insuranceType = $this->getRandomInsuranceType();
        $location = $this->getRandomLocation();

        return [
            'medical_record_number' => $this->generateMRN($index),
            'name' => $this->generateName($gender),
            'nik' => $this->generateNIK($index),
            'birth_place' => $location['city'],
            'birth_date' => $this->generateBirthDate($ageGroup),
            'gender' => $gender,
            'blood_type' => $this->getRandomBloodType(),
            'address' => $this->generateAddress($location),
            'phone' => $this->generatePhone(),
            'email' => $this->generateEmail($index),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => $this->generatePhone(),
            'marital_status' => $this->getRandomMaritalStatus($ageGroup),
            'occupation' => fake()->jobTitle(),
            'insurance_type' => $insuranceType,
            'insurance_number' => $insuranceType !== 'mandiri' ? $this->generateInsuranceNumber($insuranceType, $index) : null,
            'bpjs_card_number' => $insuranceType === 'bpjs' ? $this->generateBPJSNumber($index) : null,
            'photo_path' => null,
            'is_active' => true,
            'registered_at' => $now->copy()->subDays(rand(1, 365)),
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => 1,
        ];
    }

    /**
     * Generate medical record number.
     *
     * @param int $index
     * @return string
     */
    protected function generateMRN(int $index): string
    {
        return sprintf('RM%s%06d', date('Y'), $index);
    }

    /**
     * Generate Indonesian name based on gender.
     *
     * @param string $gender
     * @return string
     */
    protected function generateName(string $gender): string
    {
        $maleNames = [
            'Ahmad', 'Budi', 'Candra', 'Dedi', 'Eko', 'Fajar', 'Gunawan', 'Hendra', 'Indra', 'Joko',
            'Kurniawan', 'Lukman', 'Mulyadi', 'Nugroho', 'Oscar', 'Pandu', 'Qomar', 'Rudi', 'Santoso', 'Teguh',
            'Umar', 'Vicky', 'Wahyu', 'Yusuf', 'Zainal', 'Adi', 'Bayu', 'Cahyo', 'Dani', 'Edi',
            'Ferry', 'Gatot', 'Hari', 'Iwan', 'Jaya', 'Krisna', 'Liawan', 'Maman', 'Nana', 'Oman',
        ];

        $femaleNames = [
            'Aisyah', 'Budiarti', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hani', 'Indah', 'Julia',
            'Kartini', 'Lestari', 'Maya', 'Nurul', 'Oscaria', 'Putri', 'Qonita', 'Ratna', 'Sari', 'Tri',
            'Umi', 'Vina', 'Wati', 'Yani', 'Zahra', 'Ani', 'Bunga', 'Cici', 'Diana', 'Erna',
            'Farida', 'Gadis', 'Halimah', 'Iin', 'Juju', 'Kiki', 'Lina', 'Mimin', 'Nining', 'Oon',
        ];

        $lastNames = [
            'Susanto', 'Wijaya', 'Kusuma', 'Sari', 'Hidayat', 'Nugroho', 'Santoso', 'Siregar', 'Manurung', 'Simanjuntak',
            'Rajagukguk', 'Nasution', 'Harahap', 'Tarigan', 'Sembiring', 'Sinaga', 'Silalahi', 'Marpaung', 'Lumbanbatu', 'Panggabean',
            'Samosir', 'Siahaan', 'Pardede', 'Sitompul', 'Simbolon', 'Sihombing', 'Matondang', 'Turnip', 'Batubara', 'Daulay',
            'Samosir', 'Pane', 'Aritonang', 'Nainggolan', 'Lumbantobing', 'Tampubolon', 'Sitorus', 'Butarbutar', 'Gultom', 'Purba',
        ];

        $firstName = $gender === 'male'
            ? $maleNames[array_rand($maleNames)]
            : $femaleNames[array_rand($femaleNames)];

        $lastName = $lastNames[array_rand($lastNames)];

        // Sometimes add middle name
        if (rand(0, 100) > 70) {
            $middleNames = ['Putra', 'Putri', 'Bakti', 'Jaya', 'Mega', 'Citra', 'Indah', 'Maju'];
            return "{$firstName} {$middleNames[array_rand($middleNames)]} {$lastName}";
        }

        return "{$firstName} {$lastName}";
    }

    /**
     * Generate NIK (Indonesian National ID).
     *
     * @param int $index
     * @return string
     */
    protected function generateNIK(int $index): string
    {
        $provinceCodes = ['31', '32', '33', '34', '35', '36', '51', '52', '53', '61', '62', '63', '64', '71', '72', '73', '74', '75', '76', '81', '82', '91', '92'];
        $province = $provinceCodes[array_rand($provinceCodes)];
        $regency = str_pad((string) rand(1, 20), 2, '0', STR_PAD_LEFT);
        $district = str_pad((string) rand(1, 30), 2, '0', STR_PAD_LEFT);
        $dob = $this->generateRandomDOBForNIK();
        $unique = str_pad((string) $index, 4, '0', STR_PAD_LEFT);

        return "{$province}{$regency}{$district}{$dob}{$unique}";
    }

    /**
     * Generate random date of birth component for NIK.
     *
     * @return string
     */
    protected function generateRandomDOBForNIK(): string
    {
        $day = str_pad((string) rand(1, 31), 2, '0', STR_PAD_LEFT);
        $month = str_pad((string) rand(1, 12), 2, '0', STR_PAD_LEFT);
        $year = str_pad((string) rand(60, 99), 2, '0', STR_PAD_LEFT);

        return "{$day}{$month}{$year}";
    }

    /**
     * Generate birth date based on age group.
     *
     * @param string $ageGroup
     * @return string
     */
    protected function generateBirthDate(string $ageGroup): string
    {
        return match ($ageGroup) {
            'child' => Carbon::now()->subYears(rand(1, 12))->subMonths(rand(0, 11))->format('Y-m-d'),
            'teen' => Carbon::now()->subYears(rand(13, 17))->subMonths(rand(0, 11))->format('Y-m-d'),
            'young_adult' => Carbon::now()->subYears(rand(18, 30))->subMonths(rand(0, 11))->format('Y-m-d'),
            'adult' => Carbon::now()->subYears(rand(31, 50))->subMonths(rand(0, 11))->format('Y-m-d'),
            'elderly' => Carbon::now()->subYears(rand(51, 80))->subMonths(rand(0, 11))->format('Y-m-d'),
            default => Carbon::now()->subYears(rand(18, 60))->format('Y-m-d'),
        };
    }

    /**
     * Generate address.
     *
     * @param array $location
     * @return string
     */
    protected function generateAddress(array $location): string
    {
        return "Jl. {$location['street']} No. " . rand(1, 999) . ", {$location['village']}, {$location['district']}, {$location['city']}, {$location['province']}";
    }

    /**
     * Generate phone number.
     *
     * @return string
     */
    protected function generatePhone(): string
    {
        $prefixes = ['0812', '0813', '0821', '0822', '0852', '0853', '0811', '0814', '0815', '0816', '0855', '0856', '0857', '0858', '0895', '0896'];
        $prefix = $prefixes[array_rand($prefixes)];

        return $prefix . substr(str_shuffle('0123456789'), 0, 8);
    }

    /**
     * Generate email address.
     *
     * @param int $index
     * @return string
     */
    protected function generateEmail(int $index): string
    {
        $domains = ['gmail.com', 'yahoo.com', 'outlook.co.id', 'email.com'];

        return "pasien.demo{$index}@" . $domains[array_rand($domains)];
    }

    /**
     * Generate insurance number.
     *
     * @param string $type
     * @param int $index
     * @return string
     */
    protected function generateInsuranceNumber(string $type, int $index): string
    {
        return match ($type) {
            'bpjs' => $this->generateBPJSNumber($index),
            'asuransi' => 'AS' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
            default => 'INS' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
        };
    }

    /**
     * Generate BPJS card number.
     *
     * @param int $index
     * @return string
     */
    protected function generateBPJSNumber(int $index): string
    {
        return str_pad((string) $index, 13, '0', STR_PAD_LEFT);
    }

    /**
     * Get random gender.
     *
     * @return string
     */
    protected function getRandomGender(): string
    {
        return rand(0, 100) > 50 ? 'male' : 'female';
    }

    /**
     * Get random age group.
     *
     * @return string
     */
    protected function getRandomAgeGroup(): string
    {
        $groups = [
            'child' => 15,      // 0-12 years
            'teen' => 10,       // 13-17 years
            'young_adult' => 25, // 18-30 years
            'adult' => 35,      // 31-50 years
            'elderly' => 15,    // 51+ years
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($groups as $group => $probability) {
            $cumulative += $probability;
            if ($random <= $cumulative) {
                return $group;
            }
        }

        return 'adult';
    }

    /**
     * Get random insurance type.
     *
     * @return string
     */
    protected function getRandomInsuranceType(): string
    {
        $types = [
            'bpjs' => 70,      // 70% BPJS
            'asuransi' => 20,  // 20% Insurance
            'mandiri' => 10,   // 10% Self-pay
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($types as $type => $probability) {
            $cumulative += $probability;
            if ($random <= $cumulative) {
                return $type;
            }
        }

        return 'bpjs';
    }

    /**
     * Get random blood type.
     *
     * @return string|null
     */
    protected function getRandomBloodType(): ?string
    {
        $types = ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', null];

        return $types[array_rand($types)];
    }

    /**
     * Get random marital status.
     *
     * @param string $ageGroup
     * @return string|null
     */
    protected function getRandomMaritalStatus(string $ageGroup): ?string
    {
        if (in_array($ageGroup, ['child', 'teen'])) {
            return 'single';
        }

        $statuses = ['single', 'married', 'divorced', 'widowed'];
        $probabilities = [
            'young_adult' => [60, 35, 3, 2],
            'adult' => [15, 75, 5, 5],
            'elderly' => [10, 60, 5, 25],
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($probabilities[$ageGroup] as $index => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $statuses[$index];
            }
        }

        return 'single';
    }

    /**
     * Get random location data.
     *
     * @return array
     */
    protected function getRandomLocation(): array
    {
        $locations = [
            ['city' => 'Jakarta', 'province' => 'DKI Jakarta', 'street' => 'Sudirman', 'district' => 'Kec. Tanah Abang', 'village' => 'Kel. Kebon Melati'],
            ['city' => 'Surabaya', 'province' => 'Jawa Timur', 'street' => 'Pahlawan', 'district' => 'Kec. Genteng', 'village' => 'Kel. Pabean Cantian'],
            ['city' => 'Bandung', 'province' => 'Jawa Barat', 'street' => 'Asia Afrika', 'district' => 'Kec. Sumur Bandung', 'village' => 'Kel. Braga'],
            ['city' => 'Medan', 'province' => 'Sumatera Utara', 'street' => 'Iskandar Muda', 'district' => 'Kec. Medan Kota', 'village' => 'Kel. Sitirejo'],
            ['city' => 'Semarang', 'province' => 'Jawa Tengah', 'street' => 'Pemuda', 'district' => 'Kec. Semarang Tengah', 'village' => 'Kel. Miroto'],
            ['city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'street' => 'Sudirman', 'district' => 'Kec. Makassar', 'village' => 'Kel. Baru'],
            ['city' => 'Palembang', 'province' => 'Sumatera Selatan', 'street' => 'Sudirman', 'district' => 'Kec. Ilir Timur', 'village' => 'Kel. 15 Ulu'],
            ['city' => 'Denpasar', 'province' => 'Bali', 'street' => 'Gajah Mada', 'district' => 'Kec. Denpasar Barat', 'village' => 'Kel. Dauh Puri'],
            ['city' => 'Yogyakarta', 'province' => 'DI Yogyakarta', 'street' => 'Malioboro', 'district' => 'Kec. Gedong Tengen', 'village' => 'Kel. Sosromenduran'],
            ['city' => 'Malang', 'province' => 'Jawa Timur', 'street' => 'Besar', 'district' => 'Kec. Klojen', 'village' => 'Kel. Oro-oro Dowo'],
            ['city' => 'Solo', 'province' => 'Jawa Tengah', 'street' => 'Slamet Riyadi', 'district' => 'Kec. Laweyan', 'village' => 'Kel. Penumping'],
            ['city' => 'Padang', 'province' => 'Sumatera Barat', 'street' => 'Sudirman', 'district' => 'Kec. Padang Barat', 'village' => 'Kel. Belakang Tangsi'],
        ];

        return $locations[array_rand($locations)];
    }
}
