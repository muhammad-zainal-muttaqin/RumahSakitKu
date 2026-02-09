<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicalRecord extends CreateRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rekam medis berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate record number if not provided
        if (empty($data['record_number'])) {
            $data['record_number'] = $this->generateRecordNumber();
        }

        return $data;
    }

    private function generateRecordNumber(): string
    {
        $prefix = 'EMR';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }
}
