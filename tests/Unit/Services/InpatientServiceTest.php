<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use App\Services\InpatientService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class for InpatientService.
 *
 * Tests patient admission, transfer, discharge, and bed management functionality.
 */
class InpatientServiceTest extends TestCase
{
    use RefreshDatabase;

    private InpatientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InpatientService();
    }

    private function createTestRoom(string $roomClass = 'Kelas II'): Room
    {
        return Room::create([
            'code' => 'R' . uniqid(),
            'name' => 'Test Room',
            'room_class' => $roomClass,
            'floor' => 2,
            'total_beds' => 5,
            'available_beds' => 5,
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

    private function createTestVisit(array $attributes = []): Visit
    {
        $defaultAttributes = [
            'visit_number' => 'V' . uniqid(),
            'patient_id' => 1,
            'visit_date' => now(),
            'visit_type' => 'rawat_inap',
            'status' => 'pending',
            'inpatient_status' => null,
        ];

        return Visit::create(array_merge($defaultAttributes, $attributes));
    }

    // ==================== Admit Patient Tests ====================

    #[Test]
    public function it_successfully_admits_patient_to_available_bed(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $result = $this->service->admitPatient($visit->id, $room->id, $bed->id);

        $this->assertTrue($result);
        $bed->refresh();
        $visit->refresh();

        $this->assertEquals('terisi', $bed->status);
        $this->assertEquals($visit->id, $bed->current_visit_id);
        $this->assertEquals('admitted', $visit->inpatient_status);
        $this->assertEquals('in_progress', $visit->status);
    }

    #[Test]
    public function it_fails_to_admit_patient_when_bed_is_occupied(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'terisi');
        $existingVisit = $this->createTestVisit(['inpatient_status' => 'admitted']);
        
        $bed->update(['current_visit_id' => $existingVisit->id]);
        
        $newVisit = $this->createTestVisit();

        $result = $this->service->admitPatient($newVisit->id, $room->id, $bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_admit_patient_when_bed_is_in_maintenance(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'maintenance');
        $visit = $this->createTestVisit();

        $result = $this->service->admitPatient($visit->id, $room->id, $bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_admit_patient_when_bed_does_not_belong_to_room(): void
    {
        $room1 = $this->createTestRoom();
        $room2 = $this->createTestRoom();
        $bed = $this->createTestBed($room1);
        $visit = $this->createTestVisit();

        $result = $this->service->admitPatient($visit->id, $room2->id, $bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_admit_patient_with_invalid_visit_id(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);

        $result = $this->service->admitPatient(99999, $room->id, $bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_admit_patient_with_invalid_bed_id(): void
    {
        $room = $this->createTestRoom();
        $visit = $this->createTestVisit();

        $result = $this->service->admitPatient($visit->id, $room->id, 99999);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_admit_patient_with_invalid_room_id(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $result = $this->service->admitPatient($visit->id, 99999, $bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_decrements_available_beds_count_on_admission(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $initialAvailable = $room->available_beds;

        $this->service->admitPatient($visit->id, $room->id, $bed->id);

        $room->refresh();
        $this->assertEquals($initialAvailable - 1, $room->available_beds);
    }

    #[Test]
    public function it_sets_check_in_timestamp_on_admission(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $beforeAdmission = now();
        $this->service->admitPatient($visit->id, $room->id, $bed->id);
        $afterAdmission = now();

        $visit->refresh();
        $this->assertNotNull($visit->check_in_at);
        $this->assertTrue($visit->check_in_at >= $beforeAdmission);
        $this->assertTrue($visit->check_in_at <= $afterAdmission);
    }

    // ==================== Transfer Patient Tests ====================

    #[Test]
    public function it_successfully_transfers_patient_to_new_bed(): void
    {
        $room = $this->createTestRoom();
        $originalBed = $this->createTestBed($room);
        $newBed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        // First admit the patient
        $this->service->admitPatient($visit->id, $room->id, $originalBed->id);

        // Then transfer
        $result = $this->service->transferPatient($visit->id, $newBed->id, 'Need larger bed');

        $this->assertTrue($result);
        $originalBed->refresh();
        $newBed->refresh();
        $visit->refresh();

        $this->assertEquals('kosong', $originalBed->status);
        $this->assertNull($originalBed->current_visit_id);
        $this->assertEquals('terisi', $newBed->status);
        $this->assertEquals($visit->id, $newBed->current_visit_id);
        $this->assertEquals('transferred', $visit->inpatient_status);
        $this->assertEquals('Need larger bed', $visit->transfer_reason);
    }

    #[Test]
    public function it_fails_to_transfer_when_new_bed_is_occupied(): void
    {
        $room = $this->createTestRoom();
        $originalBed = $this->createTestBed($room);
        $newBed = $this->createTestBed($room, 'terisi');
        
        $otherVisit = $this->createTestVisit();
        $newBed->update(['current_visit_id' => $otherVisit->id]);
        
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $originalBed->id);
        $result = $this->service->transferPatient($visit->id, $newBed->id, 'Test transfer');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_transfer_when_patient_not_admitted(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit(['inpatient_status' => null]);

        $result = $this->service->transferPatient($visit->id, $bed->id, 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_transfer_with_invalid_visit_id(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);

        $result = $this->service->transferPatient(99999, $bed->id, 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_transfer_with_invalid_bed_id(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed->id);
        $result = $this->service->transferPatient($visit->id, 99999, 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_maintains_room_bed_counts_on_transfer(): void
    {
        $room = $this->createTestRoom();
        $bed1 = $this->createTestBed($room);
        $bed2 = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $initialAvailable = $room->available_beds;

        $this->service->admitPatient($visit->id, $room->id, $bed1->id);
        $room->refresh();
        $afterAdmission = $room->available_beds;

        $this->service->transferPatient($visit->id, $bed2->id, 'Test');
        $room->refresh();
        $afterTransfer = $room->available_beds;

        // Available beds should remain the same after transfer within same room
        $this->assertEquals($initialAvailable - 1, $afterAdmission);
        $this->assertEquals($afterAdmission, $afterTransfer);
    }

    #[Test]
    public function it_sets_transferred_at_timestamp_on_transfer(): void
    {
        $room = $this->createTestRoom();
        $bed1 = $this->createTestBed($room);
        $bed2 = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed1->id);

        $beforeTransfer = now();
        $this->service->transferPatient($visit->id, $bed2->id, 'Test');
        $afterTransfer = now();

        $visit->refresh();
        $this->assertNotNull($visit->transferred_at);
        $this->assertTrue($visit->transferred_at >= $beforeTransfer);
        $this->assertTrue($visit->transferred_at <= $afterTransfer);
    }

    // ==================== Discharge Patient Tests ====================

    #[Test]
    public function it_successfully_discharges_patient(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed->id);

        $dischargeData = [
            'discharge_date' => now(),
            'discharge_status' => 'pulang',
            'discharge_diagnosis' => 'Sembuh',
            'discharge_notes' => 'Pasien dalam kondisi baik',
        ];

        $result = $this->service->dischargePatient($visit->id, $dischargeData);

        $this->assertTrue($result);
        $bed->refresh();
        $visit->refresh();

        $this->assertEquals('kosong', $bed->status);
        $this->assertNull($bed->current_visit_id);
        $this->assertEquals('discharged', $visit->inpatient_status);
        $this->assertEquals('completed', $visit->status);
        $this->assertTrue($visit->is_completed);
    }

    #[Test]
    public function it_fails_to_discharge_with_invalid_visit_id(): void
    {
        $dischargeData = ['discharge_status' => 'pulang'];

        $result = $this->service->dischargePatient(99999, $dischargeData);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_vacates_bed_on_discharge(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed->id);
        $bed->refresh();
        $this->assertEquals('terisi', $bed->status);

        $this->service->dischargePatient($visit->id, ['discharge_status' => 'pulang']);
        $bed->refresh();

        $this->assertEquals('kosong', $bed->status);
        $this->assertNull($bed->current_visit_id);
    }

    #[Test]
    public function it_increments_available_beds_on_discharge(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed->id);
        $room->refresh();
        $afterAdmission = $room->available_beds;

        $this->service->dischargePatient($visit->id, ['discharge_status' => 'pulang']);
        $room->refresh();
        $afterDischarge = $room->available_beds;

        $this->assertEquals($afterAdmission + 1, $afterDischarge);
    }

    #[Test]
    public function it_stores_discharge_diagnosis_on_discharge(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room);
        $visit = $this->createTestVisit();

        $this->service->admitPatient($visit->id, $room->id, $bed->id);

        $this->service->dischargePatient($visit->id, [
            'discharge_status' => 'pulang',
            'discharge_diagnosis' => 'Dengue Fever',
        ]);

        $visit->refresh();
        $this->assertEquals('Dengue Fever', $visit->discharge_diagnosis);
    }

    // ==================== Length of Stay Calculation Tests ====================

    #[Test]
    public function it_calculates_length_of_stay_for_multiple_days(): void
    {
        $admissionDate = Carbon::parse('2024-01-01');
        $dischargeDate = Carbon::parse('2024-01-05');

        $los = $this->service->calculateLengthOfStay($admissionDate, $dischargeDate);

        $this->assertEquals(5, $los);
    }

    #[Test]
    public function it_calculates_length_of_stay_for_same_day(): void
    {
        $date = Carbon::parse('2024-01-15');

        $los = $this->service->calculateLengthOfStay($date, $date);

        $this->assertEquals(1, $los);
    }

    #[Test]
    public function it_calculates_length_of_stay_with_string_dates(): void
    {
        $los = $this->service->calculateLengthOfStay('2024-01-01', '2024-01-10');

        $this->assertEquals(10, $los);
    }

    #[Test]
    public function it_calculates_length_of_stay_to_current_date_when_no_discharge(): void
    {
        $admissionDate = now()->subDays(5);

        $los = $this->service->calculateLengthOfStay($admissionDate, null);

        $this->assertGreaterThanOrEqual(5, $los);
    }

    #[Test]
    public function it_returns_minimum_one_day_for_length_of_stay(): void
    {
        $admissionDate = now();
        $dischargeDate = now()->subMinutes(30);

        $los = $this->service->calculateLengthOfStay($admissionDate, $dischargeDate);

        $this->assertEquals(1, $los);
    }

    #[Test]
    public function it_calculates_length_of_stay_with_carbon_objects(): void
    {
        $admissionDate = Carbon::create(2024, 3, 15);
        $dischargeDate = Carbon::create(2024, 3, 20);

        $los = $this->service->calculateLengthOfStay($admissionDate, $dischargeDate);

        $this->assertEquals(6, $los);
    }

    // ==================== Occupancy Stats Tests ====================

    #[Test]
    public function it_returns_occupancy_stats_structure(): void
    {
        $stats = $this->service->getOccupancyStats();

        $this->assertArrayHasKey('total_beds', $stats);
        $this->assertArrayHasKey('occupied_beds', $stats);
        $this->assertArrayHasKey('available_beds', $stats);
        $this->assertArrayHasKey('maintenance_beds', $stats);
        $this->assertArrayHasKey('cleaning_beds', $stats);
        $this->assertArrayHasKey('occupancy_rate', $stats);
    }

    #[Test]
    public function it_calculates_correct_occupancy_rate(): void
    {
        // Create test beds with known states
        $room = $this->createTestRoom();
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'terisi');
        $this->createTestBed($room, 'terisi');

        $stats = $this->service->getOccupancyStats();

        $this->assertEquals(4, $stats['total_beds']);
        $this->assertEquals(2, $stats['occupied_beds']);
        $this->assertEquals(50.0, $stats['occupancy_rate']);
    }

    #[Test]
    public function it_returns_zero_occupancy_rate_when_no_beds(): void
    {
        $stats = $this->service->getOccupancyStats();

        if ($stats['total_beds'] === 0) {
            $this->assertEquals(0, $stats['occupancy_rate']);
        }
    }

    #[Test]
    public function it_counts_maintenance_beds_correctly(): void
    {
        $room = $this->createTestRoom();
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'maintenance');
        $this->createTestBed($room, 'maintenance');

        $stats = $this->service->getOccupancyStats();

        $this->assertEquals(2, $stats['maintenance_beds']);
    }

    #[Test]
    public function it_counts_cleaning_beds_correctly(): void
    {
        $room = $this->createTestRoom();
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'cleaning');

        $stats = $this->service->getOccupancyStats();

        $this->assertEquals(1, $stats['cleaning_beds']);
    }

    // ==================== Stats by Room Class Tests ====================

    #[Test]
    public function it_returns_stats_by_room_class_structure(): void
    {
        $stats = $this->service->getStatsByRoomClass();

        $this->assertArrayHasKey('VVIP', $stats);
        $this->assertArrayHasKey('VIP', $stats);
        $this->assertArrayHasKey('Kelas I', $stats);
        $this->assertArrayHasKey('Kelas II', $stats);
        $this->assertArrayHasKey('Kelas III', $stats);
        $this->assertArrayHasKey('ICU', $stats);
        $this->assertArrayHasKey('NICU', $stats);
        $this->assertArrayHasKey('PICU', $stats);
        $this->assertArrayHasKey('HCU', $stats);
    }

    #[Test]
    public function it_returns_correct_stats_per_room_class(): void
    {
        $vipRoom = $this->createTestRoom('VIP');
        $this->createTestBed($vipRoom, 'kosong');
        $this->createTestBed($vipRoom, 'terisi');

        $stats = $this->service->getStatsByRoomClass();

        $this->assertEquals(2, $stats['VIP']['total']);
        $this->assertEquals(1, $stats['VIP']['occupied']);
        $this->assertEquals(1, $stats['VIP']['available']);
    }

    #[Test]
    public function it_calculates_occupancy_rate_per_room_class(): void
    {
        $room = $this->createTestRoom('Kelas I');
        $this->createTestBed($room, 'terisi');
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'kosong');

        $stats = $this->service->getStatsByRoomClass();

        $this->assertEquals(25.0, $stats['Kelas I']['occupancy_rate']);
    }

    #[Test]
    public function it_returns_zero_stats_for_empty_room_class(): void
    {
        $stats = $this->service->getStatsByRoomClass();

        // VVIP should have 0 beds if none created
        $this->assertEquals(0, $stats['VVIP']['total']);
        $this->assertEquals(0, $stats['VVIP']['occupancy_rate']);
    }

    // ==================== Bed Availability Tests ====================

    #[Test]
    public function it_returns_true_when_bed_is_available(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'kosong');

        $result = $this->service->isBedAvailable($bed->id);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_bed_is_occupied(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'terisi');
        $visit = $this->createTestVisit();
        $bed->update(['current_visit_id' => $visit->id]);

        $result = $this->service->isBedAvailable($bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_bed_is_in_maintenance(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'maintenance');

        $result = $this->service->isBedAvailable($bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_bed_is_inactive(): void
    {
        $room = $this->createTestRoom();
        $bed = $this->createTestBed($room, 'kosong');
        $bed->update(['is_active' => false]);

        $result = $this->service->isBedAvailable($bed->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_for_invalid_bed_id(): void
    {
        $result = $this->service->isBedAvailable(99999);

        $this->assertFalse($result);
    }

    // ==================== Get Available Beds Tests ====================

    #[Test]
    public function it_returns_available_beds_for_room(): void
    {
        $room = $this->createTestRoom();
        $availableBed1 = $this->createTestBed($room, 'kosong');
        $availableBed2 = $this->createTestBed($room, 'kosong');
        $this->createTestBed($room, 'terisi');

        $availableBeds = $this->service->getAvailableBeds($room->id);

        $this->assertCount(2, $availableBeds);
        $this->assertTrue($availableBeds->contains('id', $availableBed1->id));
        $this->assertTrue($availableBeds->contains('id', $availableBed2->id));
    }

    #[Test]
    public function it_returns_empty_collection_when_no_available_beds(): void
    {
        $room = $this->createTestRoom();
        $this->createTestBed($room, 'terisi');
        $this->createTestBed($room, 'maintenance');

        $availableBeds = $this->service->getAvailableBeds($room->id);

        $this->assertTrue($availableBeds->isEmpty());
    }

    #[Test]
    public function it_excludes_inactive_beds_from_available_list(): void
    {
        $room = $this->createTestRoom();
        $this->createTestBed($room, 'kosong');
        $inactiveBed = $this->createTestBed($room, 'kosong');
        $inactiveBed->update(['is_active' => false]);

        $availableBeds = $this->service->getAvailableBeds($room->id);

        $this->assertCount(1, $availableBeds);
        $this->assertFalse($availableBeds->contains('id', $inactiveBed->id));
    }
}
