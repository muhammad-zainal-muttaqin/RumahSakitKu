<?php

declare(strict_types=1);

namespace App\Events\MedicalRecord;

use App\Models\Clinical\MedicalRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedicalRecordFinalized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MedicalRecord $medicalRecord
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
            new PrivateChannel('medical-records'),
        ];
    }
}
