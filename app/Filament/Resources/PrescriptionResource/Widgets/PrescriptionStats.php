<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionResource\Widgets;

use App\Models\Clinical\Prescription;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class PrescriptionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $draftCount = Prescription::where('status', 'draft')->count();
        $verifiedCount = Prescription::where('status', 'verified')->count();
        $processedCount = Prescription::where('status', 'processed')->count();
        $dispensedCount = Prescription::where('status', 'dispensed')->count();
        $cancelledCount = Prescription::where('status', 'cancelled')->count();

        $todayCount = Prescription::whereDate('prescription_date', today())->count();

        return [
            Stat::make('Draft', $draftCount)
                ->description('Resep menunggu verifikasi')
                ->descriptionIcon('heroicon-m-pencil')
                ->color('gray')
                ->icon('heroicon-o-document-text'),

            Stat::make('Menunggu', $verifiedCount)
                ->description('Resep terverifikasi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Diproses', $processedCount)
                ->description('Resep sedang diproses')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info')
                ->icon('heroicon-o-cog-6-tooth'),

            Stat::make('Selesai', $dispensedCount)
                ->description('Resep sudah dispensed')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('Dibatalkan', $cancelledCount)
                ->description('Resep dibatalkan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),

            Stat::make('Hari Ini', $todayCount)
                ->description('Resep dibuat hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary')
                ->icon('heroicon-o-calendar'),
        ];
    }
}
