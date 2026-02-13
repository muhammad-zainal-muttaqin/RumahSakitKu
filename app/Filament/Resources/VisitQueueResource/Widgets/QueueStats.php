<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitQueueResource\Widgets;

use App\Models\MasterData\Polyclinic;
use App\Models\Patient\VisitQueue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class QueueStats extends BaseWidget
{
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $stats = [];

        // Global stats
        $stats[] = Stat::make('Total Antrian Hari Ini', VisitQueue::today()->count())
            ->description('Seluruh poliklinik')
            ->descriptionIcon('heroicon-m-queue-list')
            ->color('primary');

        $stats[] = Stat::make('Menunggu', VisitQueue::today()->waiting()->count())
            ->description('Pasien dalam antrian')
            ->descriptionIcon('heroicon-m-clock')
            ->color('warning');

        $stats[] = Stat::make('Sedang Dilayani', VisitQueue::today()->where('status', 'in_progress')->count())
            ->description('Pasien aktif')
            ->descriptionIcon('heroicon-m-user')
            ->color('info');

        $stats[] = Stat::make('Selesai', VisitQueue::today()->completed()->count())
            ->description('Telah dilayani')
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');

        // Stats per polyclinic
        $polyclinics = Polyclinic::active()->orderBy('name')->get();

        $statsByPolyclinic = VisitQueue::today()
            ->selectRaw('polyclinic_id, 
                COUNT(*) as total,
                SUM(CASE WHEN status = "waiting" THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = "called" THEN 1 ELSE 0 END) as called,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "skipped" THEN 1 ELSE 0 END) as skipped
            ')
            ->groupBy('polyclinic_id')
            ->get()
            ->keyBy('polyclinic_id');

        foreach ($polyclinics as $polyclinic) {
            $polyStats = $statsByPolyclinic->get($polyclinic->id);

            if ($polyStats && $polyStats->total > 0) {
                $stats[] = Stat::make($polyclinic->name, $polyStats->total)
                    ->description("M: {$polyStats->waiting} | D: {$polyStats->called} | S: {$polyStats->completed}")
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color($polyStats->waiting > 10 ? 'danger' : ($polyStats->waiting > 5 ? 'warning' : 'success'));
            }
        }

        return $stats;
    }

    /**
     * Get stats for a specific polyclinic
     */
    public static function getPolyclinicStats(int $polyclinicId): Collection
    {
        $stats = VisitQueue::today()
            ->where('polyclinic_id', $polyclinicId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "waiting" THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = "called" THEN 1 ELSE 0 END) as called,
                SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "skipped" THEN 1 ELSE 0 END) as skipped
            ')
            ->first();

        return collect([
            'total' => $stats->total ?? 0,
            'waiting' => $stats->waiting ?? 0,
            'called' => $stats->called ?? 0,
            'in_progress' => $stats->in_progress ?? 0,
            'completed' => $stats->completed ?? 0,
            'skipped' => $stats->skipped ?? 0,
        ]);
    }

    /**
     * Get estimated waiting time for a polyclinic
     */
    public static function getEstimatedWaitingTime(int $polyclinicId): int
    {
        $avgServiceTime = VisitQueue::today()
            ->where('polyclinic_id', $polyclinicId)
            ->whereNotNull('completed_at')
            ->whereNotNull('called_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, completed_at)) as avg_time')
            ->value('avg_time') ?? 15; // Default 15 minutes

        $waitingCount = VisitQueue::today()
            ->waiting()
            ->where('polyclinic_id', $polyclinicId)
            ->count();

        return (int) ceil($avgServiceTime * $waitingCount);
    }
}
