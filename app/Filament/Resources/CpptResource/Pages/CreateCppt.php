<?php

declare(strict_types=1);

namespace App\Filament\Resources\CpptResource\Pages;

use App\Filament\Resources\CpptResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCppt extends CreateRecord
{
    protected static string $resource = CpptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values if not provided
        if (empty($data['cppt_date'])) {
            $data['cppt_date'] = now();
        }

        if (empty($data['created_by'])) {
            $data['created_by'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'CPPT berhasil dibuat';
    }

    protected function getCreatedNotificationDescription(): ?string
    {
        return 'Catatan Perkembangan Pasien Terintegrasi telah berhasil disimpan.';
    }
}
