<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Widgets;

use App\Models\Patient\Visit;
use App\Services\TriageService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TriageStats extends BaseWidget
{
    protected int | array | null $columns = 5;

    protected function getStats(): array
    {
        $baseQuery = Visit::query()
            ->where('visit_type', 'igd')
            ->whereDate('visit_date', today());

        $totalPatients = (clone $baseQuery)->count();

        $redCount = (clone $baseQuery)
            ->whereHas('medicalRecord.assessments', function (Builder $query) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', TriageService::CATEGORY_RED);
            })
            ->count();

        $yellowCount = (clone $baseQuery)
            ->whereHas('medicalRecord.assessments', function (Builder $query) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', TriageService::CATEGORY_YELLOW);
            })
            ->count();

        $greenCount = (clone $baseQuery)
            ->whereHas('medicalRecord.assessments', function (Builder $query) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', TriageService::CATEGORY_GREEN);
            })
            ->count();

        $inProgressCount = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->count();

        return [
            Stat::make('Total Pasien IGD', $totalPatients)
                ->description('Total pasien IGD hari ini')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([$totalPatients > 0 ? $totalPatients : 1]),

            Stat::make('Triase Merah (Emergency)', $redCount)
                ->description('Segera ditangani')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'ring-2 ring-danger-500',
                ]),

            Stat::make('Triase Kuning (Urgent)', $yellowCount)
                ->description('Ditangani < 60 menit')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Triase Hijau (Non-Urgent)', $greenCount)
                ->description('Ditangani > 60 menit')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sedang Dilayani', $inProgressCount)
                ->description('Pasien dalam penanganan')
                ->descriptionIcon('heroicon-m-play')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
