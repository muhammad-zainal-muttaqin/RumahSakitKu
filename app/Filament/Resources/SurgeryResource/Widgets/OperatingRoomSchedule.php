<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Widgets;

use App\Models\Clinical\Surgery;
use Filament\Widgets\Widget;

class OperatingRoomSchedule extends Widget
{
    protected string $view = 'filament.resources.surgery-resource.widgets.operating-room-schedule';

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Jadwal OK Hari Ini';

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function getRooms(): array
    {
        return Surgery::getOperatingRooms();
    }

    public function getScheduleData(): array
    {
        $schedule = [];
        $rooms = $this->getRooms();

        foreach ($rooms as $code => $name) {
            $surgeries = Surgery::today()
                ->where('operating_room', $code)
                ->whereNotIn('status', ['cancelled'])
                ->with(['patient', 'surgeon'])
                ->orderBy('start_time')
                ->get();

            $schedule[$code] = [
                'name' => $name,
                'surgeries' => $surgeries,
                'count' => $surgeries->count(),
                'in_progress' => $surgeries->where('status', 'in_progress')->count(),
                'completed' => $surgeries->where('status', 'completed')->count(),
                'cito' => $surgeries->whereIn('surgery_type', ['cito', 'emergency'])->count(),
            ];
        }

        return $schedule;
    }

    public function getActiveRoomsCount(): int
    {
        $count = 0;
        foreach ($this->getScheduleData() as $room) {
            if ($room['count'] > 0) {
                $count++;
            }
        }
        return $count;
    }

    public function getTotalSurgeriesCount(): int
    {
        return Surgery::today()
            ->whereNotIn('status', ['cancelled'])
            ->count();
    }

    protected function getViewData(): array
    {
        return [
            'schedule' => $this->getScheduleData(),
            'activeRoomsCount' => $this->getActiveRoomsCount(),
            'totalSurgeriesCount' => $this->getTotalSurgeriesCount(),
        ];
    }
}
