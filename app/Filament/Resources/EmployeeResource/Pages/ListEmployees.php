<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\EmployeeResource;
use App\Models\MasterData\Employee;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

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
                ->badge(Employee::count()),

            'doctors' => \Filament\Schemas\Components\Tabs\Tab::make('Dokter')
                ->icon('heroicon-o-user-circle')
                ->badge(Employee::where('is_doctor', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_doctor', true)),

            'nurses' => \Filament\Schemas\Components\Tabs\Tab::make('Perawat')
                ->icon('heroicon-o-heart')
                ->badge(Employee::where('is_nurse', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_nurse', true)),

            'aktif' => \Filament\Schemas\Components\Tabs\Tab::make('Aktif')
                ->icon('heroicon-o-check-circle')
                ->badge(Employee::where('status', 'aktif')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'aktif'))
                ->badgeColor('success'),

            'cuti' => \Filament\Schemas\Components\Tabs\Tab::make('Cuti')
                ->icon('heroicon-o-clock')
                ->badge(Employee::where('status', 'cuti')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cuti'))
                ->badgeColor('warning'),

            'pensiun' => \Filament\Schemas\Components\Tabs\Tab::make('Pensiun')
                ->icon('heroicon-o-arrow-left-start-on-rectangle')
                ->badge(Employee::where('status', 'pensiun')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pensiun'))
                ->badgeColor('gray'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
