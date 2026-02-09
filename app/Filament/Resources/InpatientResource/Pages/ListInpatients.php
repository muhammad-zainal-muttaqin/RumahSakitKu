<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InpatientResource\Widgets\InpatientStats;
use App\Filament\Resources\InpatientResource\Widgets\RoomOccupancy;
use App\Filament\Resources\InpatientResource;
use App\Filament\Resources\InpatientResource\Widgets;
use App\Models\Patient\Visit;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInpatients extends ListRecords
{
    protected static string $resource = InpatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Daftar Rawat Inap Baru'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InpatientStats::class,
            RoomOccupancy::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Visit::where('visit_type', 'rawat_inap')->count()),

            'admitted' => \Filament\Schemas\Components\Tabs\Tab::make('Dirawat')
                ->icon('heroicon-o-home')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('inpatient_status', 'admitted')
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('inpatient_status', 'admitted')),

            'discharge_planned' => \Filament\Schemas\Components\Tabs\Tab::make('Rencana Pulang')
                ->icon('heroicon-o-clock')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('inpatient_status', 'discharge_planned')
                    ->orWhere(function ($q) {
                        $q->where('visit_type', 'rawat_inap')
                            ->whereDate('planned_discharge_date', '<=', now()->addDay());
                    })
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where(function ($q) {
                        $q->where('inpatient_status', 'discharge_planned')
                            ->orWhere(function ($q2) {
                                $q2->whereDate('planned_discharge_date', '<=', now()->addDay());
                            });
                    })),

            'discharged' => \Filament\Schemas\Components\Tabs\Tab::make('Sudah Pulang')
                ->icon('heroicon-o-check-circle')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('inpatient_status', 'discharged')
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('inpatient_status', 'discharged')),

            'registered' => \Filament\Schemas\Components\Tabs\Tab::make('Terdaftar')
                ->icon('heroicon-o-clipboard-document')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('inpatient_status', 'registered')
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('inpatient_status', 'registered')),

            'transferred' => \Filament\Schemas\Components\Tabs\Tab::make('Pindahan')
                ->icon('heroicon-o-arrow-path')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('inpatient_status', 'transferred')
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('inpatient_status', 'transferred')),

            'long_stay' => \Filament\Schemas\Components\Tabs\Tab::make('LOS > 7 Hari')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(Visit::where('visit_type', 'rawat_inap')
                    ->where('is_completed', false)
                    ->whereDate('visit_date', '<=', now()->subDays(7))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_completed', false)
                    ->whereDate('visit_date', '<=', now()->subDays(7))),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'admitted';
    }
}
