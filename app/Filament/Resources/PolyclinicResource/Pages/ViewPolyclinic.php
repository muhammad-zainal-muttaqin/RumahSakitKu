<?php

declare(strict_types=1);

namespace App\Filament\Resources\PolyclinicResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PolyclinicResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPolyclinic extends ViewRecord
{
    protected static string $resource = PolyclinicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
