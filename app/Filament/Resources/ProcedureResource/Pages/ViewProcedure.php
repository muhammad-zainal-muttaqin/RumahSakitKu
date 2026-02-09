<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ProcedureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProcedure extends ViewRecord
{
    protected static string $resource = ProcedureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
