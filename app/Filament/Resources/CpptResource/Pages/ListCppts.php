<?php

declare(strict_types=1);

namespace App\Filament\Resources\CpptResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CpptResource;
use App\Models\Clinical\Cppt;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCppts extends ListRecords
{
    protected static string $resource = CpptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat CPPT Baru')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Cppt::count()),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar')
                ->badge(Cppt::whereDate('cppt_date', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('cppt_date', today())),

            'progress' => \Filament\Schemas\Components\Tabs\Tab::make('Progress Note')
                ->icon('heroicon-o-arrow-path')
                ->badge(Cppt::where('cppt_type', 'progress_note')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cppt_type', 'progress_note')),

            'procedure' => \Filament\Schemas\Components\Tabs\Tab::make('Procedure Note')
                ->icon('heroicon-o-scissors')
                ->badge(Cppt::where('cppt_type', 'procedure_note')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cppt_type', 'procedure_note')),

            'discharge' => \Filament\Schemas\Components\Tabs\Tab::make('Discharge Note')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->badge(Cppt::where('cppt_type', 'discharge_note')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('cppt_type', 'discharge_note')),

            'verified' => \Filament\Schemas\Components\Tabs\Tab::make('Terverifikasi')
                ->icon('heroicon-o-check-badge')
                ->badge(Cppt::where('is_verified', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true)),

            'unverified' => \Filament\Schemas\Components\Tabs\Tab::make('Belum Terverifikasi')
                ->icon('heroicon-o-exclamation-circle')
                ->badge(Cppt::where('is_verified', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', false)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
}
