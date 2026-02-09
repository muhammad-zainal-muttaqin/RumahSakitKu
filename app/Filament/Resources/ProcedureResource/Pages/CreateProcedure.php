<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureResource\Pages;

use App\Filament\Resources\ProcedureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcedure extends CreateRecord
{
    protected static string $resource = ProcedureResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tindakan berhasil dibuat';
    }
}
