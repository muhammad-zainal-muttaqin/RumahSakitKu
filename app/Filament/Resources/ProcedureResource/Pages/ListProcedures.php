<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ProcedureResource;
use App\Models\MasterData\Procedure;
use App\Models\MasterData\ProcedureCategory;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProcedures extends ListRecords
{
    protected static string $resource = ProcedureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Procedure::count()),
        ];

        // Add tabs for each category
        $categories = ProcedureCategory::active()->get();
        foreach ($categories as $category) {
            $tabs[$category->code] = \Filament\Schemas\Components\Tabs\Tab::make($category->name)
                ->badge(Procedure::where('category_id', $category->id)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category_id', $category->id));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
