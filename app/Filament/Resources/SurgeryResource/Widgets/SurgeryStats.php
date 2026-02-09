<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Widgets;

use App\Models\Clinical\Surgery;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class SurgeryStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayQuery = Surgery::today();

        $todayTotal = $todayQuery->count();
        $scheduled = Surgery::where('status', 'scheduled')->count();
        $inProgress = Surgery::where('status', 'in_progress')->count();
        $completed = Surgery::today()->where('status', 'completed')->count();
        $citoCount = Surgery::cito()->whereNotIn('status', ['completed', 'cancelled'])->count();

        return [
            Stat::make('Hari Ini', $todayTotal)
                ->description('Jadwal operasi hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->icon('heroicon-o-calendar')
                ->chart($this->getWeeklyChartData()),

            Stat::make('Terjadwal', $scheduled)
                ->description('Menunggu jadwal operasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->icon('heroicon-o-calendar'),

            Stat::make('Sedang Berlangsung', $inProgress)
                ->description('Operasi aktif saat ini')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning')
                ->icon('heroicon-o-play'),

            Stat::make('Selesai', $completed)
                ->description('Operasi selesai hari ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('CITO/Emergency', $citoCount)
                ->description('Kasus darurat menunggu')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger')
                ->icon('heroicon-o-bolt'),
        ];
    }

    /**
     * Get weekly chart data for the main stat.
     */
    private function getWeeklyChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Surgery::whereDate('scheduled_date', $date)
                ->whereNotIn('status', ['cancelled'])
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
