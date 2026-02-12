<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Pendapatan Harian';

    protected ?string $description = 'Pendapatan berdasarkan metode pembayaran';

    protected static int|null $sort = 4;

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public ?string $period = 'week';

    protected function getData(): array
    {
        $reportService = app(ReportService::class);
        
        // Show trend based on period
        $days = match ($this->period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            'year' => 12,
            default => 7,
        };

        if ($this->period === 'year') {
            $year = (int) now()->year;
            $cacheKey = "revenue_trend_year_{$year}";
            $data = Cache::remember($cacheKey, 600, fn () => $reportService->getMonthlyRevenueTrend($year));

            return [
                'datasets' => [
                    [
                        'label' => 'Tunai',
                        'data' => $data->pluck('cash')->toArray(),
                        'backgroundColor' => '#10b981',
                    ],
                    [
                        'label' => 'BPJS',
                        'data' => $data->pluck('bpjs')->toArray(),
                        'backgroundColor' => '#3b82f6',
                    ],
                    [
                        'label' => 'Asuransi',
                        'data' => $data->pluck('insurance')->toArray(),
                        'backgroundColor' => '#8b5cf6',
                    ],
                    [
                        'label' => 'Lainnya',
                        'data' => $data->pluck('other')->toArray(),
                        'backgroundColor' => '#6b7280',
                    ],
                ],
                'labels' => $data->pluck('month')->toArray(),
            ];
        }

        // Daily data
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $cacheKey = sprintf(
            'revenue_trend_%s_%s',
            $startDate->format('Ymd'),
            $endDate->format('Ymd')
        );

        $trend = Cache::remember($cacheKey, 300, fn () => $reportService->getDailyRevenueTrend($startDate, $endDate));

        return [
            'datasets' => [
                [
                    'label' => 'Tunai',
                    'data' => $trend->pluck('cash')->toArray(),
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'BPJS',
                    'data' => $trend->pluck('bpjs')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
                [
                    'label' => 'Asuransi',
                    'data' => $trend->pluck('insurance')->toArray(),
                    'backgroundColor' => '#8b5cf6',
                ],
                [
                    'label' => 'Lainnya',
                    'data' => $trend->pluck('other')->toArray(),
                    'backgroundColor' => '#6b7280',
                ],
            ],
            'labels' => $trend->map(fn ($item) => Carbon::parse($item['date'])->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }",
                    ],
                ],
                'x' => [
                    'stacked' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.dataset.label + ': Rp ' + context.raw.toLocaleString('id-ID'); }",
                    ],
                ],
            ],
        ];
    }
}
