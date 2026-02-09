<?php

declare(strict_types=1);

namespace App\Filament\Resources\CpptResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CpptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCppt extends EditRecord
{
    protected static string $resource = CpptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-m-eye'),
            DeleteAction::make()
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set updated_by
        $data['updated_by'] = Auth::id();

        // Handle verification timestamp
        if (! empty($data['is_verified']) && empty($data['verified_at'])) {
            $data['verified_at'] = now();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'CPPT berhasil diperbarui';
    }

    protected function getSavedNotificationDescription(): ?string
    {
        return 'Perubahan pada CPPT telah berhasil disimpan.';
    }
}
