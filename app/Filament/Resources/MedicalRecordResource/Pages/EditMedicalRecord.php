<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMedicalRecord extends EditRecord
{
    protected static string $resource = MedicalRecordResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Check if record is finalized
        if ($this->getRecord()->is_finalized) {
            // Redirect to view page if finalized
            $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => !$this->getRecord()->is_finalized),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Rekam medis berhasil diperbarui';
    }

    protected function beforeSave(): void
    {
        // Double check if record is finalized before saving
        if ($this->getRecord()->is_finalized) {
            $this->halt();
            $this->notify('danger', 'Rekam medis yang sudah difinalisasi tidak dapat diubah.');
        }
    }
}
