<?php

declare(strict_types=1);

namespace App\Events\Visit;

use App\Models\Patient\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Visit $visit,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('queue-display'),
            new PrivateChannel('admin'),
        ];
    }
}
