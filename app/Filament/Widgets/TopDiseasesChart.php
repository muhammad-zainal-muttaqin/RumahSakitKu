<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TopDiseasesChart extends ChartWidget
{
    protected ?string $heading = '10 Penyakit Terbanyak';

    protected ?string $description = 'Berdasarkan diagnosis ICD-10';

    protected static int|null $sort = 6;

    protected ?string $maxHeight = '350px';

    protected ?string $pollingInterval = null;

    public ?string $period = 'month';

    protected function getData(): array
    {
        $reportService = app(ReportService::class);
        $dateRange = $reportService->getDateRange($this->period ?? 'month');
        
        $cacheKey = 'top_diseases_' . $dateRange['start']->format('Ymd') . '_' . $dateRange['end']->format('Ymd');
        
        $diseases = Cache::remember($cacheKey, 600, function () use ($reportService, $dateRange) {
            return $reportService->getTopDiseases($dateRange['start'], $dateRange['end'], 10);
        });

        // Reverse for horizontal bar (top at bottom)
        $diseases = $diseases->reverse();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kasus',
                    'data' => $diseases->pluck('count')->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $diseases->map(function ($item) {
                $name = $item['name'];
                if (strlen($name) > 30) {
                    $name = substr($name, 0, 27) . '...';
                }
                return $item['code'] . ' - ' . $name;
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'font' => [
                            'size' => 10,
                        ],
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
