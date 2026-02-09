<?php

declare(strict_types=1);

namespace App\Events\Invoice;

use App\Models\Finance\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier'),
            new PrivateChannel('admin'),
        ];
    }
}
