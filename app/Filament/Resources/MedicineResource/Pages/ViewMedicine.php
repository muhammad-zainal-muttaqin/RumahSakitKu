<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicineResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\MedicineResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicine extends ViewRecord
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
