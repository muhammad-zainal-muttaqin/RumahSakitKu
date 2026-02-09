<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Patient\Visit;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class TodayVisitsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayQuery = Visit::today();

        $totalToday = $todayQuery->count();
        $waiting = Visit::today()->where('status', 'waiting')->count();
        $inProgress = Visit::today()->where('status', 'in_progress')->count();
        $completed = Visit::today()->where('status', 'completed')->count();

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
