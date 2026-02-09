<?php

declare(strict_types=1);

namespace App\Filament\Resources\PolyclinicResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PolyclinicResource;
use App\Models\MasterData\Polyclinic;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPolyclinics extends ListRecords
{
    protected static string $resource = PolyclinicResource::class;

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
                ->badge(Polyclinic::count()),

            'umum' => \Filament\Schemas\Components\Tabs\Tab::make('Umum')
                ->icon('heroicon-o-user')
                ->badge(Polyclinic::where('category', 'umum')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'umum')),

            'spesialis' => \Filament\Schemas\Components\Tabs\Tab::make('Spesialis')
                ->icon('heroicon-o-user-circle')
                ->badge(Polyclinic::where('category', 'spesialis')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'spesialis')),

            'gigi' => \Filament\Schemas\Components\Tabs\Tab::make('Gigi')
                ->icon('heroicon-o-face-smile')
                ->badge(Polyclinic::where('category', 'gigi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'gigi')),

            'anak' => \Filament\Schemas\Components\Tabs\Tab::make('Anak')
                ->icon('heroicon-o-baby')
                ->badge(Polyclinic::where('category', 'anak')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'anak')),

            'bedah' => \Filament\Schemas\Components\Tabs\Tab::make('Bedah')
                ->icon('heroicon-o-scissors')
                ->badge(Polyclinic::where('category', 'bedah')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'bedah')),

            'penyakit_dalam' => \Filament\Schemas\Components\Tabs\Tab::make('Penyakit Dalam')
                ->icon('heroicon-o-heart')
                ->badge(Polyclinic::where('category', 'penyakit_dalam')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'penyakit_dalam')),

            'syaraf' => \Filament\Schemas\Components\Tabs\Tab::make('Syaraf')
                ->icon('heroicon-o-bolt')
                ->badge(Polyclinic::where('category', 'syaraf')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'syaraf')),

            'jiwa' => \Filament\Schemas\Components\Tabs\Tab::make('Jiwa')
                ->icon('heroicon-o-brain')
                ->badge(Polyclinic::where('category', 'jiwa')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'jiwa')),

            'rehabilitasi' => \Filament\Schemas\Components\Tabs\Tab::make('Rehabilitasi')
                ->icon('heroicon-o-arrow-path')
                ->badge(Polyclinic::where('category', 'rehabilitasi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'rehabilitasi')),

            'radiologi' => \Filament\Schemas\Components\Tabs\Tab::make('Radiologi')
                ->icon('heroicon-o-x-circle')
                ->badge(Polyclinic::where('category', 'radiologi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'radiologi')),

            'laboratorium' => \Filament\Schemas\Components\Tabs\Tab::make('Laboratorium')
                ->icon('heroicon-o-beaker')
                ->badge(Polyclinic::where('category', 'laboratorium')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'laboratorium')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
