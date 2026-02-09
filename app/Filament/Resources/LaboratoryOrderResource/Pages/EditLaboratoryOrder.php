<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\LaboratoryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaboratoryOrder extends EditRecord
{
    protected static string $resource = LaboratoryOrderResource::class;

    protected static ?string $title = 'Edit Order Lab';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

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
        return 'Order laboratorium berhasil diperbarui';
    }
}
