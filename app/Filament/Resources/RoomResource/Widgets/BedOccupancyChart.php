<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoomResource\Widgets;

use App\Models\MasterData\Room;
use Filament\Widgets\ChartWidget;

class BedOccupancyChart extends ChartWidget
{
    protected ?string $heading = 'Tingkat Hunian per Kelas Kamar';

    protected ?string $description = 'Persentase hunian tempat tidur berdasarkan kelas kamar (diperbarui harian)';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $roomClasses = ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU'];
        $occupancyRates = [];
        $totalBeds = [];
        $occupiedBeds = [];

        foreach ($roomClasses as $class) {
            $rooms = Room::where('room_class', $class)->where('is_active', true)->get();
            
            $classTotalBeds = $rooms->sum('total_beds');
            $classAvailableBeds = $rooms->sum('available_beds');
            $classOccupiedBeds = $classTotalBeds - $classAvailableBeds;
            $occupancyRate = $classTotalBeds > 0 ? round(($classOccupiedBeds / $classTotalBeds) * 100, 1) : 0;

            $occupancyRates[] = $occupancyRate;
            $totalBeds[] = $classTotalBeds;
            $occupiedBeds[] = $classOccupiedBeds;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tingkat Hunian (%)',
                    'data' => $occupancyRates,
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.6)',   // VVIP - red
                        'rgba(245, 158, 11, 0.6)',  // VIP - amber
                        'rgba(59, 130, 246, 0.6)',  // Kelas I - blue
                        'rgba(6, 182, 212, 0.6)',   // Kelas II - cyan
                        'rgba(34, 197, 94, 0.6)',   // Kelas III - green
                        'rgba(147, 51, 234, 0.6)',  // ICU - purple
                        'rgba(168, 85, 247, 0.6)',  // NICU - purple
                        'rgba(192, 132, 252, 0.6)', // PICU - purple
                        'rgba(216, 180, 254, 0.6)', // HCU - purple
                    ],
                    'borderColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)',
                        'rgb(59, 130, 246)',
                        'rgb(6, 182, 212)',
                        'rgb(34, 197, 94)',
                        'rgb(147, 51, 234)',
                        'rgb(168, 85, 247)',
                        'rgb(192, 132, 252)',
                        'rgb(216, 180, 254)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $roomClasses,
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
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.parsed.y + "% hunian"; }',
                    ],
                ],
            ],
        ];
    }
}
