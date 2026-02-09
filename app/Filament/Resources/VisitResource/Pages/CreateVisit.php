<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate visit number if not provided
        if (empty($data['visit_number'])) {
            $data['visit_number'] = VisitResource::generateVisitNumber();
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'registered';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kunjungan berhasil didaftarkan';
    }
}
