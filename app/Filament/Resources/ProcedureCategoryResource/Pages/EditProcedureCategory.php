<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureCategoryResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ProcedureCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcedureCategory extends EditRecord
{
    protected static string $resource = ProcedureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kategori tindakan berhasil diperbarui';
    }
}
