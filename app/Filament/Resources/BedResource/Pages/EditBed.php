<?php

declare(strict_types=1);

namespace App\Filament\Resources\BedResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBed extends EditRecord
{
    protected static string $resource = BedResource::class;

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
        return 'Tempat tidur berhasil diperbarui';
    }
}
