<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadiologyOrderResource\Pages;

use App\Filament\Resources\RadiologyOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRadiologyOrder extends CreateRecord
{
    protected static string $resource = RadiologyOrderResource::class;

    protected static ?string $title = 'Order Radiologi Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = RadiologyOrderResource::generateOrderNumber();
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order radiologi berhasil dibuat';
    }
}
