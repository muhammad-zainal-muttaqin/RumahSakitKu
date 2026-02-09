<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PrescriptionResource\Widgets\PrescriptionStats;
use App\Filament\Resources\PrescriptionResource;
use App\Models\Clinical\Prescription;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPrescriptions extends ListRecords
{
    protected static string $resource = PrescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Resep Baru'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Prescription::count()),

            'draft' => \Filament\Schemas\Components\Tabs\Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->badge(Prescription::where('status', 'draft')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),

            'verified' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->badge(Prescription::where('status', 'verified')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'verified')),

            'processed' => \Filament\Schemas\Components\Tabs\Tab::make('Diproses')
                ->icon('heroicon-o-cog-6-tooth')
                ->badge(Prescription::where('status', 'processed')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processed')),

            'dispensed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-badge')
                ->badge(Prescription::where('status', 'dispensed')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'dispensed')),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->badge(Prescription::where('status', 'cancelled')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PrescriptionStats::class,
        ];
    }
}
