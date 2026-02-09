<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SurgeryResource\Widgets\SurgeryStats;
use App\Filament\Resources\SurgeryResource\Widgets\OperatingRoomSchedule;
use App\Filament\Resources\SurgeryResource;
use App\Models\Clinical\Surgery;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSurgeries extends ListRecords
{
    protected static string $resource = SurgeryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Jadwalkan Operasi Baru'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SurgeryStats::class,
            OperatingRoomSchedule::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Surgery::count()),

            'scheduled' => \Filament\Schemas\Components\Tabs\Tab::make('Terjadwal')
                ->icon('heroicon-o-calendar')
                ->badge(Surgery::where('status', 'scheduled')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled')),

            'preparation' => \Filament\Schemas\Components\Tabs\Tab::make('Persiapan')
                ->icon('heroicon-o-clock')
                ->badge(Surgery::where('status', 'preparation')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'preparation')),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Berlangsung')
                ->icon('heroicon-o-play')
                ->badge(Surgery::where('status', 'in_progress')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(Surgery::where('status', 'completed')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->badge(Surgery::where('status', 'cancelled')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar-days')
                ->badge(Surgery::today()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'cito' => \Filament\Schemas\Components\Tabs\Tab::make('CITO/Emergency')
                ->icon('heroicon-o-bolt')
                ->badge(Surgery::cito()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->cito()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
}
