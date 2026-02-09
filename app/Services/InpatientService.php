<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Models\MasterData\Bed;

/**
 * Inpatient Service
 * 
 * Manages inpatient admission, transfer, and discharge workflows.
 * Handles bed allocation and room management for hospitalized patients.
 */

use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InpatientService
{
    /**
     * Admit a patient to a room/bed.
     *
     * @param int $visitId The visit ID
     * @param int $roomId The room ID
     * @param int $bedId The bed ID
     * @return bool True if successful
     */
    public function admitPatient(int $visitId, int $roomId, int $bedId): bool
    {
        try {
            DB::beginTransaction();

            $visit = Visit::find($visitId);
            $bed = Bed::find($bedId);
            $room = Room::find($roomId);

            if (!$visit || !$bed || !$room) {
                Log::error('InpatientService: Invalid visit, bed, or room ID', [
                    'visit_id' => $visitId,
                    'room_id' => $roomId,
                    'bed_id' => $bedId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if bed is available
            if ($bed->status !== 'kosong') {
                Log::warning('InpatientService: Bed is not available', [
                    'bed_id' => $bedId,
                    'status' => $bed->status,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if bed belongs to the room
            if ($bed->room_id !== $room->id) {
                Log::warning('InpatientService: Bed does not belong to room', [
                    'bed_id' => $bedId,
                    'room_id' => $roomId,
                    'bed_room_id' => $bed->room_id,
                ]);
                DB::rollBack();
                return false;
            }

            // Occupy the bed
            $bed->occupy($visitId);

            // Update visit
            $visit->update([
                'status' => 'in_progress',
                'inpatient_status' => 'admitted',
                'check_in_at' => now(),
            ]);

            DB::commit();

            Log::info('InpatientService: Patient admitted successfully', [
                'visit_id' => $visitId,
                'bed_id' => $bedId,
                'room_id' => $roomId,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('InpatientService: Error admitting patient', [
                'visit_id' => $visitId,
                'bed_id' => $bedId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Transfer a patient to a different bed.
     *
     * @param int $visitId The visit ID
     * @param int $newBedId The new bed ID
     * @param string|null $reason The reason for transfer
     * @return bool True if successful
     */
    public function transferPatient(int $visitId, int $newBedId, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $visit = Visit::find($visitId);
            $newBed = Bed::find($newBedId);

            if (!$visit || !$newBed) {
                Log::error('InpatientService: Invalid visit or bed ID', [
                    'visit_id' => $visitId,
                    'bed_id' => $newBedId,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if patient is currently admitted
            if (!in_array($visit->inpatient_status, ['admitted', 'registered', 'transferred'])) {
                Log::warning('InpatientService: Patient is not admitted', [
                    'visit_id' => $visitId,
                    'status' => $visit->inpatient_status,
                ]);
                DB::rollBack();
                return false;
            }

            // Check if new bed is available
            if ($newBed->status !== 'kosong') {
                Log::warning('InpatientService: New bed is not available', [
                    'bed_id' => $newBedId,
                    'status' => $newBed->status,
                ]);
                DB::rollBack();
                return false;
            }

            // Get current bed
            $currentBed = Bed::where('current_visit_id', $visitId)->first();

            if ($currentBed) {
                // Vacate current bed
                $currentBed->vacate();
            }

            // Occupy new bed
            $newBed->occupy($visitId);

            // Update visit
            $visit->update([
                'inpatient_status' => 'transferred',
                'transfer_reason' => $reason,
                'transferred_at' => now(),
            ]);

            DB::commit();

            Log::info('InpatientService: Patient transferred successfully', [
                'visit_id' => $visitId,
                'from_bed' => $currentBed?->id,
                'to_bed' => $newBedId,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('InpatientService: Error transferring patient', [
                'visit_id' => $visitId,
                'bed_id' => $newBedId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Discharge a patient.
     *
     * @param int $visitId The visit ID
     * @param array $dischargeData The discharge data
     * @return bool True if successful
     */
    public function dischargePatient(int $visitId, array $dischargeData): bool
    {
        try {
            DB::beginTransaction();

            $visit = Visit::find($visitId);

            if (!$visit) {
                Log::error('InpatientService: Invalid visit ID', [
                    'visit_id' => $visitId,
                ]);
                DB::rollBack();
                return false;
            }

            // Get current bed
            $currentBed = Bed::where('current_visit_id', $visitId)->first();

            if ($currentBed) {
                // Vacate bed
                $currentBed->vacate();
            }

            // Update visit
            $visit->update([
                'status' => 'completed',
                'inpatient_status' => 'discharged',
                'is_completed' => true,
                'check_out_at' => $dischargeData['discharge_date'] ?? now(),
                'discharge_date' => $dischargeData['discharge_date'] ?? now(),
                'discharge_status' => $dischargeData['discharge_status'] ?? null,
                'discharge_diagnosis' => $dischargeData['discharge_diagnosis'] ?? null,
                'discharge_notes' => $dischargeData['discharge_notes'] ?? null,
            ]);

            DB::commit();

            Log::info('InpatientService: Patient discharged successfully', [
                'visit_id' => $visitId,
                'discharge_status' => $dischargeData['discharge_status'] ?? null,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('InpatientService: Error discharging patient', [
                'visit_id' => $visitId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate length of stay in days.
     *
     * @param Carbon|string $admissionDate The admission date
     * @param Carbon|string|null $dischargeDate The discharge date (null if still admitted)
     * @return int Length of stay in days
     */
    public function calculateLengthOfStay(Carbon|string $admissionDate, Carbon|string|null $dischargeDate = null): int
    {
        if (is_string($admissionDate)) {
            $admissionDate = Carbon::parse($admissionDate);
        }

        if ($dischargeDate === null) {
            $dischargeDate = now();
        } elseif (is_string($dischargeDate)) {
            $dischargeDate = Carbon::parse($dischargeDate);
        }

        // Calculate difference in days (minimum 1 day)
        $days = $admissionDate->diffInDays($dischargeDate) + 1;

        return max(1, (int) $days);
    }

    /**
     * Get occupancy statistics.
     *
     * @return array Occupancy statistics
     */
    public function getOccupancyStats(): array
    {
        $totalBeds = Bed::where('is_active', true)->count();
        $occupiedBeds = Bed::where('status', 'terisi')->count();
        $availableBeds = Bed::where('status', 'kosong')->count();
        $maintenanceBeds = Bed::where('status', 'maintenance')->count();
        $cleaningBeds = Bed::where('status', 'cleaning')->count();

        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        return [
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => $availableBeds,
            'maintenance_beds' => $maintenanceBeds,
            'cleaning_beds' => $cleaningBeds,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    /**
     * Get inpatient statistics by room class.
     *
     * @return array Statistics by room class
     */
    public function getStatsByRoomClass(): array
    {
        $roomClasses = ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU'];
        $stats = [];

        foreach ($roomClasses as $class) {
            $rooms = Room::where('room_class', $class)->where('is_active', true)->pluck('id');
            
            $totalBeds = Bed::whereIn('room_id', $rooms)->where('is_active', true)->count();
            $occupiedBeds = Bed::whereIn('room_id', $rooms)->where('status', 'terisi')->count();
            $availableBeds = $totalBeds - $occupiedBeds;
            
            $stats[$class] = [
                'total' => $totalBeds,
                'occupied' => $occupiedBeds,
                'available' => $availableBeds,
                'occupancy_rate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Check if a bed is available.
     *
     * @param int $bedId The bed ID
     * @return bool True if available
     */
    public function isBedAvailable(int $bedId): bool
    {
        $bed = Bed::find($bedId);
        return $bed && $bed->status === 'kosong' && $bed->is_active;
    }

    /**
     * Get available beds for a room.
     *
     * @param int $roomId The room ID
     * @return Collection
     */
    public function getAvailableBeds(int $roomId)
    {
        return Bed::where('room_id', $roomId)
            ->where('status', 'kosong')
            ->where('is_active', true)
            ->get();
    }
}
