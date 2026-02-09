<?php

declare(strict_types=1);

namespace App\Events\Surgery;

use App\Models\Surgery\Surgery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SurgeryStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Surgery $surgery
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('surgery'),
            new PrivateChannel('admin'),
        ];
    }
}
