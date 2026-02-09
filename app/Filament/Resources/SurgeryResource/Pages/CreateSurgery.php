<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Pages;

use App\Filament\Resources\SurgeryResource;
use App\Services\SurgeryService;
use Filament\Resources\Pages\CreateRecord;

class CreateSurgery extends CreateRecord
{
    protected static string $resource = SurgeryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate surgery number if not provided
        if (empty($data['surgery_number'])) {
            $service = app(SurgeryService::class);
            $data['surgery_number'] = $service->generateSurgeryNumber();
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'scheduled';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Jadwal operasi berhasil dibuat';
    }
}
