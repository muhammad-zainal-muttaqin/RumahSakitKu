<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class PatientDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Pasien per Poliklinik';

    protected ?string $description = 'Jumlah kunjungan pasien berdasarkan poliklinik';

    protected static int|null $sort = 3;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public ?string $period = 'today';

    protected function getData(): array
    {
        $reportService = app(ReportService::class);
        $dateRange = $reportService->getDateRange($this->period ?? 'today');
        
        $cacheKey = 'patient_distribution_' . $dateRange['start']->format('Ymd') . '_' . $dateRange['end']->format('Ymd');
        
        $distribution = Cache::remember($cacheKey, 300, function () use ($reportService, $dateRange) {
            return $reportService->getPatientDistributionByPolyclinic($dateRange['start'], $dateRange['end']);
        });

        // Take top 8 polyclinics
        $topPolyclinics = $distribution->take(8);
        
        $colors = [
            '#3b82f6',
            '#ef4444',
            '#10b981',
            '#f59e0b',
            '#8b5cf6',
            '#ec4899',
            '#06b6d4',
            '#84cc16',
        ];

        return [
            'datasets' => [
                [
                    'data' => $topPolyclinics->pluck('count')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $topPolyclinics->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $topPolyclinics->pluck('polyclinic')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'boxWidth' => 12,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
        ];
    }
}
