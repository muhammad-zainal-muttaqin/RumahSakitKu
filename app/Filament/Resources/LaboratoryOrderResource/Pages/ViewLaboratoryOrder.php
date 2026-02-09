<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\LaboratoryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaboratoryOrder extends ViewRecord
{
    protected static string $resource = LaboratoryOrderResource::class;

    protected static ?string $title = 'Detail Order Lab';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
