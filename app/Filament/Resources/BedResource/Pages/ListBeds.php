<?php

declare(strict_types=1);

namespace App\Filament\Resources\BedResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Resources\BedResource;
use App\Models\MasterData\Bed;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBeds extends ListRecords
{
    protected static string $resource = BedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            
            Action::make('bedMap')
                ->label('Peta Tempat Tidur')
                ->icon('heroicon-o-map')
                ->color('info')
                ->url(fn (): string => static::getResource()::getUrl('management')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Bed::count()),

            'kosong' => \Filament\Schemas\Components\Tabs\Tab::make('Kosong')
                ->icon('heroicon-o-check-circle')
                ->badge(Bed::where('status', 'kosong')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'kosong'))
                ->color('success'),

            'terisi' => \Filament\Schemas\Components\Tabs\Tab::make('Terisi')
                ->icon('heroicon-o-user')
                ->badge(Bed::where('status', 'terisi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'terisi'))
                ->color('danger'),

            'reserved' => \Filament\Schemas\Components\Tabs\Tab::make('Dipesan')
                ->icon('heroicon-o-clock')
                ->badge(Bed::where('status', 'reserved')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reserved'))
                ->color('warning'),

            'maintenance' => \Filament\Schemas\Components\Tabs\Tab::make('Maintenance')
                ->icon('heroicon-o-wrench')
                ->badge(Bed::where('status', 'maintenance')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'maintenance'))
                ->color('gray'),

            'cleaning' => \Filament\Schemas\Components\Tabs\Tab::make('Cleaning')
                ->icon('heroicon-o-sparkles')
                ->badge(Bed::where('status', 'cleaning')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cleaning'))
                ->color('info'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
