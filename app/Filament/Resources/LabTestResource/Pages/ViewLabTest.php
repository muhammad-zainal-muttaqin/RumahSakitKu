<?php

declare(strict_types=1);

namespace App\Filament\Resources\LabTestResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\LabTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLabTest extends ViewRecord
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
