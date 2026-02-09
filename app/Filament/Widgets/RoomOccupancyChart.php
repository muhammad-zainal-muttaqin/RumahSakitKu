<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RoomOccupancyChart extends ChartWidget
{
    protected ?string $heading = 'Okupansi Kamar per Kelas';

    protected ?string $description = 'Persentase hunian kamar berdasarkan kelas';

    protected static int|null $sort = 5;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $cacheKey = 'room_occupancy_' . now()->format('Ymd');
        
        $occupancy = Cache::remember($cacheKey, 300, function () {
            $reportService = app(ReportService::class);
            return $reportService->getRoomOccupancyByClass();
        });

        $classOrder = ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU'];
        
        // Sort by predefined order
        $sorted = $occupancy->sortBy(function ($item) use ($classOrder) {
            return array_search($item['class'], $classOrder);
        });

        $colors = [
            '#dc2626', // VVIP - red
            '#f97316', // VIP - orange
            '#3b82f6', // Kelas I - blue
            '#06b6d4', // Kelas II - cyan
            '#10b981', // Kelas III - green
            '#8b5cf6', // ICU - purple
            '#ec4899', // NICU - pink
            '#f59e0b', // PICU - amber
            '#6366f1', // HCU - indigo
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total Tempat Tidur',
                    'data' => $sorted->pluck('total_beds')->toArray(),
                    'backgroundColor' => '#e5e7eb',
                    'borderColor' => '#9ca3af',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Terisi',
                    'data' => $sorted->pluck('occupied_beds')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $sorted->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $sorted->pluck('class')->toArray(),
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
                    'stacked' => false,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
                'x' => [
                    'stacked' => false,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'afterBody' => "function(tooltipItems) {
                            const dataIndex = tooltipItems[0].dataIndex;
                            const chart = tooltipItems[0].chart;
                            const total = chart.data.datasets[0].data[dataIndex];
                            const occupied = chart.data.datasets[1].data[dataIndex];
                            const rate = total > 0 ? ((occupied / total) * 100).toFixed(1) : 0;
                            return 'Okupansi: ' + rate + '%';
                        }",
                    ],
                ],
            ],
        ];
    }
}
