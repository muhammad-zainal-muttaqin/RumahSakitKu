<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Models\Finance\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier'),
            new PrivateChannel('finance'),
            new PrivateChannel('admin'),
        ];
    }
}
