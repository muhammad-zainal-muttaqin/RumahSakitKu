<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureCategoryResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\ProcedureCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProcedureCategory extends ViewRecord
{
    protected static string $resource = ProcedureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
