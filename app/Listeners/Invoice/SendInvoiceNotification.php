<?php

declare(strict_types=1);

namespace App\Listeners\Invoice;

use App\Events\Invoice\InvoiceCreated;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;
        $patient = $invoice->patient;
        
        if ($patient && $patient->email) {
            Mail::to($patient->email)
                ->send(new InvoiceCreatedMail($invoice));
        }
        
        // Also send WhatsApp notification if phone available
        if ($patient && $patient->phone) {
            // WhatsApp notification logic here
            // \App\Services\WhatsAppService::sendInvoice($patient->phone, $invoice);
        }
    }
}
