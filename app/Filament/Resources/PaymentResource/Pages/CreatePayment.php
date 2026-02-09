<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Financial\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembayaran berhasil dicatat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default received_by if not set
        if (empty($data['received_by'])) {
            $data['received_by'] = Auth::id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update invoice paid amount
        $payment = $this->record;
        $invoice = $payment->invoice;

        if ($invoice) {
            $invoice->paid_amount += $payment->amount;
            $invoice->save();
        }
    }

    public function mount(): void
    {
        parent::mount();

        // Pre-fill invoice_id from query parameter
        $invoiceId = request()->query('invoice_id');
        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice) {
                $this->form->fill([
                    'invoice_id' => $invoiceId,
                    'amount' => $invoice->balance_due,
                    '_invoice_info' => [
                        'patient_name' => $invoice->patient?->name,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'balance_due' => $invoice->balance_due,
                    ],
                ]);
            }
        }
    }
}
