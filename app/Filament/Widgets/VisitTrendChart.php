<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class VisitTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Kunjungan 7 Hari Terakhir';

    protected ?string $description = 'Kunjungan pasien berdasarkan jenis pelayanan';

    protected static int|null $sort = 2;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public ?string $period = 'week';

    protected function getData(): array
    {
        $reportService = app(ReportService::class);
        
        // Always show last 7 days for trend
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $cacheKey = sprintf(
            'visit_trend_%s_%s',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        $trend = Cache::remember($cacheKey, 300, fn () => $reportService->getDailyVisitTrend($startDate, $endDate));

        return [
            'datasets' => [
                [
                    'label' => 'Rawat Jalan',
                    'data' => $trend->pluck('rawat_jalan')->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => 'IGD',
                    'data' => $trend->pluck('igd')->toArray(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => 'Rawat Inap',
                    'data' => $trend->pluck('rawat_inap')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
            ],
            'labels' => $trend->map(fn ($item) => Carbon::parse($item['date'])->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ];
    }
}
