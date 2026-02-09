<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitQueueResource\Widgets;

use App\Models\MasterData\Polyclinic;
use App\Models\Patient\VisitQueue;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class LiveQueueDisplay extends Widget
{
    protected string $view = 'filament.resources.visit-queue-resource.widgets.live-queue-display';

    protected ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = 'full';

    public function getCurrentQueues(): Collection
    {
        $polyclinics = Polyclinic::active()->orderBy('name')->get();
        $queues = collect();

        foreach ($polyclinics as $polyclinic) {
            $currentQueue = VisitQueue::today()
                ->with(['patient'])
                ->where('polyclinic_id', $polyclinic->id)
                ->whereIn('status', ['called', 'in_progress'])
                ->latest('called_at')
                ->first();

            $waitingCount = VisitQueue::today()
                ->waiting()
                ->where('polyclinic_id', $polyclinic->id)
                ->count();

            $estimatedTime = $this->calculateEstimatedTime($polyclinic->id, $waitingCount);

            $queues->push([
                'polyclinic' => $polyclinic,
                'current' => $currentQueue,
                'waiting_count' => $waitingCount,
                'estimated_time' => $estimatedTime,
            ]);
        }

        return $queues;
    }

    public function getRecentCalls(): Collection
    {
        return VisitQueue::today()
            ->with(['patient', 'polyclinic'])
            ->whereNotNull('called_at')
            ->orderBy('called_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getNextInLine(): Collection
    {
        $polyclinics = Polyclinic::active()->pluck('id');
        $nextQueues = collect();

        foreach ($polyclinics as $polyclinicId) {
            $next = VisitQueue::today()
                ->with(['patient', 'polyclinic'])
                ->where('polyclinic_id', $polyclinicId)
                ->whereIn('status', ['waiting', 'skipped'])
                ->orderBy('queue_number')
                ->first();

            if ($next) {
                $nextQueues->push($next);
            }
        }

        return $nextQueues;
    }

    private function calculateEstimatedTime(int $polyclinicId, int $waitingCount): int
    {
        if ($waitingCount === 0) {
            return 0;
        }

        $avgServiceTime = VisitQueue::today()
            ->where('polyclinic_id', $polyclinicId)
            ->whereNotNull('completed_at')
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_time')
            ->value('avg_time') ?? 15;

        return (int) ceil($avgServiceTime * $waitingCount);
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'waiting' => 'gray',
            'called' => 'warning',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'skipped' => 'orange',
            default => 'gray',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'skipped' => 'Dilewati',
            default => $status,
        };
    }

    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'waiting' => 'heroicon-o-clock',
            'called' => 'heroicon-o-speaker-wave',
            'in_progress' => 'heroicon-o-user',
            'completed' => 'heroicon-o-check-circle',
            'cancelled' => 'heroicon-o-x-circle',
            'skipped' => 'heroicon-o-forward',
            default => 'heroicon-o-question-mark-circle',
        };
    }
}
