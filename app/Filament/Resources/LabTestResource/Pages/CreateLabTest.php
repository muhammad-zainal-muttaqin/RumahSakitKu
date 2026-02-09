<?php

declare(strict_types=1);

namespace App\Filament\Resources\LabTestResource\Pages;

use App\Filament\Resources\LabTestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabTest extends CreateRecord
{
    protected static string $resource = LabTestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pemeriksaan lab berhasil dibuat';
    }
}
