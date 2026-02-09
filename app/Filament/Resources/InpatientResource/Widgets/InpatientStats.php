<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Widgets;

use BackedEnum;
use Filament\Support\Enums\IconPosition;
use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class InpatientStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Total patients currently admitted
        $totalAdmitted = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereIn('inpatient_status', ['admitted', 'transferred'])
            ->count();

        // Count by room class
        $vvipVipCount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereHas('bed.room', fn ($q) => $q->whereIn('room_class', ['VVIP', 'VIP']))
            ->count();

        $classICount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereHas('bed.room', fn ($q) => $q->where('room_class', 'Kelas I'))
            ->count();

        $classIICount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereHas('bed.room', fn ($q) => $q->where('room_class', 'Kelas II'))
            ->count();

        $classIIICount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereHas('bed.room', fn ($q) => $q->where('room_class', 'Kelas III'))
            ->count();

        $icuCount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereHas('bed.room', fn ($q) => $q->whereIn('room_class', ['ICU', 'NICU', 'PICU', 'HCU']))
            ->count();

        // Planned discharge today
        $plannedDischargeToday = Visit::where('visit_type', 'rawat_inap')
            ->where('inpatient_status', 'discharge_planned')
            ->whereDate('planned_discharge_date', today())
            ->count();

        // Long stay (>7 days)
        $longStayCount = Visit::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
            ->whereDate('visit_date', '<=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Total Dirawat', $totalAdmitted)
                ->description('Pasien aktif saat ini')
                ->descriptionIcon('heroicon-m-users', IconPosition::Before)
                ->color('primary')
                ->icon('heroicon-o-home'),

            Stat::make('VVIP / VIP', $vvipVipCount)
                ->description('Kamar kelas premium')
                ->descriptionIcon('heroicon-m-star', IconPosition::Before)
                ->color('warning')
                ->icon('heroicon-o-star'),

            Stat::make('Kelas I', $classICount)
                ->description('Kamar kelas I')
                ->descriptionIcon('heroicon-m-building-office', IconPosition::Before)
                ->color('info')
                ->icon('heroicon-o-building-office'),

            Stat::make('Kelas II', $classIICount)
                ->description('Kamar kelas II')
                ->descriptionIcon('heroicon-m-building-office-2', IconPosition::Before)
                ->color('success')
                ->icon('heroicon-o-building-office-2'),

            Stat::make('Kelas III', $classIIICount)
                ->description('Kamar kelas III')
                ->descriptionIcon('heroicon-m-home', IconPosition::Before)
                ->color('gray')
                ->icon('heroicon-o-home'),

            Stat::make('ICU/HCU', $icuCount)
                ->description('Intensive Care')
                ->descriptionIcon('heroicon-m-heart', IconPosition::Before)
                ->color('danger')
                ->icon('heroicon-o-heart'),

            Stat::make('Rencana Pulang Hari Ini', $plannedDischargeToday)
                ->description('Pasien rencana pulang')
                ->descriptionIcon('heroicon-m-calendar', IconPosition::Before)
                ->color($plannedDischargeToday > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-calendar'),

            Stat::make('LOS > 7 Hari', $longStayCount)
                ->description('Rawat inap lama')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color($longStayCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
