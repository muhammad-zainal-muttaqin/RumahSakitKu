<?php

declare(strict_types=1);

namespace App\Filament\Resources\BedResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\BedResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBed extends ViewRecord
{
    protected static string $resource = BedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
