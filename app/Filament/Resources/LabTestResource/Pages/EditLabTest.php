<?php

declare(strict_types=1);

namespace App\Filament\Resources\LabTestResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\LabTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabTest extends EditRecord
{
    protected static string $resource = LabTestResource::class;

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
        return 'Pemeriksaan lab berhasil diperbarui';
    }
}
