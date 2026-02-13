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
        $allQueues = VisitQueue::today()
            ->with(['patient', 'polyclinic'])
            ->get()
            ->groupBy('polyclinic_id');

        $polyclinics = Polyclinic::active()->orderBy('name')->get();

        $avgServiceTimes = VisitQueue::today()
            ->whereNotNull('completed_at')
            ->whereNotNull('called_at')
            ->selectRaw('polyclinic_id, AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_time')
            ->groupBy('polyclinic_id')
            ->pluck('avg_time', 'polyclinic_id');

        return $polyclinics->map(function ($polyclinic) use ($allQueues, $avgServiceTimes) {
            $polyQueues = $allQueues->get($polyclinic->id, collect());
            
            $currentQueue = $polyQueues->whereIn('status', ['called', 'in_progress'])->sortByDesc('called_at')->first();
            $waitingCount = $polyQueues->where('status', 'waiting')->count();
            $estimatedTime = $this->calculateEstimatedTime($polyclinic->id, $waitingCount, $avgServiceTimes);

            return [
                'polyclinic' => $polyclinic,
                'current' => $currentQueue,
                'waiting_count' => $waitingCount,
                'estimated_time' => $estimatedTime,
            ];
        });
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
        $allQueues = VisitQueue::today()
            ->with(['patient', 'polyclinic'])
            ->whereIn('status', ['waiting', 'skipped'])
            ->orderBy('queue_number')
            ->get()
            ->groupBy('polyclinic_id');

        return $allQueues->map(function ($queues) {
            return $queues->first();
        })->filter();
    }

    private function calculateEstimatedTime(int $polyclinicId, int $waitingCount, ?Collection $avgServiceTimes = null): int
    {
        if ($waitingCount === 0) {
            return 0;
        }

        $avgServiceTime = $avgServiceTimes?->get($polyclinicId) ?? 15;

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
