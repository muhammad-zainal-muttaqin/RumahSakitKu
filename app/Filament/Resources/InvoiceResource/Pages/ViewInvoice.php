<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('pay')
                ->label('Bayar')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->visible(fn ($record): bool => $record->balance_due > 0 && !in_array($record->status, ['paid', 'cancelled']))
                ->url(fn ($record): string => PaymentResource::getUrl('create', ['invoice_id' => $record->id])),
            Action::make('print')
                ->label('Cetak Invoice')
                ->icon('heroicon-m-printer')
                ->color('info')
                ->url(fn ($record): string => route('invoices.print', $record))
                ->openUrlInNewTab(),
        ];
    }
}
