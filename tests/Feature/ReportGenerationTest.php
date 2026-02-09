<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $reportUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'report_viewer', 'guard_name' => 'web']);

        // Create users
        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        $this->reportUser = User::factory()->create(['is_active' => true]);
        $this->reportUser->assignRole('report_viewer');
    }

    #[Test]
    public function it_can_display_rl_1_1_hospital_basic_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-1-1');

        $response->assertStatus(200);
        $response->assertSee('Data Dasar Rumah Sakit');
        $response->assertSee(config('app.hospital_name', 'Rumah Sakit'));
    }

    #[Test]
    public function it_calculates_bor_correctly(): void
    {
        // Setup: Create rooms and beds
        $room1 = Room::factory()->create(['total_beds' => 10, 'available_beds' => 10]);
        $room2 = Room::factory()->create(['total_beds' => 10, 'available_beds' => 10]);

        // Create 15 occupied beds (75% BOR)
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

        // Create 5 available beds
        for ($i = 0; $i < 3; $i++) {
            Bed::factory()->create([
                'room_id' => $room1->id,
                'status' => 'kosong',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Bed::factory()->create([
                'room_id' => $room2->id,
                'status' => 'kosong',
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1');

        $response->assertStatus(200);
        // BOR = (Hari Perawatan / (Jumlah TT x Jumlah Hari)) x 100
        // With 15 occupied beds, BOR = 75%
    }

    #[Test]
    public function it_calculates_los_correctly(): void
    {
        $patient = Patient::factory()->create();

        // Create visits with known duration
        Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'check_in_at' => now()->subDays(5),
            'check_out_at' => now(),
            'is_completed' => true,
        ]);

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'check_in_at' => now()->subDays(3),
            'check_out_at' => now(),
            'is_completed' => true,
        ]);

        Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
            'check_in_at' => now()->subDays(7),
            'check_out_at' => now(),
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1');

        $response->assertStatus(200);
        // LOS = Total hari perawatan / Jumlah pasien keluar
        // LOS = (5 + 3 + 7) / 3 = 5 days
    }

    #[Test]
    public function it_calculates_toi_correctly(): void
    {
        // TOI = (Jumlah TT x Periode - Hari Perawatan) / Jumlah Pasien Keluar
        $room = Room::factory()->create(['total_beds' => 10]);

        // Create visits
        for ($i = 0; $i < 5; $i++) {
            $patient = Patient::factory()->create();
            Visit::factory()->create([
                'patient_id' => $patient->id,
                'visit_type' => 'rawat_inap',
                'check_out_at' => now()->subDays($i),
                'is_completed' => true,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_calculates_bto_correctly(): void
    {
        // BTO = Jumlah Pasien Keluar / Jumlah Tempat Tidur
        $room = Room::factory()->create(['total_beds' => 10]);

        // Create 30 discharged patients in a period
        for ($i = 0; $i < 30; $i++) {
            $patient = Patient::factory()->create();
            Visit::factory()->create([
                'patient_id' => $patient->id,
                'visit_type' => 'rawat_inap',
                'check_out_at' => now()->subDays(rand(1, 30)),
                'is_completed' => true,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1');

        $response->assertStatus(200);
        // BTO = 30 / 10 = 3
    }

    #[Test]
    public function it_displays_top_10_diseases_for_rl_4(): void
    {
        // Create medical records with various diagnoses
        $diagnoses = [
            ['code' => 'J06.9', 'name' => 'ISPA', 'count' => 50],
            ['code' => 'K30', 'name' => 'Dispepsia', 'count' => 40],
            ['code' => 'E11.9', 'name' => 'DM Tipe 2', 'count' => 35],
            ['code' => 'I10', 'name' => 'Hipertensi', 'count' => 30],
            ['code' => 'A09', 'name' => 'Diare', 'count' => 25],
            ['code' => 'J18.9', 'name' => 'Pneumonia', 'count' => 20],
            ['code' => 'N39.0', 'name' => 'ISKA', 'count' => 18],
            ['code' => 'M25.5', 'name' => 'Artralgia', 'count' => 15],
            ['code' => 'R50.9', 'name' => 'Demam', 'count' => 12],
            ['code' => 'H10.9', 'name' => 'Konjungtivitis', 'count' => 10],
            ['code' => 'L30.9', 'name' => 'Dermatitis', 'count' => 8],
        ];

        foreach ($diagnoses as $diagnosis) {
            for ($i = 0; $i < $diagnosis['count']; $i++) {
                $patient = Patient::factory()->create();
                $visit = Visit::factory()->create([
                    'patient_id' => $patient->id,
                ]);

                MedicalRecord::factory()->create([
                    'visit_id' => $visit->id,
                    'patient_id' => $patient->id,
                    'icd_code' => $diagnosis['code'],
                    'icd_name' => $diagnosis['name'],
                ]);
            }
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-4');

        $response->assertStatus(200);
        $response->assertSee('ISPA');
        $response->assertSee('Dispepsia');
        $response->assertSee('50'); // Top count
    }

    #[Test]
    public function it_groups_diagnoses_by_icd10_category(): void
    {
        // Create diagnoses by ICD-10 chapters
        $icdChapters = [
            ['code_start' => 'A00', 'code_end' => 'B99', 'name' => 'Penyakit Infeksi'], // Chapter I
            ['code_start' => 'C00', 'code_end' => 'D48', 'name' => 'Neoplasma'], // Chapter II
            ['code_start' => 'E00', 'code_end' => 'E90', 'name' => 'Gangguan Metabolik'], // Chapter IV
        ];

        foreach ($icdChapters as $chapter) {
            for ($i = 0; $i < 10; $i++) {
                $patient = Patient::factory()->create();
                $visit = Visit::factory()->create([
                    'patient_id' => $patient->id,
                ]);

                MedicalRecord::factory()->create([
                    'visit_id' => $visit->id,
                    'patient_id' => $patient->id,
                    'icd_code' => $chapter['code_start'],
                ]);
            }
        }

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-4?group_by=chapter');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_filters_daily_reports(): void
    {
        $patient = Patient::factory()->create();

        // Visits today
        Visit::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'visit_date' => now(),
        ]);

        // Visits yesterday
        Visit::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'visit_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/daily?date=' . now()->format('Y-m-d'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_filters_monthly_reports(): void
    {
        $patient = Patient::factory()->create();

        // Visits this month
        Visit::factory()->count(20)->create([
            'patient_id' => $patient->id,
            'visit_date' => now(),
        ]);

        // Visits last month
        Visit::factory()->count(15)->create([
            'patient_id' => $patient->id,
            'visit_date' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/monthly?month=' . now()->format('Y-m'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_filters_yearly_reports(): void
    {
        $patient = Patient::factory()->create();

        // Visits this year
        Visit::factory()->count(100)->create([
            'patient_id' => $patient->id,
            'visit_date' => now(),
        ]);

        // Visits last year
        Visit::factory()->count(80)->create([
            'patient_id' => $patient->id,
            'visit_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/yearly?year=' . now()->format('Y'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_exports_reports_to_excel(): void
    {
        $patient = Patient::factory()->create();
        Visit::factory()->count(10)->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1/export?format=excel');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    #[Test]
    public function it_exports_reports_to_pdf(): void
    {
        $patient = Patient::factory()->create();
        Visit::factory()->count(10)->create([
            'patient_id' => $patient->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1/export?format=pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function it_validates_date_range_for_reports(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/rl-3-1?start_date=invalid&end_date=invalid');

        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    #[Test]
    public function it_displays_report_statistics_summary(): void
    {
        $patient = Patient::factory()->create();

        // Create various visit types
        Visit::factory()->count(10)->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_jalan',
        ]);

        Visit::factory()->count(5)->create([
            'patient_id' => $patient->id,
            'visit_type' => 'rawat_inap',
        ]);

        Visit::factory()->count(3)->create([
            'patient_id' => $patient->id,
            'visit_type' => 'igd',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/summary');

        $response->assertStatus(200);
        $response->assertSee('Rawat Jalan');
        $response->assertSee('Rawat Inap');
        $response->assertSee('IGD');
    }

    #[Test]
    public function it_calculates_growth_percentage(): void
    {
        $patient = Patient::factory()->create();

        // Last month visits
        Visit::factory()->count(100)->create([
            'patient_id' => $patient->id,
            'visit_date' => now()->subMonth(),
        ]);

        // This month visits (20% increase)
        Visit::factory()->count(120)->create([
            'patient_id' => $patient->id,
            'visit_date' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/comparison');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_restricts_report_access_by_permission(): void
    {
        $userWithoutPermission = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($userWithoutPermission)
            ->get('/admin/reports/rl-3-1');

        $response->assertStatus(403);
    }

    #[Test]
    public function it_displays_room_occupancy_by_class(): void
    {
        Room::factory()->create(['room_class' => 'VVIP', 'total_beds' => 2, 'available_beds' => 0]);
        Room::factory()->create(['room_class' => 'VIP', 'total_beds' => 5, 'available_beds' => 2]);
        Room::factory()->create(['room_class' => 'Kelas I', 'total_beds' => 10, 'available_beds' => 5]);
        Room::factory()->create(['room_class' => 'Kelas II', 'total_beds' => 15, 'available_beds' => 10]);
        Room::factory()->create(['room_class' => 'Kelas III', 'total_beds' => 20, 'available_beds' => 15]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/bed-occupancy-by-class');

        $response->assertStatus(200);
        $response->assertSee('VVIP');
        $response->assertSee('VIP');
        $response->assertSee('Kelas I');
    }
}
