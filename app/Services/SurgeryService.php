<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\Clinical\Surgery;

/**
 * Surgery Service
 * 
 * Manages surgical procedure scheduling and workflow.
 * Handles operating room allocation and surgery lifecycle.
 */

use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurgeryService
{
    /**
     * Schedule a new surgery.
     *
     * @param array $data Surgery data
     * @return Surgery|null The created surgery or null on failure
     */
    public function scheduleSurgery(array $data): ?Surgery
    {
        try {
            DB::beginTransaction();

            // Generate surgery number
            $data['surgery_number'] = $this->generateSurgeryNumber();

            // Set default status
            $data['status'] = 'scheduled';

            // Check for room conflicts only when full time range is provided.
            if (
                !empty($data['operating_room'])
                && !empty($data['start_time'])
                && !empty($data['estimated_end_time'])
            ) {
                $start = Carbon::parse($data['start_time']);
                $end = Carbon::parse($data['estimated_end_time']);

                if ($this->hasRoomConflict(
                    $data['operating_room'],
                    $start,
                    $end
                )) {
                    Log::warning('SurgeryService: Room conflict detected', [
                        'room' => $data['operating_room'],
                        'start' => $start,
                        'end' => $end,
                    ]);
                    DB::rollBack();
                    return null;
                }
            }

            $surgery = Surgery::create($data);

            DB::commit();

            Log::info('SurgeryService: Surgery scheduled successfully', [
                'surgery_id' => $surgery->id,
                'surgery_number' => $surgery->surgery_number,
            ]);

            return $surgery;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error scheduling surgery', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return null;
        }
    }

    /**
     * Start a surgery.
     *
     * @param int $surgeryId The surgery ID
     * @return bool True if successful
     */
    public function startSurgery(int $surgeryId): bool
    {
        try {
            DB::beginTransaction();

            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                Log::error('SurgeryService: Surgery not found', [
                    'surgery_id' => $surgeryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if surgery can be started
            if (!in_array($surgery->status, ['scheduled', 'preparation'])) {
                Log::warning('SurgeryService: Cannot start surgery with current status', [
                    'surgery_id' => $surgeryId,
                    'current_status' => $surgery->status,
                ]);
                DB::rollBack();
                return false;
            }

            $surgery->update([
                'status' => 'in_progress',
                'actual_start' => now(),
            ]);

            DB::commit();

            Log::info('SurgeryService: Surgery started successfully', [
                'surgery_id' => $surgeryId,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error starting surgery', [
                'surgery_id' => $surgeryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Complete a surgery.
     *
     * @param int $surgeryId The surgery ID
     * @param array $data Completion data
     * @return bool True if successful
     */
    public function completeSurgery(int $surgeryId, array $data): bool
    {
        try {
            DB::beginTransaction();

            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                Log::error('SurgeryService: Surgery not found', [
                    'surgery_id' => $surgeryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if surgery can be completed
            if ($surgery->status !== 'in_progress') {
                Log::warning('SurgeryService: Cannot complete surgery with current status', [
                    'surgery_id' => $surgeryId,
                    'current_status' => $surgery->status,
                ]);
                DB::rollBack();
                return false;
            }

            // Check safety checklist completion for non-emergency surgeries
            if (!in_array($surgery->surgery_type, ['cito', 'emergency']) && !$surgery->is_safety_checklist_complete) {
                Log::warning('SurgeryService: Safety checklist not complete', [
                    'surgery_id' => $surgeryId,
                ]);
                // Note: We don't block completion, just log warning
            }

            $updateData = [
                'status' => 'completed',
                'actual_end' => now(),
            ];

            // Merge additional data
            if (!empty($data['post_diagnosis'])) {
                $updateData['post_diagnosis'] = $data['post_diagnosis'];
            }
            if (!empty($data['procedure_notes'])) {
                $updateData['procedure_notes'] = $data['procedure_notes'];
            }
            if (!empty($data['findings'])) {
                $updateData['findings'] = $data['findings'];
            }
            if (!empty($data['complications'])) {
                $updateData['complications'] = $data['complications'];
            }
            if (!empty($data['specimens'])) {
                $updateData['specimens'] = $data['specimens'];
            }
            if (!empty($data['safety_checklist_sign_out'])) {
                $updateData['safety_checklist_sign_out'] = true;
                $updateData['safety_checklist_sign_out_at'] = now();
            }

            $surgery->update($updateData);

            DB::commit();

            Log::info('SurgeryService: Surgery completed successfully', [
                'surgery_id' => $surgeryId,
                'duration_minutes' => $surgery->fresh()->duration,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error completing surgery', [
                'surgery_id' => $surgeryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Cancel a surgery.
     *
     * @param int $surgeryId The surgery ID
     * @param string|null $reason Cancellation reason
     * @param int|null $cancelledBy User ID who cancelled
     * @return bool True if successful
     */
    public function cancelSurgery(int $surgeryId, ?string $reason = null, ?int $cancelledBy = null): bool
    {
        try {
            DB::beginTransaction();

            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                Log::error('SurgeryService: Surgery not found', [
                    'surgery_id' => $surgeryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if surgery can be cancelled
            if (in_array($surgery->status, ['completed', 'cancelled'])) {
                Log::warning('SurgeryService: Cannot cancel surgery with current status', [
                    'surgery_id' => $surgeryId,
                    'status' => $surgery->status,
                ]);
                DB::rollBack();
                return false;
            }

            $surgery->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $reason,
            ]);

            DB::commit();

            Log::info('SurgeryService: Surgery cancelled successfully', [
                'surgery_id' => $surgeryId,
                'reason' => $reason,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error cancelling surgery', [
                'surgery_id' => $surgeryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Postpone a surgery.
     *
     * @param int $surgeryId The surgery ID
     * @param string|null $reason Postponement reason
     * @return bool True if successful
     */
    public function postponeSurgery(int $surgeryId, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                Log::error('SurgeryService: Surgery not found', [
                    'surgery_id' => $surgeryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if surgery can be postponed
            if (in_array($surgery->status, ['completed', 'cancelled', 'in_progress'])) {
                Log::warning('SurgeryService: Cannot postpone surgery with current status', [
                    'surgery_id' => $surgeryId,
                    'current_status' => $surgery->status,
                ]);
                DB::rollBack();
                return false;
            }

            $surgery->update([
                'is_postponed' => true,
                'postponed_reason' => $reason,
                'postponed_at' => now(),
            ]);

            DB::commit();

            Log::info('SurgeryService: Surgery postponed successfully', [
                'surgery_id' => $surgeryId,
                'reason' => $reason,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error postponing surgery', [
                'surgery_id' => $surgeryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get available time slots for a room on a specific date.
     *
     * @param string $date The date (Y-m-d)
     * @param string $room Operating room
     * @param int $durationMinutes Duration needed in minutes
     * @return array Available time slots
     */
    public function getAvailableSlots(string $date, string $room, int $durationMinutes = 120): array
    {
        $slots = [];
        $operatingHours = [
            'start' => '07:00',
            'end' => '20:00',
        ];

        $startOfDay = Carbon::parse("{$date} {$operatingHours['start']}");
        $endOfDay = Carbon::parse("{$date} {$operatingHours['end']}");

        // Get existing surgeries for this room and date
        $existingSurgeries = Surgery::where('operating_room', $room)
            ->whereDate('scheduled_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $currentTime = $startOfDay->copy();

        foreach ($existingSurgeries as $surgery) {
            $surgeryStart = Carbon::parse($surgery->start_time);
            $surgeryEnd = Carbon::parse($surgery->estimated_end_time);

            // Check if there's enough time before this surgery
            if ($currentTime->copy()->addMinutes($durationMinutes)->lte($surgeryStart)) {
                $slots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $surgeryStart->format('H:i'),
                ];
            }

            $currentTime = $surgeryEnd->copy();
        }

        // Check remaining time after last surgery
        if ($currentTime->copy()->addMinutes($durationMinutes)->lte($endOfDay)) {
            $slots[] = [
                'start' => $currentTime->format('H:i'),
                'end' => $endOfDay->format('H:i'),
            ];
        }

        return $slots;
    }

    /**
     * Check if there's a room conflict.
     *
     * @param string $room Operating room
     * @param Carbon $start Start time
     * @param Carbon $end End time
     * @param int|null $excludeSurgeryId Surgery ID to exclude (for updates)
     * @return bool True if conflict exists
     */
    public function hasRoomConflict(string $room, Carbon $start, Carbon $end, ?int $excludeSurgeryId = null): bool
    {
        return Surgery::overlapping($room, $start, $end, $excludeSurgeryId)->exists();
    }

    /**
     * Mark safety checklist item as complete.
     *
     * @param int $surgeryId The surgery ID
     * @param string $checklistItem One of: sign_in, time_out, sign_out
     * @return bool True if successful
     */
    public function completeSafetyChecklist(int $surgeryId, string $checklistItem): bool
    {
        try {
            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                return false;
            }

            $field = "safety_checklist_{$checklistItem}";
            $fieldAt = "safety_checklist_{$checklistItem}_at";

            if (!in_array($field, ['safety_checklist_sign_in', 'safety_checklist_time_out', 'safety_checklist_sign_out'])) {
                return false;
            }

            $surgery->update([
                $field => true,
                $fieldAt => now(),
            ]);

            Log::info('SurgeryService: Safety checklist completed', [
                'surgery_id' => $surgeryId,
                'checklist_item' => $checklistItem,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('SurgeryService: Error completing safety checklist', [
                'surgery_id' => $surgeryId,
                'checklist_item' => $checklistItem,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get surgery statistics.
     *
     * @param string|null $date Date to filter (null for today)
     * @return array Statistics data
     */
    public function getStatistics(?string $date = null): array
    {
        $date = $date ?? today()->format('Y-m-d');

        $todayQuery = Surgery::onDate($date);

        return [
            'total_today' => $todayQuery->count(),
            'scheduled' => $todayQuery->clone()->where('status', 'scheduled')->count(),
            'preparation' => $todayQuery->clone()->where('status', 'preparation')->count(),
            'in_progress' => $todayQuery->clone()->where('status', 'in_progress')->count(),
            'completed' => $todayQuery->clone()->where('status', 'completed')->count(),
            'cancelled' => $todayQuery->clone()->where('status', 'cancelled')->count(),
            'cito_emergency' => $todayQuery->clone()->cito()->count(),
            'by_room' => Surgery::onDate($date)
                ->selectRaw('operating_room, count(*) as count')
                ->groupBy('operating_room')
                ->pluck('count', 'operating_room')
                ->toArray(),
        ];
    }

    /**
     * Generate a unique surgery number.
     * Format: OK-YYYYMMDD-XXXX
     */
    public function generateSurgeryNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "OK-{$date}-";

        $lastSurgery = Surgery::where('surgery_number', 'like', "{$prefix}%")
            ->orderBy('surgery_number', 'desc')
            ->first();

        if ($lastSurgery) {
            $lastNumber = (int) substr($lastSurgery->surgery_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get daily schedule for all operating rooms.
     *
     * @param string|null $date Date to get schedule for
     * @return array Schedule data
     */
    public function getDailySchedule(?string $date = null): array
    {
        $date = $date ?? today()->format('Y-m-d');
        $rooms = Surgery::getOperatingRooms();

        $schedule = [];

        foreach ($rooms as $code => $name) {
            $surgeries = Surgery::onDate($date)
                ->where('operating_room', $code)
                ->whereNotIn('status', ['cancelled'])
                ->with(['patient', 'surgeon'])
                ->orderBy('start_time')
                ->get();

            $schedule[$code] = [
                'name' => $name,
                'surgeries' => $surgeries,
                'count' => $surgeries->count(),
                'in_progress' => $surgeries->where('status', 'in_progress')->count(),
                'completed' => $surgeries->where('status', 'completed')->count(),
            ];
        }

        return $schedule;
    }

    /**
     * Reschedule a surgery to a new time.
     *
     * @param int $surgeryId The surgery ID
     * @param array $newSchedule New schedule data
     * @return bool True if successful
     */
    public function rescheduleSurgery(int $surgeryId, array $newSchedule): bool
    {
        try {
            DB::beginTransaction();

            $surgery = Surgery::find($surgeryId);

            if (!$surgery) {
                Log::error('SurgeryService: Surgery not found for rescheduling', [
                    'surgery_id' => $surgeryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check for conflicts only when full time range is provided
            if (
                !empty($newSchedule['operating_room'])
                && !empty($newSchedule['start_time'])
                && !empty($newSchedule['estimated_end_time'])
            ) {
                $start = Carbon::parse($newSchedule['start_time']);
                $end = Carbon::parse($newSchedule['estimated_end_time']);

                if ($this->hasRoomConflict(
                    $newSchedule['operating_room'],
                    $start,
                    $end,
                    $surgeryId
                )) {
                    Log::warning('SurgeryService: Room conflict on reschedule', [
                        'surgery_id' => $surgeryId,
                        'room' => $newSchedule['operating_room'],
                    ]);
                    DB::rollBack();
                    return false;
                }
            }

            $updateData = [];

            if (!empty($newSchedule['scheduled_date'])) {
                $updateData['scheduled_date'] = $newSchedule['scheduled_date'];
            }
            if (!empty($newSchedule['start_time'])) {
                $updateData['start_time'] = $newSchedule['start_time'];
            }
            if (!empty($newSchedule['estimated_end_time'])) {
                $updateData['estimated_end_time'] = $newSchedule['estimated_end_time'];
            }
            if (!empty($newSchedule['operating_room'])) {
                $updateData['operating_room'] = $newSchedule['operating_room'];
            }

            // Reset postponed status if rescheduling
            if ($surgery->is_postponed) {
                $updateData['is_postponed'] = false;
                $updateData['postponed_reason'] = null;
            }

            $surgery->update($updateData);

            DB::commit();

            Log::info('SurgeryService: Surgery rescheduled successfully', [
                'surgery_id' => $surgeryId,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SurgeryService: Error rescheduling surgery', [
                'surgery_id' => $surgeryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
