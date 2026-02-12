<?php

declare(strict_types=1);

namespace App\Events\Inpatient;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientAdmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visit $visit,
        public Room $room,
        public Bed $bed
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inpatient'),
            new PrivateChannel('admin'),
        ];
    }
}
