<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitResource\Widgets\TodayVisitsStats;
use App\Filament\Resources\VisitResource;
use App\Models\Patient\Visit;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListVisits extends ListRecords
{
    protected static string $resource = VisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Daftar Kunjungan Baru'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TodayVisitsStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Visit::count()),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar')
                ->badge(Visit::today()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'registered' => \Filament\Schemas\Components\Tabs\Tab::make('Terdaftar')
                ->icon('heroicon-o-clipboard-document')
                ->badge(Visit::where('status', 'registered')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'registered')),

            'waiting' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->badge(Visit::where('status', 'waiting')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'waiting')),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dilayani')
                ->icon('heroicon-o-play')
                ->badge(Visit::where('status', 'in_progress')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(Visit::where('status', 'completed')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->badge(Visit::where('status', 'cancelled')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),

            'rawat_jalan' => \Filament\Schemas\Components\Tabs\Tab::make('Rawat Jalan')
                ->icon('heroicon-o-building-office')
                ->badge(Visit::where('visit_type', 'rawat_jalan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', 'rawat_jalan')),

            'igd' => \Filament\Schemas\Components\Tabs\Tab::make('IGD')
                ->icon('heroicon-o-truck')
                ->badge(Visit::where('visit_type', 'igd')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', 'igd')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
}
