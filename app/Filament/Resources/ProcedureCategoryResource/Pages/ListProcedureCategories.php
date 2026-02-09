<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureCategoryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ProcedureCategoryResource;
use App\Models\MasterData\ProcedureCategory;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcedureCategories extends ListRecords
{
    protected static string $resource = ProcedureCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
