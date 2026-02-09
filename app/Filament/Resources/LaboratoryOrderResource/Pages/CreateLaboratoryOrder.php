<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\Pages;

use App\Filament\Resources\LaboratoryOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLaboratoryOrder extends CreateRecord
{
    protected static string $resource = LaboratoryOrderResource::class;

    protected static ?string $title = 'Order Lab Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = LaboratoryOrderResource::generateOrderNumber();
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order laboratorium berhasil dibuat';
    }
}
