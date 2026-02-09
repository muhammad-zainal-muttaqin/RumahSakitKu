<?php

declare(strict_types=1);

namespace App\Events\Prescription;

use App\Models\Pharmacy\Prescription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionDispensed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Prescription $prescription
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('pharmacy'),
            new PrivateChannel('admin'),
        ];
    }
}
