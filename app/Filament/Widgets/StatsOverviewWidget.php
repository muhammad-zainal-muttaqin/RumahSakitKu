<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Patient\Visit;
use App\Models\MasterData\Procedure;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public ?string $period = 'today';

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $dateRange = $reportService->getDateRange($this->period ?? 'today');
        
        $cacheKey = "stats_overview_{$this->period}_" . $dateRange['start']->format('Ymd');
        
        return Cache::remember($cacheKey, 60, function () use ($reportService, $dateRange) {
            $visitCounts = $reportService->getVisitCountsByType($dateRange['start'], $dateRange['end']);
            
            // Current inpatient count
            $currentInpatients = Visit::where('visit_type', 'rawat_inap')
                ->whereNull('discharge_date')
                ->count();
            
            // Surgery count (assuming we track surgeries in procedures or visits)
            $surgeryCount = Procedure::whereHas('category', function ($q) {
                $q->where('name', 'like', '%bedah%')
                    ->orWhere('name', 'like', '%operasi%');
            })->count(); // This would need adjustment based on actual surgery tracking
            
            // Revenue
            $revenue = $reportService->getRevenueByPaymentMethod($dateRange['start'], $dateRange['end']);

            return [
                Stat::make('Kunjungan', number_format($visitCounts['total']))
                    ->description($reportService->formatPeriodLabel($this->period))
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('primary')
                    ->icon('heroicon-o-users')
                    ->chart([
                        $visitCounts['rawat_jalan'],
                        $visitCounts['rawat_inap'],
                        $visitCounts['igd'],
                        $visitCounts['mcu'],
                    ]),

                Stat::make('Pasien Dirawat', number_format($currentInpatients))
                    ->description('Pasien aktif saat ini')
                    ->descriptionIcon('heroicon-m-home')
                    ->color('warning')
                    ->icon('heroicon-o-home'),

                Stat::make('Operasi', number_format($surgeryCount))
                    ->description('Total tindakan bedah')
                    ->descriptionIcon('heroicon-m-heart')
                    ->color('danger')
                    ->icon('heroicon-o-heart'),

                Stat::make('Pendapatan', 'Rp ' . number_format($revenue['total'], 0, ',', '.'))
                    ->description('Total pendapatan periode ini')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('success')
                    ->icon('heroicon-o-banknotes'),
            ];
        });
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
