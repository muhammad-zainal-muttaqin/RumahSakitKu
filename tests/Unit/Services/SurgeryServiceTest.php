<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Clinical\Surgery;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\SurgeryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class for SurgeryService.
 *
 * Tests surgery scheduling, status management, room conflict detection,
 * and safety checklist completion.
 */
class SurgeryServiceTest extends TestCase
{
    use RefreshDatabase;

    private SurgeryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SurgeryService();
    }

    private function createTestVisit(): Visit
    {
        $patient = Patient::create([
            'medical_record_number' => 'MRN' . uniqid(),
            'name' => 'Test Patient',
            'birth_date' => now()->subYears(30),
            'gender' => 'male',
            'address' => 'Test Address, Jakarta',
            'phone_primary' => '081234567890',
        ]);

        $user = \App\Models\User::factory()->create();

        return Visit::create([
            'visit_number' => 'V' . uniqid(),
            'patient_id' => $patient->id,
            'registration_date' => now(),
            'visit_type' => 'rawat_inap',
            'visit_status' => 'proses',
            'registered_by' => $user->id,
        ]);
    }

    private function createTestSurgery(array $attributes = []): Surgery
    {
        $visit = $this->createTestVisit();
        $defaults = [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Appendectomy',
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ];

        return Surgery::create(array_merge($defaults, $attributes));
    }

    // ==================== Schedule Surgery Tests ====================

    #[Test]
    public function it_successfully_schedules_surgery(): void
    {
        $visit = $this->createTestVisit();

        $data = [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Appendectomy',
            'surgery_type' => 'elektif',
        ];

        $surgery = $this->service->scheduleSurgery($data);

        $this->assertInstanceOf(Surgery::class, $surgery);
        $this->assertNotNull($surgery->surgery_number);
        $this->assertEquals('scheduled', $surgery->status);
        $this->assertEquals('OK1', $surgery->operating_room);
    }

    #[Test]
    public function it_generates_unique_surgery_number(): void
    {
        $visit = $this->createTestVisit();

        $data = [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'scheduled_date' => now(),
            'start_time' => now()->setTime(9, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Test Procedure',
            'surgery_type' => 'elektif',
        ];

        $surgery1 = $this->service->scheduleSurgery($data);
        $surgery2 = $this->service->scheduleSurgery($data);

        $this->assertNotEquals($surgery1->surgery_number, $surgery2->surgery_number);
    }

    #[Test]
    public function it_detects_room_conflict_when_scheduling(): void
    {
        $visit1 = $this->createTestVisit();
        $visit2 = $this->createTestVisit();

        // Schedule first surgery
        $this->service->scheduleSurgery([
            'visit_id' => $visit1->id,
            'patient_id' => $visit1->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'First Surgery',
            'surgery_type' => 'elektif',
        ]);

        // Try to schedule overlapping surgery
        $result = $this->service->scheduleSurgery([
            'visit_id' => $visit2->id,
            'patient_id' => $visit2->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(10, 0),
            'estimated_end_time' => now()->addDay()->setTime(12, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Second Surgery',
            'surgery_type' => 'elektif',
        ]);

        $this->assertNull($result);
    }

    #[Test]
    public function it_allows_scheduling_in_different_rooms_simultaneously(): void
    {
        $visit1 = $this->createTestVisit();
        $visit2 = $this->createTestVisit();

        $surgery1 = $this->service->scheduleSurgery([
            'visit_id' => $visit1->id,
            'patient_id' => $visit1->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'First Surgery',
            'surgery_type' => 'elektif',
        ]);

        $surgery2 = $this->service->scheduleSurgery([
            'visit_id' => $visit2->id,
            'patient_id' => $visit2->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK2',
            'procedure_name' => 'Second Surgery',
            'surgery_type' => 'elektif',
        ]);

        $this->assertInstanceOf(Surgery::class, $surgery1);
        $this->assertInstanceOf(Surgery::class, $surgery2);
    }

    #[Test]
    public function it_allows_back_to_back_scheduling_in_same_room(): void
    {
        $visit1 = $this->createTestVisit();
        $visit2 = $this->createTestVisit();

        $surgery1 = $this->service->scheduleSurgery([
            'visit_id' => $visit1->id,
            'patient_id' => $visit1->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(9, 0),
            'estimated_end_time' => now()->addDay()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'First Surgery',
            'surgery_type' => 'elektif',
        ]);

        $surgery2 = $this->service->scheduleSurgery([
            'visit_id' => $visit2->id,
            'patient_id' => $visit2->patient_id,
            'scheduled_date' => now()->addDay(),
            'start_time' => now()->addDay()->setTime(11, 0),
            'estimated_end_time' => now()->addDay()->setTime(13, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Second Surgery',
            'surgery_type' => 'elektif',
        ]);

        $this->assertInstanceOf(Surgery::class, $surgery1);
        $this->assertInstanceOf(Surgery::class, $surgery2);
    }

    // ==================== Start Surgery Tests ====================

    #[Test]
    public function it_successfully_starts_scheduled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $result = $this->service->startSurgery($surgery->id);

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals('in_progress', $surgery->status);
        $this->assertNotNull($surgery->actual_start);
    }

    #[Test]
    public function it_successfully_starts_preparation_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'preparation']);

        $result = $this->service->startSurgery($surgery->id);

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals('in_progress', $surgery->status);
    }

    #[Test]
    public function it_fails_to_start_already_started_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $result = $this->service->startSurgery($surgery->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_start_completed_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'completed']);

        $result = $this->service->startSurgery($surgery->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_start_cancelled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'cancelled']);

        $result = $this->service->startSurgery($surgery->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_start_nonexistent_surgery(): void
    {
        $result = $this->service->startSurgery(99999);

        $this->assertFalse($result);
    }

    // ==================== Complete Surgery Tests ====================

    #[Test]
    public function it_successfully_completes_in_progress_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);
        $surgery->update(['actual_start' => now()->subHours(2)]);

        $result = $this->service->completeSurgery($surgery->id, [
            'post_diagnosis' => 'Acute Appendicitis',
            'procedure_notes' => 'Procedure went well',
        ]);

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals('completed', $surgery->status);
        $this->assertNotNull($surgery->actual_end);
        $this->assertEquals('Acute Appendicitis', $surgery->post_diagnosis);
    }

    #[Test]
    public function it_fails_to_complete_not_started_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $result = $this->service->completeSurgery($surgery->id, []);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_complete_already_completed_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'completed']);

        $result = $this->service->completeSurgery($surgery->id, []);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_stores_complications_on_completion(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $this->service->completeSurgery($surgery->id, [
            'complications' => 'Minor bleeding controlled',
        ]);

        $surgery->refresh();
        $this->assertEquals('Minor bleeding controlled', $surgery->complications);
    }

    #[Test]
    public function it_stores_specimens_on_completion(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $this->service->completeSurgery($surgery->id, [
            'specimens' => 'Appendix sent to pathology',
        ]);

        $surgery->refresh();
        $this->assertEquals('Appendix sent to pathology', $surgery->specimens);
    }

    #[Test]
    public function it_updates_safety_checklist_sign_out_on_completion(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $this->service->completeSurgery($surgery->id, [
            'safety_checklist_sign_out' => true,
        ]);

        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_sign_out);
        $this->assertNotNull($surgery->safety_checklist_sign_out_at);
    }

    // ==================== Cancel Surgery Tests ====================

    #[Test]
    public function it_successfully_cancels_scheduled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $result = $this->service->cancelSurgery($surgery->id, 'Patient cancelled');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals('cancelled', $surgery->status);
        $this->assertEquals('Patient cancelled', $surgery->cancellation_reason);
        $this->assertNotNull($surgery->cancelled_at);
    }

    #[Test]
    public function it_successfully_cancels_in_progress_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $result = $this->service->cancelSurgery($surgery->id, 'Emergency complication');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals('cancelled', $surgery->status);
    }

    #[Test]
    public function it_fails_to_cancel_completed_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'completed']);

        $result = $this->service->cancelSurgery($surgery->id, 'Test reason');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_cancel_already_cancelled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'cancelled']);

        $result = $this->service->cancelSurgery($surgery->id, 'Test reason');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_records_cancellation_user_id(): void
    {
        $user = \App\Models\User::factory()->create();
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $this->service->cancelSurgery($surgery->id, 'Reason', $user->id);

        $surgery->refresh();
        $this->assertEquals($user->id, $surgery->cancelled_by);
    }

    // ==================== Postpone Surgery Tests ====================

    #[Test]
    public function it_successfully_postpones_scheduled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $result = $this->service->postponeSurgery($surgery->id, 'Patient not ready');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertTrue($surgery->is_postponed);
        $this->assertEquals('Patient not ready', $surgery->postponed_reason);
        $this->assertNotNull($surgery->postponed_at);
    }

    #[Test]
    public function it_successfully_postpones_preparation_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'preparation']);

        $result = $this->service->postponeSurgery($surgery->id, 'Equipment not available');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertTrue($surgery->is_postponed);
    }

    #[Test]
    public function it_fails_to_postpone_in_progress_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'in_progress']);

        $result = $this->service->postponeSurgery($surgery->id, 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_postpone_completed_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'completed']);

        $result = $this->service->postponeSurgery($surgery->id, 'Test');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_postpone_cancelled_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'cancelled']);

        $result = $this->service->postponeSurgery($surgery->id, 'Test');

        $this->assertFalse($result);
    }

    // ==================== Get Available Slots Tests ====================

    #[Test]
    public function it_returns_available_slots_for_empty_schedule(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $slots = $this->service->getAvailableSlots($date, 'OK1', 120);

        $this->assertIsArray($slots);
        $this->assertCount(1, $slots);
        $this->assertEquals('07:00', $slots[0]['start']);
        $this->assertEquals('20:00', $slots[0]['end']);
    }

    #[Test]
    public function it_returns_slots_around_existing_surgery(): void
    {
        $date = now()->addDay();
        $visit = $this->createTestVisit();

        // Create an existing surgery
        Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(10, 0),
            'estimated_end_time' => $date->copy()->setTime(12, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Existing Surgery',
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ]);

        $slots = $this->service->getAvailableSlots($date->format('Y-m-d'), 'OK1', 60);

        $this->assertIsArray($slots);
        $this->assertGreaterThan(1, count($slots));
    }

    #[Test]
    public function it_excludes_cancelled_surgeries_from_slot_calculation(): void
    {
        $date = now()->addDay();
        $visit = $this->createTestVisit();

        Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(10, 0),
            'estimated_end_time' => $date->copy()->setTime(12, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Cancelled Surgery',
            'surgery_type' => 'elektif',
            'status' => 'cancelled',
        ]);

        $slots = $this->service->getAvailableSlots($date->format('Y-m-d'), 'OK1', 120);

        // Should still have full day available since surgery is cancelled
        $this->assertCount(1, $slots);
    }

    // ==================== Room Conflict Detection Tests ====================

    #[Test]
    public function it_detects_room_conflict_for_overlapping_times(): void
    {
        $visit = $this->createTestVisit();
        $date = now()->addDay();

        Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(9, 0),
            'estimated_end_time' => $date->copy()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Existing Surgery',
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ]);

        $hasConflict = $this->service->hasRoomConflict(
            'OK1',
            $date->copy()->setTime(10, 0),
            $date->copy()->setTime(12, 0)
        );

        $this->assertTrue($hasConflict);
    }

    #[Test]
    public function it_returns_no_conflict_for_non_overlapping_times(): void
    {
        $visit = $this->createTestVisit();
        $date = now()->addDay();

        Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(9, 0),
            'estimated_end_time' => $date->copy()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Existing Surgery',
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ]);

        $hasConflict = $this->service->hasRoomConflict(
            'OK1',
            $date->copy()->setTime(12, 0),
            $date->copy()->setTime(14, 0)
        );

        $this->assertFalse($hasConflict);
    }

    #[Test]
    public function it_excludes_specified_surgery_from_conflict_check(): void
    {
        $visit = $this->createTestVisit();
        $date = now()->addDay();

        $existingSurgery = Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(9, 0),
            'estimated_end_time' => $date->copy()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Existing Surgery',
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
        ]);

        $hasConflict = $this->service->hasRoomConflict(
            'OK1',
            $date->copy()->setTime(9, 0),
            $date->copy()->setTime(11, 0),
            $existingSurgery->id
        );

        $this->assertFalse($hasConflict);
    }

    #[Test]
    public function it_ignores_completed_surgeries_in_conflict_check(): void
    {
        $visit = $this->createTestVisit();
        $date = now()->addDay();

        Surgery::create([
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'surgery_number' => 'OK-TEST-0001',
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(9, 0),
            'estimated_end_time' => $date->copy()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'Completed Surgery',
            'surgery_type' => 'elektif',
            'status' => 'completed',
        ]);

        $hasConflict = $this->service->hasRoomConflict(
            'OK1',
            $date->copy()->setTime(9, 0),
            $date->copy()->setTime(11, 0)
        );

        $this->assertFalse($hasConflict);
    }

    // ==================== Safety Checklist Tests ====================

    #[Test]
    public function it_successfully_completes_sign_in_checklist(): void
    {
        $surgery = $this->createTestSurgery();

        $result = $this->service->completeSafetyChecklist($surgery->id, 'sign_in');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_sign_in);
        $this->assertNotNull($surgery->safety_checklist_sign_in_at);
    }

    #[Test]
    public function it_successfully_completes_time_out_checklist(): void
    {
        $surgery = $this->createTestSurgery();

        $result = $this->service->completeSafetyChecklist($surgery->id, 'time_out');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_time_out);
    }

    #[Test]
    public function it_successfully_completes_sign_out_checklist(): void
    {
        $surgery = $this->createTestSurgery();

        $result = $this->service->completeSafetyChecklist($surgery->id, 'sign_out');

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertTrue($surgery->safety_checklist_sign_out);
    }

    #[Test]
    public function it_fails_to_complete_invalid_checklist_item(): void
    {
        $surgery = $this->createTestSurgery();

        $result = $this->service->completeSafetyChecklist($surgery->id, 'invalid_item');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_fails_to_complete_checklist_for_nonexistent_surgery(): void
    {
        $result = $this->service->completeSafetyChecklist(99999, 'sign_in');

        $this->assertFalse($result);
    }

    // ==================== Surgery Statistics Tests ====================

    #[Test]
    public function it_returns_surgery_statistics_structure(): void
    {
        $stats = $this->service->getStatistics();

        $this->assertArrayHasKey('total_today', $stats);
        $this->assertArrayHasKey('scheduled', $stats);
        $this->assertArrayHasKey('preparation', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('cancelled', $stats);
        $this->assertArrayHasKey('cito_emergency', $stats);
        $this->assertArrayHasKey('by_room', $stats);
    }

    #[Test]
    public function it_calculates_correct_statistics(): void
    {
        // Create test surgeries with different statuses
        $this->createTestSurgery(['status' => 'scheduled', 'scheduled_date' => now()]);
        $this->createTestSurgery(['status' => 'scheduled', 'scheduled_date' => now()]);
        $this->createTestSurgery(['status' => 'in_progress', 'scheduled_date' => now()]);
        $this->createTestSurgery(['status' => 'completed', 'scheduled_date' => now()]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(4, $stats['total_today']);
        $this->assertEquals(2, $stats['scheduled']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
    }

    #[Test]
    public function it_counts_cito_emergency_surgeries(): void
    {
        $this->createTestSurgery([
            'status' => 'scheduled',
            'scheduled_date' => now(),
            'surgery_type' => 'cito',
        ]);
        $this->createTestSurgery([
            'status' => 'scheduled',
            'scheduled_date' => now(),
            'surgery_type' => 'emergency',
        ]);
        $this->createTestSurgery([
            'status' => 'scheduled',
            'scheduled_date' => now(),
            'surgery_type' => 'elektif',
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['cito_emergency']);
    }

    // ==================== Daily Schedule Tests ====================

    #[Test]
    public function it_returns_daily_schedule_for_all_rooms(): void
    {
        $this->createTestSurgery([
            'scheduled_date' => now(),
            'operating_room' => 'OK1',
        ]);

        $schedule = $this->service->getDailySchedule();

        $this->assertArrayHasKey('OK1', $schedule);
        $this->assertArrayHasKey('OK2', $schedule);
        $this->assertArrayHasKey('name', $schedule['OK1']);
        $this->assertArrayHasKey('surgeries', $schedule['OK1']);
    }

    // ==================== Reschedule Surgery Tests ====================

    #[Test]
    public function it_successfully_reschedules_surgery(): void
    {
        $surgery = $this->createTestSurgery(['status' => 'scheduled']);

        $result = $this->service->rescheduleSurgery($surgery->id, [
            'scheduled_date' => now()->addDays(2),
            'start_time' => now()->addDays(2)->setTime(14, 0),
            'estimated_end_time' => now()->addDays(2)->setTime(16, 0),
        ]);

        $this->assertTrue($result);
        $surgery->refresh();
        $this->assertEquals(now()->addDays(2)->format('Y-m-d'), $surgery->scheduled_date->format('Y-m-d'));
    }

    #[Test]
    public function it_resets_postponed_status_on_reschedule(): void
    {
        $surgery = $this->createTestSurgery([
            'status' => 'scheduled',
            'is_postponed' => true,
            'postponed_reason' => 'Previous reason',
        ]);

        $this->service->rescheduleSurgery($surgery->id, [
            'scheduled_date' => now()->addDays(2),
        ]);

        $surgery->refresh();
        $this->assertFalse($surgery->is_postponed);
        $this->assertNull($surgery->postponed_reason);
    }

    #[Test]
    public function it_detects_conflict_when_rescheduling(): void
    {
        $date = now()->addDay();
        $visit1 = $this->createTestVisit();
        $visit2 = $this->createTestVisit();

        // Create first surgery
        $this->service->scheduleSurgery([
            'visit_id' => $visit1->id,
            'patient_id' => $visit1->patient_id,
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(9, 0),
            'estimated_end_time' => $date->copy()->setTime(11, 0),
            'operating_room' => 'OK1',
            'procedure_name' => 'First Surgery',
            'surgery_type' => 'elektif',
        ]);

        // Create second surgery
        $surgery2 = $this->service->scheduleSurgery([
            'visit_id' => $visit2->id,
            'patient_id' => $visit2->patient_id,
            'scheduled_date' => now()->addDays(2),
            'start_time' => now()->addDays(2)->setTime(13, 0),
            'estimated_end_time' => now()->addDays(2)->setTime(15, 0),
            'operating_room' => 'OK2',
            'procedure_name' => 'Second Surgery',
            'surgery_type' => 'elektif',
        ]);

        // Try to reschedule second surgery to conflict with first
        $result = $this->service->rescheduleSurgery($surgery2->id, [
            'scheduled_date' => $date,
            'start_time' => $date->copy()->setTime(10, 0),
            'estimated_end_time' => $date->copy()->setTime(12, 0),
            'operating_room' => 'OK1',
        ]);

        $this->assertFalse($result);
    }
}
