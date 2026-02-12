<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Services\VisitMetricsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayVisitsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $counts = app(VisitMetricsService::class)->getTodayStatusCounts();

        $totalToday = $counts['total'] ?? 0;
        $waiting = $counts['waiting'] ?? 0;
        $inProgress = $counts['in_progress'] ?? 0;
        $completed = $counts['completed'] ?? 0;

        return [
            Stat::make('Total Hari Ini', $totalToday)
                ->description('Kunjungan pasien hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Menunggu', $waiting)
                ->description('Pasien dalam antrian')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Sedang Dilayani', $inProgress)
                ->description('Pasien dalam pelayanan')
                ->descriptionIcon('heroicon-m-play')
                ->color('info')
                ->icon('heroicon-o-play'),

            Stat::make('Selesai', $completed)
                ->description('Kunjungan selesai')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
        ];
    }
}
