<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\SurgeryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSurgery extends EditRecord
{
    protected static string $resource = SurgeryResource::class;

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
        return 'Data operasi berhasil diperbarui';
    }
}
