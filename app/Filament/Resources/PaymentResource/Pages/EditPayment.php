<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn ($record) => !$record->is_refunded),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pembayaran berhasil diperbarui';
    }

    protected function beforeSave(): void
    {
        // Store old amount for calculation
        $this->oldAmount = $this->record->getOriginal('amount');
    }

    protected function afterSave(): void
    {
        // Update invoice paid amount if payment amount changed
        $payment = $this->record;
        $invoice = $payment->invoice;

        if ($invoice && isset($this->oldAmount) && $this->oldAmount !== $payment->amount) {
            $difference = $payment->amount - $this->oldAmount;
            $invoice->paid_amount += $difference;
            $invoice->save();
        }
    }
}
