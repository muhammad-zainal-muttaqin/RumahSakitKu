<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\QueueResource;
use App\Models\Patient\VisitQueue as Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Queue Management API Controller.
 * 
 * Handles queue operations including display, calling, skipping,
 * and completing queue entries.
 */
class QueueController extends BaseController
{
    /**
     * Display current queues.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Queue::query()
            ->with(['patient', 'polyclinic', 'visit.doctor'])
            ->when($request->clinic_id, fn($q, $c) => $q->where('polyclinic_id', $c))
            ->when($request->doctor_id, fn($q, $d) => $q->whereHas('visit', fn ($vq) => $vq->where('doctor_id', $d)))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date, fn($q, $d) => $q->whereDate('created_at', $d))
            ->when($request->priority, fn($q, $p) => $q->whereHas('visit', fn ($vq) => $vq->whereIn('priority', ['urgent', 'emergency'])));

        // Default to today's queues if no date specified
        if (!$request->date && !$request->from_date) {
            $query->whereDate('created_at', today());
        }

        $queues = $query->orderBy('is_priority', 'desc')
            ->orderBy('queue_number')
            ->paginate($request->per_page ?? 30);

        return $this->paginateResponse($queues);
    }

    /**
     * Get queue display data for display screens.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function display(Request $request): JsonResponse
    {
        $request->validate([
            'clinic_id' => ['nullable', 'exists:polyclinics,id'],
        ]);

        $query = Queue::query()
            ->with(['patient:id,name', 'polyclinic:id,name', 'visit.doctor:id,name'])
            ->whereDate('created_at', today());

        if ($request->clinic_id) {
            $query->where('polyclinic_id', $request->clinic_id);
        }

        $current = (clone $query)
            ->whereIn('status', ['called', 'in_progress'])
            ->latest('called_at')
            ->first();

        $waiting = (clone $query)
            ->where('status', 'waiting')
            ->orderBy('is_priority', 'desc')
            ->orderBy('queue_number')
            ->limit(10)
            ->get();

        $completed = (clone $query)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->limit(5)
            ->get();

        $skipped = (clone $query)
            ->where('status', 'skipped')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $counts = (clone $query)->selectRaw('
            SUM(status = "waiting") as waiting,
            SUM(status = "completed") as completed,
            SUM(status = "skipped") as skipped
        ')->first();

        return $this->successResponse([
            'current' => $current ? new QueueResource($current) : null,
            'waiting' => QueueResource::collection($waiting),
            'completed' => QueueResource::collection($completed),
            'skipped' => QueueResource::collection($skipped),
            'total_waiting' => $counts->waiting ?? 0,
            'total_completed' => $counts->completed ?? 0,
            'total_skipped' => $counts->skipped ?? 0,
            'last_updated' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Call a queue entry.
     *
     * @param Request $request
     * @param Queue $queue
     * @return JsonResponse
     */
    public function call(Request $request, Queue $queue): JsonResponse
    {
        if (!in_array($queue->status, ['waiting', 'skipped'])) {
            return $this->errorResponse('Queue cannot be called', 422);
        }

        // Mark any currently called queues as skipped
        Queue::where('polyclinic_id', $queue->polyclinic_id)
            ->where('status', 'called')
            ->where('id', '!=', $queue->id)
            ->update([
                'status' => 'skipped',
            ]);

        $queue->update([
            'status' => 'called',
            'called_at' => now(),
            'counter_number' => $request->room_number ?? $queue->counter_number,
        ]);

        return $this->successResponse(
            new QueueResource($queue->fresh()->load(['patient', 'polyclinic', 'visit.doctor'])),
            'Queue called successfully'
        );
    }

    /**
     * Skip a queue entry.
     *
     * @param Request $request
     * @param Queue $queue
     * @return JsonResponse
     */
    public function skip(Request $request, Queue $queue): JsonResponse
    {
        if (!in_array($queue->status, ['waiting', 'called'])) {
            return $this->errorResponse('Queue cannot be skipped', 422);
        }

        $validated = $request->validate([
            'skip_reason' => ['nullable', 'string'],
        ]);

        $queue->update([
            'status' => 'skipped',
        ]);

        return $this->successResponse(
            new QueueResource($queue->fresh()),
            'Queue skipped successfully'
        );
    }

    /**
     * Complete a queue entry.
     *
     * @param Request $request
     * @param Queue $queue
     * @return JsonResponse
     */
    public function complete(Request $request, Queue $queue): JsonResponse
    {
        if (!in_array($queue->status, ['called', 'in_progress'])) {
            return $this->errorResponse('Queue must be in progress to complete', 422);
        }

        $queue->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $this->successResponse(
            new QueueResource($queue->fresh()),
            'Queue completed successfully'
        );
    }

    /**
     * Recall a skipped queue entry.
     *
     * @param Request $request
     * @param Queue $queue
     * @return JsonResponse
     */
    public function recall(Request $request, Queue $queue): JsonResponse
    {
        if ($queue->status !== 'skipped') {
            return $this->errorResponse('Only skipped queues can be recalled', 422);
        }

        $queue->update([
            'status' => 'waiting',
        ]);

        return $this->successResponse(
            new QueueResource($queue->fresh()),
            'Queue recalled successfully'
        );
    }

    /**
     * Get queue statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'clinic_id' => ['nullable', 'exists:polyclinics,id'],
        ]);

        $date = $request->date ?? today();

        $query = Queue::whereDate('created_at', $date);

        if ($request->clinic_id) {
            $query->where('polyclinic_id', $request->clinic_id);
        }

        $statsData = (clone $query)->selectRaw('
            COUNT(*) as total,
            SUM(status = "waiting") as waiting,
            SUM(status = "called") as called,
            SUM(status = "in_progress") as in_progress,
            SUM(status = "completed") as completed,
            SUM(status = "skipped") as skipped,
            SUM(status = "cancelled") as cancelled
        ')->first();

        $stats = [
            'date' => $date,
            'total' => $statsData->total ?? 0,
            'waiting' => $statsData->waiting ?? 0,
            'called' => $statsData->called ?? 0,
            'in_progress' => $statsData->in_progress ?? 0,
            'completed' => $statsData->completed ?? 0,
            'skipped' => $statsData->skipped ?? 0,
            'cancelled' => $statsData->cancelled ?? 0,
            'average_waiting_time' => $this->calculateAverageWaitingTime($query),
            'average_service_time' => $this->calculateAverageServiceTime($query),
            'by_clinic' => $this->getStatsByClinic($date),
            'by_hour' => $this->getStatsByHour($date),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Calculate average waiting time.
     *
     * @param Builder $query
     * @return float|null
     */
    private function calculateAverageWaitingTime($query): ?float
    {
        $avgWait = (clone $query)
            ->whereNotNull('called_at')
            ->whereNotNull('created_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait')
            ->value('avg_wait');

        return $avgWait !== null ? round((float) $avgWait, 2) : null;
    }

    /**
     * Calculate average service time.
     *
     * @param Builder $query
     * @return float|null
     */
    private function calculateAverageServiceTime($query): ?float
    {
        $avgService = (clone $query)
            ->whereNotNull('completed_at')
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_service')
            ->value('avg_service');

        return $avgService !== null ? round((float) $avgService, 2) : null;
    }

    /**
     * Get statistics grouped by clinic.
     *
     * @param string $date
     * @return array
     */
    private function getStatsByClinic(string $date): array
    {
        return Queue::whereDate('created_at', $date)
            ->selectRaw('polyclinic_id, count(*) as total, sum(case when status = "completed" then 1 else 0 end) as completed')
            ->groupBy('polyclinic_id')
            ->with('polyclinic:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'clinic' => $item->polyclinic?->name,
                    'total' => $item->total,
                    'completed' => $item->completed,
                ];
            })
            ->toArray();
    }

    /**
     * Get statistics grouped by hour.
     *
     * @param string $date
     * @return array
     */
    private function getStatsByHour(string $date): array
    {
        return Queue::whereDate('created_at', $date)
            ->selectRaw('HOUR(created_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [sprintf('%02d:00', $item->hour) => $item->total];
            })
            ->toArray();
    }
}
