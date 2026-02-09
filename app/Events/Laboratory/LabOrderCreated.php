<?php

declare(strict_types=1);

namespace App\Events\Laboratory;

use App\Models\Laboratory\LaboratoryOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public LaboratoryOrder $laboratoryOrder
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('laboratory'),
            new PrivateChannel('admin'),
        ];
    }
}
