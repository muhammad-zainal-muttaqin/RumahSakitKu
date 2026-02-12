<?php

declare(strict_types=1);

namespace App\Filament\Resources\LabTestResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LabTestResource;
use App\Models\MasterData\LabTest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLabTests extends ListRecords
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(LabTest::count()),

            'hematologi' => \Filament\Schemas\Components\Tabs\Tab::make('Hematologi')
                ->icon('heroicon-o-beaker')
                ->badge(LabTest::where('category', 'hematologi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'hematologi'))
                ->badgeColor('danger'),

            'kimia_darah' => \Filament\Schemas\Components\Tabs\Tab::make('Kimia Darah')
                ->icon('heroicon-o-beaker')
                ->badge(LabTest::where('category', 'kimia_darah')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'kimia_darah'))
                ->badgeColor('primary'),

            'urinalisa' => \Filament\Schemas\Components\Tabs\Tab::make('Urinalisa')
                ->icon('heroicon-o-beaker')
                ->badge(LabTest::where('category', 'urinalisa')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'urinalisa'))
                ->badgeColor('warning'),

            'mikrobiologi' => \Filament\Schemas\Components\Tabs\Tab::make('Mikrobiologi')
                ->icon('heroicon-o-beaker')
                ->badge(LabTest::where('category', 'mikrobiologi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'mikrobiologi'))
                ->badgeColor('success'),

            'imunologi' => \Filament\Schemas\Components\Tabs\Tab::make('Imunologi/Serologi')
                ->icon('heroicon-o-beaker')
                ->badge(LabTest::whereIn('category', ['imunologi', 'serologi'])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('category', ['imunologi', 'serologi']))
                ->badgeColor('info'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
