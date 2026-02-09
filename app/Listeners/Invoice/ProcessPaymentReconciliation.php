<?php

declare(strict_types=1);

namespace App\Listeners\Invoice;

use App\Models\Finance\JournalEntry;
use App\Events\Invoice\PaymentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessPaymentReconciliation implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment;
        $invoice = $payment->invoice;
        
        // Update invoice status
        $totalPaid = $invoice->payments()->sum('amount');
        
        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update(['status' => 'paid']);
        } else {
            $invoice->update(['status' => 'partial']);
        }
        
        // Create journal entry for accounting
        $this->createJournalEntry($payment);
        
        // Log reconciliation
        activity()
            ->performedOn($payment)
            ->withProperties(['amount' => $payment->amount])
            ->log('payment_reconciled');
    }
    
    private function createJournalEntry($payment): void
    {
        // Journal entry logic for financial records
        JournalEntry::create([
            'payment_id' => $payment->id,
            'debit_account' => 'cash',
            'credit_account' => 'accounts_receivable',
            'amount' => $payment->amount,
            'description' => "Payment received for invoice {$payment->invoice->invoice_number}",
        ]);
    }
}
