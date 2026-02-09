<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadiologyOrderResource\Widgets;

use App\Models\Clinical\RadiologyOrder;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class RadiologyStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayCount = RadiologyOrder::today()->count();
        $pendingCount = RadiologyOrder::where('status', 'pending')->count();
        $scheduledCount = RadiologyOrder::where('status', 'scheduled')->count();
        $inProgressCount = RadiologyOrder::where('status', 'in_progress')->count();
        $completedCount = RadiologyOrder::where('status', 'completed')->count();
        $reportedCount = RadiologyOrder::where('status', 'reported')->count();

        return [
            Stat::make('Hari Ini', $todayCount)
                ->description('Order radiologi hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->icon('heroicon-o-calendar'),

            Stat::make('Menunggu', $pendingCount)
                ->description('Order menunggu penjadwalan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Terjadwal', $scheduledCount)
                ->description('Order sudah dijadwalkan')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Sedang Dikerjakan', $inProgressCount)
                ->description('Pemeriksaan sedang berlangsung')
                ->descriptionIcon('heroicon-m-play')
                ->color('primary')
                ->icon('heroicon-o-play'),

            Stat::make('Selesai', $completedCount)
                ->description('Pemeriksaan selesai, menunggu bacaan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Sudah Dibaca', $reportedCount)
                ->description('Pemeriksaan sudah dibaca radiologis')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary')
                ->icon('heroicon-o-document-check'),
        ];
    }
}
