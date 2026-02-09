<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Clinical\Assessment;
use App\Models\Financial\Payment;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class for ReportService.
 *
 * Tests hospital statistics calculations including BOR, LOS, TOI, BTO,
 * GDR, NDR, and various reporting metrics.
 */
class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    private function createTestRoom(string $roomClass = 'Kelas II', int $totalBeds = 10): Room
    {
        return Room::create([
            'code' => 'R' . uniqid(),
            'name' => 'Test Room ' . $roomClass,
            'room_class' => $roomClass,
            'floor' => 2,
            'total_beds' => $totalBeds,
            'available_beds' => $totalBeds,
            'is_active' => true,
        ]);
    }

    private function createTestBed(Room $room, string $status = 'kosong'): Bed
    {
        return Bed::create([
            'room_id' => $room->id,
            'bed_number' => 'B' . uniqid(),
            'bed_name' => 'Test Bed',
            'bed_type' => 'standard',
            'status' => $status,
            'is_active' => true,
        ]);
    }

    private function createTestPatient(): Patient
    {
        return Patient::create([
            'medical_record_number' => 'MRN' . uniqid(),
            'name' => 'Test Patient',
            'birth_date' => now()->subYears(30),
            'gender' => 'male',
        ]);
    }

    private function createTestVisit(array $attributes = []): Visit
    {
        $patient = $this->createTestPatient();
        $defaults = [
            'visit_number' => 'V' . uniqid(),
            'patient_id' => $patient->id,
            'visit_date' => now(),
            'visit_type' => 'rawat_inap',
            'status' => 'completed',
        ];

        return Visit::create(array_merge($defaults, $attributes));
    }

    private function createTestPayment(array $attributes = []): Payment
    {
        $defaults = [
            'payment_number' => 'PAY' . uniqid(),
            'payment_date' => now(),
            'amount' => 1000000,
            'payment_method' => 'cash',
            'is_refunded' => false,
        ];

        return Payment::create(array_merge($defaults, $attributes));
    }

    private function createTestAssessment(array $attributes = []): Assessment
    {
        $patient = $this->createTestPatient();
        $defaults = [
            'medical_record_id' => 1,
            'patient_id' => $patient->id,
            'visit_id' => 1,
            'assessment_type' => 'umum',
            'assessment_date' => now(),
            'assessed_at' => now(),
        ];

        return Assessment::create(array_merge($defaults, $attributes));
    }

    // ==================== BOR (Bed Occupancy Rate) Tests ====================

    #[Test]
    public function it_calculates_bor_correctly(): void
    {
        $room = $this->createTestRoom('Kelas I', 10);
        $this->createTestBed($room);

        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-10');

        // Create a visit with 5 days of care
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => '2024-01-05',
            'discharge_date' => '2024-01-10',
            'room_id' => $room->id,
        ]);

        $bor = $this->service->calculateBOR($startDate, $endDate);

        $this->assertIsFloat($bor);
        $this->assertGreaterThanOrEqual(0, $bor);
        $this->assertLessThanOrEqual(100, $bor);
    }

    #[Test]
    public function it_returns_zero_bor_when_no_beds(): void
    {
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now();

        $bor = $this->service->calculateBOR($startDate, $endDate);

        $this->assertEquals(0.0, $bor);
    }

    #[Test]
    public function it_calculates_bor_for_specific_room(): void
    {
        $room1 = $this->createTestRoom('Kelas I', 5);
        $room2 = $this->createTestRoom('Kelas II', 10);
        
        $this->createTestBed($room1);
        $this->createTestBed($room2);

        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(5),
            'discharge_date' => now()->subDays(2),
            'room_id' => $room1->id,
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $borRoom1 = $this->service->calculateBOR($startDate, $endDate, $room1->id);
        $borAll = $this->service->calculateBOR($startDate, $endDate);

        $this->assertIsFloat($borRoom1);
        $this->assertIsFloat($borAll);
    }

    // ==================== LOS (Length of Stay) Tests ====================

    #[Test]
    public function it_calculates_los_correctly(): void
    {
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => '2024-01-01',
            'discharge_date' => '2024-01-06',
        ]);

        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => '2024-01-01',
            'discharge_date' => '2024-01-11',
        ]);

        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        $los = $this->service->calculateLOS($startDate, $endDate);

        // Expected: (5 + 10) / 2 = 7.5 days average
        $this->assertEquals(7.5, $los);
    }

    #[Test]
    public function it_returns_zero_los_when_no_discharges(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $los = $this->service->calculateLOS($startDate, $endDate);

        $this->assertEquals(0.0, $los);
    }

    #[Test]
    public function it_calculates_los_with_single_patient(): void
    {
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => '2024-01-01',
            'discharge_date' => '2024-01-08',
        ]);

        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        $los = $this->service->calculateLOS($startDate, $endDate);

        $this->assertEquals(7.0, $los);
    }

    // ==================== TOI (Turn Over Interval) Tests ====================

    #[Test]
    public function it_calculates_toi_correctly(): void
    {
        $room = $this->createTestRoom('Kelas I', 10);
        $this->createTestBed($room);

        // Create discharges
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(10),
            'discharge_date' => now()->subDays(5),
        ]);

        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(8),
            'discharge_date' => now()->subDays(3),
        ]);

        $startDate = now()->subDays(15);
        $endDate = now();

        $toi = $this->service->calculateTOI($startDate, $endDate);

        $this->assertIsFloat($toi);
        $this->assertGreaterThanOrEqual(0, $toi);
    }

    #[Test]
    public function it_returns_zero_toi_when_no_discharges(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $toi = $this->service->calculateTOI($startDate, $endDate);

        $this->assertEquals(0.0, $toi);
    }

    // ==================== BTO (Bed Turn Over) Tests ====================

    #[Test]
    public function it_calculates_bto_correctly(): void
    {
        $room = $this->createTestRoom('Kelas I', 10);
        $this->createTestBed($room);

        // Create 5 discharges
        for ($i = 0; $i < 5; $i++) {
            $this->createTestVisit([
                'visit_type' => 'rawat_inap',
                'admission_date' => now()->subDays(10 + $i),
                'discharge_date' => now()->subDays(5 + $i),
            ]);
        }

        $startDate = now()->subDays(30);
        $endDate = now();

        $bto = $this->service->calculateBTO($startDate, $endDate);

        // Expected: 5 discharges / 10 beds = 0.5
        $this->assertEquals(0.5, $bto);
    }

    #[Test]
    public function it_returns_zero_bto_when_no_beds(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $bto = $this->service->calculateBTO($startDate, $endDate);

        $this->assertEquals(0.0, $bto);
    }

    // ==================== GDR (Gross Death Rate) Tests ====================

    #[Test]
    public function it_calculates_gdr_correctly(): void
    {
        // Create 10 discharges, 2 deaths
        for ($i = 0; $i < 8; $i++) {
            $this->createTestVisit([
                'visit_type' => 'rawat_inap',
                'discharge_date' => now()->subDays($i),
                'discharge_status' => 'pulang',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            $this->createTestVisit([
                'visit_type' => 'rawat_inap',
                'discharge_date' => now()->subDays($i + 10),
                'discharge_status' => 'meninggal',
            ]);
        }

        $startDate = now()->subDays(30);
        $endDate = now();

        $gdr = $this->service->calculateGDR($startDate, $endDate);

        // Expected: (2 / 10) * 100 = 20%
        $this->assertEquals(20.0, $gdr);
    }

    #[Test]
    public function it_returns_zero_gdr_when_no_discharges(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $gdr = $this->service->calculateGDR($startDate, $endDate);

        $this->assertEquals(0.0, $gdr);
    }

    #[Test]
    public function it_filters_gdr_by_visit_type(): void
    {
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'discharge_date' => now()->subDays(5),
            'discharge_status' => 'meninggal',
        ]);

        $this->createTestVisit([
            'visit_type' => 'igd',
            'discharge_date' => now()->subDays(5),
            'discharge_status' => 'meninggal',
        ]);

        $startDate = now()->subDays(30);
        $endDate = now();

        $gdrAll = $this->service->calculateGDR($startDate, $endDate);
        $gdrInpatient = $this->service->calculateGDR($startDate, $endDate, 'rawat_inap');

        $this->assertIsFloat($gdrAll);
        $this->assertIsFloat($gdrInpatient);
    }

    // ==================== NDR (Net Death Rate) Tests ====================

    #[Test]
    public function it_calculates_ndr_correctly(): void
    {
        // Create deaths - 1 under 48h, 1 over 48h
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(5),
            'discharge_date' => now()->subDays(4), // 24 hours
            'discharge_status' => 'meninggal',
        ]);

        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(10),
            'discharge_date' => now()->subDays(7), // 72 hours
            'discharge_status' => 'meninggal',
        ]);

        // Create surviving discharge
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(8),
            'discharge_date' => now()->subDays(5),
            'discharge_status' => 'pulang',
        ]);

        $startDate = now()->subDays(30);
        $endDate = now();

        $ndr = $this->service->calculateNDR($startDate, $endDate);

        // 1 death over 48h / (3 discharges - 1 death under 48h) * 100
        $this->assertIsFloat($ndr);
    }

    #[Test]
    public function it_returns_zero_ndr_when_no_deaths_over_48h(): void
    {
        // Only deaths under 48h
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(5),
            'discharge_date' => now()->subDays(4),
            'discharge_status' => 'meninggal',
        ]);

        // Surviving discharge
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(8),
            'discharge_date' => now()->subDays(5),
            'discharge_status' => 'pulang',
        ]);

        $startDate = now()->subDays(30);
        $endDate = now();

        $ndr = $this->service->calculateNDR($startDate, $endDate);

        $this->assertEquals(0.0, $ndr);
    }

    // ==================== Visit Counts Tests ====================

    #[Test]
    public function it_returns_visit_counts_by_type(): void
    {
        // Create visits of different types
        $this->createTestVisit(['visit_type' => 'rawat_jalan', 'visit_date' => now()->subDays(5)]);
        $this->createTestVisit(['visit_type' => 'rawat_jalan', 'visit_date' => now()->subDays(3)]);
        $this->createTestVisit(['visit_type' => 'rawat_inap', 'visit_date' => now()->subDays(4)]);
        $this->createTestVisit(['visit_type' => 'igd', 'visit_date' => now()->subDays(2)]);
        $this->createTestVisit(['visit_type' => 'mcu', 'visit_date' => now()->subDays(1)]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $counts = $this->service->getVisitCountsByType($startDate, $endDate);

        $this->assertArrayHasKey('rawat_jalan', $counts);
        $this->assertArrayHasKey('rawat_inap', $counts);
        $this->assertArrayHasKey('igd', $counts);
        $this->assertArrayHasKey('mcu', $counts);
        $this->assertArrayHasKey('total', $counts);
        $this->assertEquals(2, $counts['rawat_jalan']);
        $this->assertEquals(1, $counts['rawat_inap']);
        $this->assertEquals(1, $counts['igd']);
        $this->assertEquals(1, $counts['mcu']);
        $this->assertEquals(5, $counts['total']);
    }

    #[Test]
    public function it_returns_zero_for_missing_visit_types(): void
    {
        $this->createTestVisit(['visit_type' => 'rawat_jalan', 'visit_date' => now()->subDays(5)]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $counts = $this->service->getVisitCountsByType($startDate, $endDate);

        $this->assertEquals(1, $counts['rawat_jalan']);
        $this->assertEquals(0, $counts['rawat_inap']);
        $this->assertEquals(0, $counts['igd']);
        $this->assertEquals(0, $counts['mcu']);
    }

    // ==================== Daily Visit Trend Tests ====================

    #[Test]
    public function it_returns_daily_visit_trend(): void
    {
        $this->createTestVisit(['visit_type' => 'rawat_jalan', 'visit_date' => now()->subDays(2)]);
        $this->createTestVisit(['visit_type' => 'rawat_jalan', 'visit_date' => now()->subDays(1)]);
        $this->createTestVisit(['visit_type' => 'igd', 'visit_date' => now()->subDays(1)]);

        $startDate = now()->subDays(5);
        $endDate = now();

        $trend = $this->service->getDailyVisitTrend($startDate, $endDate);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $trend);
        $this->assertCount(6, $trend); // 6 days including today

        $firstDay = $trend->first();
        $this->assertArrayHasKey('date', $firstDay);
        $this->assertArrayHasKey('rawat_jalan', $firstDay);
        $this->assertArrayHasKey('rawat_inap', $firstDay);
        $this->assertArrayHasKey('igd', $firstDay);
        $this->assertArrayHasKey('mcu', $firstDay);
    }

    // ==================== Revenue Tests ====================

    #[Test]
    public function it_returns_revenue_by_payment_method(): void
    {
        $this->createTestPayment(['payment_method' => 'cash', 'amount' => 500000, 'payment_date' => now()->subDays(5)]);
        $this->createTestPayment(['payment_method' => 'cash', 'amount' => 300000, 'payment_date' => now()->subDays(3)]);
        $this->createTestPayment(['payment_method' => 'bpjs', 'amount' => 1000000, 'payment_date' => now()->subDays(4)]);
        $this->createTestPayment(['payment_method' => 'credit_card', 'amount' => 750000, 'payment_date' => now()->subDays(2)]);
        $this->createTestPayment(['payment_method' => 'debit_card', 'amount' => 250000, 'payment_date' => now()->subDays(1)]);
        $this->createTestPayment(['payment_method' => 'bank_transfer', 'amount' => 600000, 'payment_date' => now()->subDays(3)]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $revenue = $this->service->getRevenueByPaymentMethod($startDate, $endDate);

        $this->assertArrayHasKey('cash', $revenue);
        $this->assertArrayHasKey('card', $revenue);
        $this->assertArrayHasKey('transfer', $revenue);
        $this->assertArrayHasKey('bpjs', $revenue);
        $this->assertArrayHasKey('insurance', $revenue);
        $this->assertArrayHasKey('mobile_payment', $revenue);
        $this->assertArrayHasKey('total', $revenue);

        $this->assertEquals(800000, $revenue['cash']); // 500000 + 300000
        $this->assertEquals(1000000, $revenue['card']); // 750000 + 250000
        $this->assertEquals(600000, $revenue['transfer']);
        $this->assertEquals(1000000, $revenue['bpjs']);
    }

    #[Test]
    public function it_excludes_refunded_payments_from_revenue(): void
    {
        $this->createTestPayment([
            'payment_method' => 'cash',
            'amount' => 500000,
            'payment_date' => now()->subDays(5),
            'is_refunded' => false,
        ]);
        $this->createTestPayment([
            'payment_method' => 'cash',
            'amount' => 300000,
            'payment_date' => now()->subDays(3),
            'is_refunded' => true,
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $revenue = $this->service->getRevenueByPaymentMethod($startDate, $endDate);

        $this->assertEquals(500000, $revenue['cash']);
    }

    #[Test]
    public function it_returns_zero_for_missing_payment_methods(): void
    {
        $this->createTestPayment(['payment_method' => 'cash', 'amount' => 500000, 'payment_date' => now()->subDays(5)]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $revenue = $this->service->getRevenueByPaymentMethod($startDate, $endDate);

        $this->assertEquals(500000, $revenue['cash']);
        $this->assertEquals(0, $revenue['bpjs']);
        $this->assertEquals(0, $revenue['insurance']);
    }

    // ==================== Daily Revenue Trend Tests ====================

    #[Test]
    public function it_returns_daily_revenue_trend(): void
    {
        $this->createTestPayment(['payment_method' => 'cash', 'amount' => 500000, 'payment_date' => now()->subDays(2)]);
        $this->createTestPayment(['payment_method' => 'bpjs', 'amount' => 1000000, 'payment_date' => now()->subDays(1)]);

        $startDate = now()->subDays(5);
        $endDate = now();

        $trend = $this->service->getDailyRevenueTrend($startDate, $endDate);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $trend);
        $this->assertArrayHasKey('date', $trend->first());
        $this->assertArrayHasKey('cash', $trend->first());
        $this->assertArrayHasKey('bpjs', $trend->first());
        $this->assertArrayHasKey('insurance', $trend->first());
        $this->assertArrayHasKey('other', $trend->first());
    }

    // ==================== Room Occupancy Tests ====================

    #[Test]
    public function it_returns_room_occupancy_by_class(): void
    {
        $room1 = $this->createTestRoom('VIP', 5);
        $room1->update(['available_beds' => 2]); // 3 occupied

        $room2 = $this->createTestRoom('Kelas I', 10);
        $room2->update(['available_beds' => 5]); // 5 occupied

        $occupancy = $this->service->getRoomOccupancyByClass();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $occupancy);
        
        $vipData = $occupancy->firstWhere('class', 'VIP');
        $this->assertNotNull($vipData);
        $this->assertEquals(5, $vipData['total_beds']);
        $this->assertEquals(3, $vipData['occupied_beds']);
        $this->assertEquals(60.0, $vipData['occupancy_rate']);
    }

    // ==================== Top Diseases Tests ====================

    #[Test]
    public function it_returns_top_diseases(): void
    {
        // Create assessments with diagnoses
        $this->createTestAssessment([
            'primary_diagnosis_code' => 'A00',
            'primary_diagnosis_name' => 'Cholera',
            'assessed_at' => now()->subDays(5),
        ]);
        $this->createTestAssessment([
            'primary_diagnosis_code' => 'A00',
            'primary_diagnosis_name' => 'Cholera',
            'assessed_at' => now()->subDays(4),
        ]);
        $this->createTestAssessment([
            'primary_diagnosis_code' => 'B01',
            'primary_diagnosis_name' => 'Varicella',
            'assessed_at' => now()->subDays(3),
        ]);
        $this->createTestAssessment([
            'primary_diagnosis_code' => 'C01',
            'primary_diagnosis_name' => 'Malignant neoplasm',
            'assessed_at' => now()->subDays(2),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $diseases = $this->service->getTopDiseases($startDate, $endDate, 3);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $diseases);
        $this->assertCount(3, $diseases);

        $firstDisease = $diseases->first();
        $this->assertArrayHasKey('code', $firstDisease);
        $this->assertArrayHasKey('name', $firstDisease);
        $this->assertArrayHasKey('count', $firstDisease);

        // Cholera should be first with count 2
        $this->assertEquals('A00', $firstDisease['code']);
        $this->assertEquals(2, $firstDisease['count']);
    }

    #[Test]
    public function it_limits_top_diseases_results(): void
    {
        // Create 10 different diagnoses
        for ($i = 0; $i < 10; $i++) {
            $this->createTestAssessment([
                'primary_diagnosis_code' => 'D' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'primary_diagnosis_name' => 'Disease ' . $i,
                'assessed_at' => now()->subDays($i),
            ]);
        }

        $startDate = now()->subDays(30);
        $endDate = now();

        $diseases5 = $this->service->getTopDiseases($startDate, $endDate, 5);
        $diseases10 = $this->service->getTopDiseases($startDate, $endDate, 10);

        $this->assertCount(5, $diseases5);
        $this->assertCount(10, $diseases10);
    }

    // ==================== Recent Visits Tests ====================

    #[Test]
    public function it_returns_recent_visits(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createTestVisit(['visit_date' => now()->subDays($i)]);
        }

        $recent = $this->service->getRecentVisits(3);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
        $this->assertCount(3, $recent);

        $firstVisit = $recent->first();
        $this->assertArrayHasKey('visit_number', $firstVisit);
        $this->assertArrayHasKey('patient_name', $firstVisit);
        $this->assertArrayHasKey('visit_type', $firstVisit);
    }

    // ==================== Long Stay Patients Tests ====================

    #[Test]
    public function it_returns_long_stay_patients(): void
    {
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(10),
            'discharge_date' => null,
        ]);

        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(3),
            'discharge_date' => null,
        ]);

        $longStay = $this->service->getLongStayPatients(7);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $longStay);
        $this->assertCount(1, $longStay); // Only 1 patient with LOS > 7 days
    }

    // ==================== Employee Statistics Tests ====================

    #[Test]
    public function it_returns_employee_statistics(): void
    {
        // Create test employees
        Employee::create([
            'employee_number' => 'E' . uniqid(),
            'name' => 'Dr. Test',
            'is_doctor' => true,
            'status' => 'aktif',
        ]);
        Employee::create([
            'employee_number' => 'E' . uniqid(),
            'name' => 'Nurse Test',
            'is_nurse' => true,
            'status' => 'aktif',
        ]);

        $stats = $this->service->getEmployeeStatistics();

        $this->assertArrayHasKey('doctors', $stats);
        $this->assertArrayHasKey('nurses', $stats);
        $this->assertArrayHasKey('pharmacists', $stats);
        $this->assertArrayHasKey('midwives', $stats);
        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertIsInt($stats['total_employees']);
    }

    // ==================== Hospital Bed Statistics Tests ====================

    #[Test]
    public function it_returns_hospital_bed_statistics(): void
    {
        $room = $this->createTestRoom('Kelas I', 10);
        $room->update(['available_beds' => 4]); // 6 occupied

        $stats = $this->service->getHospitalBedStatistics();

        $this->assertArrayHasKey('total_beds', $stats);
        $this->assertArrayHasKey('occupied_beds', $stats);
        $this->assertArrayHasKey('available_beds', $stats);
        $this->assertArrayHasKey('occupancy_rate', $stats);
        $this->assertArrayHasKey('class_distribution', $stats);

        $this->assertEquals(10, $stats['total_beds']);
        $this->assertEquals(60.0, $stats['occupancy_rate']);
    }

    // ==================== Service Statistics Tests ====================

    #[Test]
    public function it_returns_service_statistics(): void
    {
        // Create test data
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(5),
        ]);
        $this->createTestVisit([
            'visit_type' => 'rawat_jalan',
            'visit_date' => now()->subDays(3),
        ]);
        $this->createTestVisit([
            'visit_type' => 'igd',
            'visit_date' => now()->subDays(2),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $stats = $this->service->getServiceStatistics($startDate, $endDate);

        $this->assertArrayHasKey('rawat_inap', $stats);
        $this->assertArrayHasKey('rawat_jalan', $stats);
        $this->assertArrayHasKey('igd', $stats);
        $this->assertArrayHasKey('laboratory', $stats);
        $this->assertArrayHasKey('radiology', $stats);
        $this->assertArrayHasKey('pharmacy', $stats);
    }

    // ==================== Mortality Statistics Tests ====================

    #[Test]
    public function it_returns_mortality_statistics(): void
    {
        // Create deaths with different LOS
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(5),
            'discharge_date' => now()->subDays(4),
            'discharge_status' => 'meninggal',
        ]);
        $this->createTestVisit([
            'visit_type' => 'rawat_inap',
            'admission_date' => now()->subDays(10),
            'discharge_date' => now()->subDays(7),
            'discharge_status' => 'meninggal',
        ]);
        $this->createTestVisit([
            'visit_type' => 'igd',
            'visit_date' => now()->subDays(3),
            'discharge_status' => 'meninggal',
        ]);

        $startDate = now()->subDays(30);
        $endDate = now();

        $stats = $this->service->getMortalityStatistics($startDate, $endDate);

        $this->assertArrayHasKey('rawat_inap', $stats);
        $this->assertArrayHasKey('igd', $stats);
        $this->assertArrayHasKey('rawat_jalan', $stats);
        $this->assertArrayHasKey('total_deaths', $stats['rawat_inap']);
        $this->assertArrayHasKey('under_48h', $stats['rawat_inap']);
        $this->assertArrayHasKey('over_48h', $stats['rawat_inap']);
    }

    // ==================== Period Label Tests ====================

    #[Test]
    public function it_formats_today_period_label(): void
    {
        $label = $this->service->formatPeriodLabel('today');

        $this->assertStringContainsString('Hari Ini', $label);
        $this->assertStringContainsString(now()->format('d M Y'), $label);
    }

    #[Test]
    public function it_formats_week_period_label(): void
    {
        $label = $this->service->formatPeriodLabel('week');

        $this->assertStringContainsString('Minggu Ini', $label);
    }

    #[Test]
    public function it_formats_month_period_label(): void
    {
        $label = $this->service->formatPeriodLabel('month');

        $this->assertStringContainsString('Bulan Ini', $label);
        $this->assertStringContainsString(now()->format('F Y'), $label);
    }

    #[Test]
    public function it_formats_year_period_label(): void
    {
        $label = $this->service->formatPeriodLabel('year');

        $this->assertStringContainsString('Tahun Ini', $label);
        $this->assertStringContainsString(now()->format('Y'), $label);
    }

    #[Test]
    public function it_formats_custom_period_label(): void
    {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        $label = $this->service->formatPeriodLabel('custom', $startDate, $endDate);

        $this->assertStringContainsString('01 Jan 2024', $label);
        $this->assertStringContainsString('31 Jan 2024', $label);
    }

    #[Test]
    public function it_returns_default_for_unknown_period(): void
    {
        $label = $this->service->formatPeriodLabel('unknown');

        $this->assertStringContainsString('Hari Ini', $label);
    }

    // ==================== Date Range Tests ====================

    #[Test]
    public function it_returns_today_date_range(): void
    {
        $range = $this->service->getDateRange('today');

        $this->assertArrayHasKey('start', $range);
        $this->assertArrayHasKey('end', $range);
        $this->assertTrue($range['start']->isToday());
        $this->assertTrue($range['end']->isToday());
    }

    #[Test]
    public function it_returns_week_date_range(): void
    {
        $range = $this->service->getDateRange('week');

        $this->assertTrue($range['start']->isMonday() || $range['start']->isSunday());
        $this->assertTrue($range['end']->isSunday() || $range['end']->isSaturday());
    }

    #[Test]
    public function it_returns_month_date_range(): void
    {
        $range = $this->service->getDateRange('month');

        $this->assertEquals(1, $range['start']->day);
        $this->assertEquals(now()->daysInMonth, $range['end']->day);
    }

    #[Test]
    public function it_returns_year_date_range(): void
    {
        $range = $this->service->getDateRange('year');

        $this->assertEquals(1, $range['start']->month);
        $this->assertEquals(1, $range['start']->day);
        $this->assertEquals(12, $range['end']->month);
        $this->assertEquals(31, $range['end']->day);
    }

    #[Test]
    public function it_returns_default_for_unknown_date_range(): void
    {
        $range = $this->service->getDateRange('unknown');

        $this->assertTrue($range['start']->isToday());
        $this->assertTrue($range['end']->isToday());
    }
}
