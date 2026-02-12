<?php

declare(strict_types=1);

namespace App\Events\Inpatient;

use App\Models\Patient\Visit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientDischarged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param array<string, mixed> $dischargeData
     */
    public function __construct(
        public Visit $visit,
        public array $dischargeData
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inpatient'),
            new PrivateChannel('cashier'),
            new PrivateChannel('admin'),
        ];
    }
}
