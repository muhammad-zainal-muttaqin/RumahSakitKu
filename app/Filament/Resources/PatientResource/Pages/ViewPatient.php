<?php

declare(strict_types=1);

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
