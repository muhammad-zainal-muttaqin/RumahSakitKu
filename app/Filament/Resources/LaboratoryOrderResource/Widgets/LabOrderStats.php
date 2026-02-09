<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\Widgets;

use App\Models\Clinical\LaboratoryOrder;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class LabOrderStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayCount = LaboratoryOrder::today()->count();
        $pendingCount = LaboratoryOrder::where('status', 'pending')->count();
        $inProgressCount = LaboratoryOrder::where('status', 'in_progress')->count();
        $completedCount = LaboratoryOrder::where('status', 'completed')->count();
        $validatedCount = LaboratoryOrder::where('status', 'validated')->count();
        $citoCount = LaboratoryOrder::where('is_cito', true)
            ->whereNotIn('status', ['validated', 'cancelled'])
            ->count();

        return [
            Stat::make('Hari Ini', $todayCount)
                ->description('Order laboratorium hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->icon('heroicon-o-calendar'),

            Stat::make('Pending', $pendingCount)
                ->description('Order menunggu diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Diproses', $inProgressCount)
                ->description('Order sedang dikerjakan')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info')
                ->icon('heroicon-o-cog-6-tooth'),

            Stat::make('Selesai', $completedCount)
                ->description('Order selesai, menunggu validasi')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Divalidasi', $validatedCount)
                ->description('Order sudah divalidasi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary')
                ->icon('heroicon-o-check-badge'),

            Stat::make('CITO', $citoCount)
                ->description('Order darurat yang aktif')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger')
                ->icon('heroicon-o-bolt')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->when($citoCount > 0, fn (Stat $stat) => $stat->extraAttributes(['class' => 'ring-2 ring-danger-500'])),
        ];
    }
}
