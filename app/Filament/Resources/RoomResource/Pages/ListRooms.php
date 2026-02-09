<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoomResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\RoomResource\Widgets\BedOccupancyChart;
use App\Filament\Resources\RoomResource;
use App\Models\MasterData\Room;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BedOccupancyChart::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Room::count()),

            'VVIP' => \Filament\Schemas\Components\Tabs\Tab::make('VVIP')
                ->icon('heroicon-o-star')
                ->badge(Room::where('room_class', 'VVIP')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('room_class', 'VVIP')),

            'VIP' => \Filament\Schemas\Components\Tabs\Tab::make('VIP')
                ->icon('heroicon-o-star')
                ->badge(Room::where('room_class', 'VIP')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('room_class', 'VIP')),

            'kelas_i' => \Filament\Schemas\Components\Tabs\Tab::make('Kelas I')
                ->icon('heroicon-o-building-office')
                ->badge(Room::where('room_class', 'Kelas I')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('room_class', 'Kelas I')),

            'kelas_ii' => \Filament\Schemas\Components\Tabs\Tab::make('Kelas II')
                ->icon('heroicon-o-building-office-2')
                ->badge(Room::where('room_class', 'Kelas II')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('room_class', 'Kelas II')),

            'kelas_iii' => \Filament\Schemas\Components\Tabs\Tab::make('Kelas III')
                ->icon('heroicon-o-home')
                ->badge(Room::where('room_class', 'Kelas III')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('room_class', 'Kelas III')),

            'icu' => \Filament\Schemas\Components\Tabs\Tab::make('ICU/HCU')
                ->icon('heroicon-o-heart')
                ->badge(Room::whereIn('room_class', ['ICU', 'NICU', 'PICU', 'HCU'])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('room_class', ['ICU', 'NICU', 'PICU', 'HCU'])),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
