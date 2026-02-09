<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LaboratoryOrderResource\Widgets\LabOrderStats;
use App\Filament\Resources\LaboratoryOrderResource;
use App\Models\Clinical\LaboratoryOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLaboratoryOrders extends ListRecords
{
    protected static string $resource = LaboratoryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Order Lab Baru'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(LaboratoryOrder::count()),

            'pending' => \Filament\Schemas\Components\Tabs\Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->badge(LaboratoryOrder::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Diproses')
                ->icon('heroicon-o-cog-6-tooth')
                ->badge(LaboratoryOrder::where('status', 'in_progress')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(LaboratoryOrder::where('status', 'completed')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'validated' => \Filament\Schemas\Components\Tabs\Tab::make('Divalidasi')
                ->icon('heroicon-o-check-badge')
                ->badge(LaboratoryOrder::where('status', 'validated')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'validated')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LabOrderStats::class,
        ];
    }
}
