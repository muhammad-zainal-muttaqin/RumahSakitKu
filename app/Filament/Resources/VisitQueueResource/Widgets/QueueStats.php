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

        foreach ($polyclinics as $polyclinic) {
            $total = VisitQueue::today()->where('polyclinic_id', $polyclinic->id)->count();
            $waiting = VisitQueue::today()->waiting()->where('polyclinic_id', $polyclinic->id)->count();
            $called = VisitQueue::today()->called()->where('polyclinic_id', $polyclinic->id)->count();
            $completed = VisitQueue::today()->completed()->where('polyclinic_id', $polyclinic->id)->count();

            if ($total > 0) {
                $stats[] = Stat::make($polyclinic->name, $total)
                    ->description("M: {$waiting} | D: {$called} | S: {$completed}")
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color($waiting > 10 ? 'danger' : ($waiting > 5 ? 'warning' : 'success'));
            }
        }

        return $stats;
    }

    /**
     * Get stats for a specific polyclinic
     */
    public static function getPolyclinicStats(int $polyclinicId): Collection
    {
        $today = now()->toDateString();

        return collect([
            'total' => VisitQueue::today()->where('polyclinic_id', $polyclinicId)->count(),
            'waiting' => VisitQueue::today()->waiting()->where('polyclinic_id', $polyclinicId)->count(),
            'called' => VisitQueue::today()->called()->where('polyclinic_id', $polyclinicId)->count(),
            'in_progress' => VisitQueue::today()->where('status', 'in_progress')->where('polyclinic_id', $polyclinicId)->count(),
            'completed' => VisitQueue::today()->completed()->where('polyclinic_id', $polyclinicId)->count(),
            'skipped' => VisitQueue::today()->where('status', 'skipped')->where('polyclinic_id', $polyclinicId)->count(),
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
