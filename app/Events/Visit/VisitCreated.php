<?php

declare(strict_types=1);

namespace App\Events\Visit;

use App\Models\Visit\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visit $visit
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('doctor.' . $this->visit->doctor_id),
        ];
    }
}
