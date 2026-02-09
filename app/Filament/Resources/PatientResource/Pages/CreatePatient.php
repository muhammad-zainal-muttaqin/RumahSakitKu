<?php

declare(strict_types=1);

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data pasien berhasil ditambahkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['registered_at'] = now();

        return $data;
    }
}
