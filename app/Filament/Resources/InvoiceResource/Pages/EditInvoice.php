<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn ($record) => in_array($record->status, ['draft', 'cancelled'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tagihan berhasil diperbarui';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calculate totals from items if present
        if (!empty($data['items']) && is_array($data['items'])) {
            $subtotal = collect($data['items'])->sum('total_price');
            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal - ($data['discount_amount'] ?? 0) + ($data['tax_amount'] ?? 0);
            $data['balance_due'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);
        }

        return $data;
    }
}
