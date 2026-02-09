<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureCategoryResource\Pages;

use App\Filament\Resources\ProcedureCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcedureCategory extends CreateRecord
{
    protected static string $resource = ProcedureCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kategori tindakan berhasil dibuat';
    }
}
