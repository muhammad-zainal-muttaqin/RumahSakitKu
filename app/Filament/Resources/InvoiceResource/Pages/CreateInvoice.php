<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tagihan berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
