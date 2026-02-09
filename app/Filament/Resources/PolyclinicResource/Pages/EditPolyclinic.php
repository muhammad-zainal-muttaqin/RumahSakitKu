<?php

declare(strict_types=1);

namespace App\Filament\Resources\PolyclinicResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\PolyclinicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolyclinic extends EditRecord
{
    protected static string $resource = PolyclinicResource::class;

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
        return 'Poliklinik berhasil diperbarui';
    }
}
