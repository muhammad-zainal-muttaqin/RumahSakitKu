<?php

declare(strict_types=1);

namespace App\Events\Laboratory;

use App\Models\Clinical\LaboratoryResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LabResultsEntered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public LaboratoryResult $laboratoryResult
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
