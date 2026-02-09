<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadiologyOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\RadiologyOrderResource\Widgets\RadiologyStats;
use App\Filament\Resources\RadiologyOrderResource;
use App\Models\Clinical\RadiologyOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRadiologyOrders extends ListRecords
{
    protected static string $resource = RadiologyOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Order Radiologi Baru'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(RadiologyOrder::count()),

            'pending' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->badge(RadiologyOrder::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'scheduled' => \Filament\Schemas\Components\Tabs\Tab::make('Terjadwal')
                ->icon('heroicon-o-calendar')
                ->badge(RadiologyOrder::where('status', 'scheduled')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'scheduled')),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dikerjakan')
                ->icon('heroicon-o-play')
                ->badge(RadiologyOrder::where('status', 'in_progress')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(RadiologyOrder::whereIn('status', ['completed', 'reported'])->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['completed', 'reported'])),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RadiologyStats::class,
        ];
    }
}
