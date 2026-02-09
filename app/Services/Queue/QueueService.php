<?php

declare(strict_types=1);

namespace App\Services\Queue;

use Exception;
use RuntimeException;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queue Service
 *
 * Manages patient queue operations including queue number generation,
 * queue creation, calling, skipping, completion, and display data
 * for polyclinic queue management screens.
 */
class QueueService
{
    /**
     * Generate the next queue number for a polyclinic.
     *
     * Format: {queue_prefix}{sequential_number} (e.g., "A001", "B002")
     * The sequential number resets daily.
     *
     * @param int $polyclinicId The polyclinic ID
     * @return string The generated queue display number
     */
    public function generateQueueNumber(int $polyclinicId): string
    {
        try {
            $polyclinic = Polyclinic::find($polyclinicId);

            if (!$polyclinic) {
                Log::error('QueueService: Polyclinic not found', [
                    'polyclinic_id' => $polyclinicId,
                ]);
                return 'Q001';
            }

            $prefix = $polyclinic->queue_prefix ?? 'Q';

            // Get the last queue number for this polyclinic today
            $lastQueue = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', today())
                ->orderBy('queue_number', 'desc')
                ->first();

            $nextNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

            return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            Log::error('QueueService: Error generating queue number', [
                'polyclinic_id' => $polyclinicId,
                'error' => $e->getMessage(),
            ]);

            return 'Q001';
        }
    }

    /**
     * Create a queue entry for a visit.
     *
     * @param int $visitId The visit ID
     * @param int $polyclinicId The polyclinic ID
     * @return VisitQueue The created queue entry
     *
     * @throws RuntimeException If the queue entry could not be created
     */
    public function createQueue(int $visitId, int $polyclinicId): VisitQueue
    {
        try {
            DB::beginTransaction();

            $visit = Visit::find($visitId);

            if (!$visit) {
                DB::rollBack();
                throw new RuntimeException("Visit with ID {$visitId} not found.");
            }

            $polyclinic = Polyclinic::find($polyclinicId);

            if (!$polyclinic) {
                DB::rollBack();
                throw new RuntimeException("Polyclinic with ID {$polyclinicId} not found.");
            }

            // Check if the polyclinic has reached its daily quota
            if ($polyclinic->has_reached_quota) {
                DB::rollBack();
                throw new RuntimeException("Polyclinic {$polyclinic->name} has reached its daily queue quota.");
            }

            // Check if a queue already exists for this visit and polyclinic today
            $existingQueue = VisitQueue::where('visit_id', $visitId)
                ->where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', today())
                ->first();

            if ($existingQueue) {
                DB::rollBack();
                throw new RuntimeException('A queue entry already exists for this visit today.');
            }

            // Get the next sequential queue number
            $lastQueue = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', today())
                ->orderBy('queue_number', 'desc')
                ->first();

            $queueNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;
            $displayNumber = $this->generateQueueNumber($polyclinicId);

            $queue = VisitQueue::create([
                'visit_id' => $visitId,
                'patient_id' => $visit->patient_id,
                'polyclinic_id' => $polyclinicId,
                'queue_number' => $queueNumber,
                'display_number' => $displayNumber,
                'status' => 'waiting',
            ]);

            DB::commit();

            Log::info('QueueService: Queue entry created successfully', [
                'queue_id' => $queue->id,
                'visit_id' => $visitId,
                'polyclinic_id' => $polyclinicId,
                'display_number' => $displayNumber,
            ]);

            return $queue;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('QueueService: Error creating queue entry', [
                'visit_id' => $visitId,
                'polyclinic_id' => $polyclinicId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to create queue entry: ' . $e->getMessage());
        }
    }

    /**
     * Call the next queue entry for a polyclinic.
     *
     * Finds the next waiting queue (ordered by queue number) and marks it as called.
     *
     * @param int $polyclinicId The polyclinic ID
     * @param int|null $counterNumber Optional counter/window number
     * @return VisitQueue|null The called queue entry, or null if no waiting queues
     */
    public function callNextQueue(int $polyclinicId, ?int $counterNumber = null): ?VisitQueue
    {
        try {
            DB::beginTransaction();

            $nextQueue = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', today())
                ->where('status', 'waiting')
                ->orderBy('queue_number', 'asc')
                ->first();

            if (!$nextQueue) {
                DB::rollBack();
                Log::info('QueueService: No waiting queues available', [
                    'polyclinic_id' => $polyclinicId,
                ]);
                return null;
            }

            $counterString = $counterNumber !== null ? (string) $counterNumber : null;
            $nextQueue->markAsCalled($counterString);

            DB::commit();

            Log::info('QueueService: Next queue called', [
                'queue_id' => $nextQueue->id,
                'display_number' => $nextQueue->display_number,
                'polyclinic_id' => $polyclinicId,
                'counter' => $counterNumber,
            ]);

            return $nextQueue->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('QueueService: Error calling next queue', [
                'polyclinic_id' => $polyclinicId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Skip a queue entry.
     *
     * Marks the queue entry as skipped. Skipped entries can be recalled later.
     *
     * @param int $queueId The queue entry ID
     * @return bool True if the queue was skipped successfully
     */
    public function skipQueue(int $queueId): bool
    {
        try {
            DB::beginTransaction();

            $queue = VisitQueue::find($queueId);

            if (!$queue) {
                Log::error('QueueService: Queue entry not found', [
                    'queue_id' => $queueId,
                ]);
                DB::rollBack();
                return false;
            }

            if (!$queue->can_be_skipped) {
                Log::warning('QueueService: Queue entry cannot be skipped', [
                    'queue_id' => $queueId,
                    'status' => $queue->status,
                ]);
                DB::rollBack();
                return false;
            }

            $queue->markAsSkipped();

            DB::commit();

            Log::info('QueueService: Queue entry skipped', [
                'queue_id' => $queueId,
                'display_number' => $queue->display_number,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('QueueService: Error skipping queue entry', [
                'queue_id' => $queueId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark a queue entry as completed.
     *
     * @param int $queueId The queue entry ID
     * @return bool True if the queue was completed successfully
     */
    public function completeQueue(int $queueId): bool
    {
        try {
            DB::beginTransaction();

            $queue = VisitQueue::find($queueId);

            if (!$queue) {
                Log::error('QueueService: Queue entry not found', [
                    'queue_id' => $queueId,
                ]);
                DB::rollBack();
                return false;
            }

            if (!$queue->can_be_completed) {
                Log::warning('QueueService: Queue entry cannot be completed', [
                    'queue_id' => $queueId,
                    'status' => $queue->status,
                ]);
                DB::rollBack();
                return false;
            }

            $queue->markAsCompleted();

            DB::commit();

            Log::info('QueueService: Queue entry completed', [
                'queue_id' => $queueId,
                'display_number' => $queue->display_number,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('QueueService: Error completing queue entry', [
                'queue_id' => $queueId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get queue statistics for a polyclinic (today).
     *
     * Returns the count of waiting, served (called/in_progress), completed,
     * and skipped queues, plus average wait time.
     *
     * @param int $polyclinicId The polyclinic ID
     * @return array Queue statistics
     */
    public function getQueueStats(int $polyclinicId): array
    {
        try {
            $today = today();

            $waitingCount = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->where('status', 'waiting')
                ->count();

            $servedCount = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['called', 'in_progress'])
                ->count();

            $completedCount = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->count();

            $skippedCount = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->where('status', 'skipped')
                ->count();

            $totalCount = VisitQueue::where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->count();

            // Calculate average wait time from completed/called queues
            $avgWaitTime = (float) DB::table('visit_queues')
                ->where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->whereNotNull('called_at')
                ->whereNull('deleted_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait')
                ->value('avg_wait') ?? 0.0;

            return [
                'polyclinic_id' => $polyclinicId,
                'date' => $today->toDateString(),
                'total' => $totalCount,
                'waiting' => $waitingCount,
                'served' => $servedCount,
                'completed' => $completedCount,
                'skipped' => $skippedCount,
                'avg_wait_time_minutes' => round($avgWaitTime, 1),
            ];
        } catch (Exception $e) {
            Log::error('QueueService: Error getting queue stats', [
                'polyclinic_id' => $polyclinicId,
                'error' => $e->getMessage(),
            ]);

            return [
                'polyclinic_id' => $polyclinicId,
                'date' => today()->toDateString(),
                'total' => 0,
                'waiting' => 0,
                'served' => 0,
                'completed' => 0,
                'skipped' => 0,
                'avg_wait_time_minutes' => 0.0,
            ];
        }
    }

    /**
     * Get data for the queue display screen.
     *
     * Returns the currently called queue, next in line,
     * and the full waiting list for a polyclinic.
     *
     * @param int $polyclinicId The polyclinic ID
     * @return array Display data with current, next, and waiting list
     */
    public function getDisplayData(int $polyclinicId): array
    {
        try {
            $polyclinic = Polyclinic::find($polyclinicId);

            if (!$polyclinic) {
                Log::warning('QueueService: Polyclinic not found for display data', [
                    'polyclinic_id' => $polyclinicId,
                ]);

                return [
                    'polyclinic' => null,
                    'current' => null,
                    'next' => null,
                    'waiting_list' => [],
                    'stats' => [],
                ];
            }

            $today = today();

            // Currently being served (called or in_progress)
            $current = VisitQueue::with(['patient', 'visit'])
                ->where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['called', 'in_progress'])
                ->orderBy('called_at', 'desc')
                ->first();

            // Next waiting queue
            $next = VisitQueue::with(['patient', 'visit'])
                ->where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->where('status', 'waiting')
                ->orderBy('queue_number', 'asc')
                ->first();

            // Full waiting list
            $waitingList = VisitQueue::with(['patient', 'visit'])
                ->where('polyclinic_id', $polyclinicId)
                ->whereDate('created_at', $today)
                ->where('status', 'waiting')
                ->orderBy('queue_number', 'asc')
                ->get();

            // Queue stats
            $stats = $this->getQueueStats($polyclinicId);

            return [
                'polyclinic' => [
                    'id' => $polyclinic->id,
                    'name' => $polyclinic->name,
                    'code' => $polyclinic->code,
                ],
                'current' => $current ? [
                    'id' => $current->id,
                    'display_number' => $current->display_number,
                    'patient_name' => $current->patient?->name,
                    'counter_number' => $current->counter_number,
                    'called_at' => $current->called_at?->format('H:i'),
                    'status' => $current->status,
                ] : null,
                'next' => $next ? [
                    'id' => $next->id,
                    'display_number' => $next->display_number,
                    'patient_name' => $next->patient?->name,
                ] : null,
                'waiting_list' => $waitingList->map(function (VisitQueue $queue) {
                    return [
                        'id' => $queue->id,
                        'display_number' => $queue->display_number,
                        'patient_name' => $queue->patient?->name,
                        'waiting_time' => $queue->waiting_time,
                    ];
                })->toArray(),
                'stats' => $stats,
            ];
        } catch (Exception $e) {
            Log::error('QueueService: Error getting display data', [
                'polyclinic_id' => $polyclinicId,
                'error' => $e->getMessage(),
            ]);

            return [
                'polyclinic' => null,
                'current' => null,
                'next' => null,
                'waiting_list' => [],
                'stats' => [],
            ];
        }
    }
}
