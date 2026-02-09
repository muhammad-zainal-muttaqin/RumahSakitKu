<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\InpatientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInpatient extends EditRecord
{
    protected static string $resource = InpatientResource::class;

    protected static ?string $title = 'Edit Data Rawat Inap';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => !$this->record->is_completed),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure visit type remains rawat_inap
        $data['visit_type'] = 'rawat_inap';

        return $data;
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Data rawat inap berhasil diperbarui')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
