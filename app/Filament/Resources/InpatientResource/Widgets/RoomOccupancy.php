<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Widgets;

use App\Models\MasterData\Room;
use Filament\Widgets\Widget;

class RoomOccupancy extends Widget
{
    protected string $view = 'filament.resources.inpatient-resource.widgets.room-occupancy';

    protected int | string | array $columnSpan = 'full';

    public function getOccupancyData(): array
    {
        $roomClasses = ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU'];
        
        $allRooms = Room::where('is_active', true)->get();
        
        $grouped = $allRooms->groupBy('room_class');
        
        $data = [];
        foreach ($roomClasses as $class) {
            $rooms = $grouped->get($class, collect());
            
            $totalBeds = $rooms->sum('total_beds');
            $availableBeds = $rooms->sum('available_beds');
            $occupiedBeds = $totalBeds - $availableBeds;
            $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

            $data[$class] = [
                'total_beds' => $totalBeds,
                'available_beds' => $availableBeds,
                'occupied_beds' => $occupiedBeds,
                'occupancy_rate' => $occupancyRate,
                'color' => $this->getClassColor($class),
            ];
        }

        return $data;
    }

    public function getTotalStats(): array
    {
        $allRooms = Room::where('is_active', true)->get();
        
        $totalBeds = $allRooms->sum('total_beds');
        $availableBeds = $allRooms->sum('available_beds');
        $occupiedBeds = $totalBeds - $availableBeds;
        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        return [
            'total_beds' => $totalBeds,
            'available_beds' => $availableBeds,
            'occupied_beds' => $occupiedBeds,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    private function getClassColor(string $class): string
    {
        return match ($class) {
            'VVIP' => 'danger',
            'VIP' => 'warning',
            'Kelas I' => 'primary',
            'Kelas II' => 'info',
            'Kelas III' => 'success',
            'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
            default => 'gray',
        };
    }

    protected function getViewData(): array
    {
        return [
            'occupancyData' => $this->getOccupancyData(),
            'totalStats' => $this->getTotalStats(),
        ];
    }
}
