<?php

declare(strict_types=1);

namespace App\Filament\Resources\PolyclinicResource\Pages;

use App\Filament\Resources\PolyclinicResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolyclinic extends CreateRecord
{
    protected static string $resource = PolyclinicResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Poliklinik berhasil dibuat';
    }
}
