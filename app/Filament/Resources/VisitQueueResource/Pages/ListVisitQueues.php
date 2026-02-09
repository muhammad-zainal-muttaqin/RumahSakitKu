<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitQueueResource\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\VisitQueueResource\Widgets\QueueStats;
use App\Filament\Resources\VisitQueueResource\Widgets\LiveQueueDisplay;
use App\Filament\Resources\VisitQueueResource;
use App\Filament\Resources\VisitQueueResource\Widgets;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\VisitQueue;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVisitQueues extends ListRecords
{
    protected static string $resource = VisitQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refreshData()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QueueStats::class,
            LiveQueueDisplay::class,
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(VisitQueue::today()->count()),

            'waiting' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->badge(VisitQueue::today()->waiting()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->waiting()),

            'called' => \Filament\Schemas\Components\Tabs\Tab::make('Dipanggil')
                ->icon('heroicon-o-speaker-wave')
                ->badge(VisitQueue::today()->called()->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->called()),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dilayani')
                ->icon('heroicon-o-user')
                ->badge(VisitQueue::today()->where('status', 'in_progress')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge(VisitQueue::today()->completed()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->completed()),

            'skipped' => \Filament\Schemas\Components\Tabs\Tab::make('Dilewati')
                ->icon('heroicon-o-forward')
                ->badge(VisitQueue::today()->where('status', 'skipped')->count())
                ->badgeColor('orange')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'skipped')),
        ];

        // Add tabs for each polyclinic
        $polyclinics = Polyclinic::active()->orderBy('name')->get();
        foreach ($polyclinics as $polyclinic) {
            $tabs["poly_{$polyclinic->id}"] = \Filament\Schemas\Components\Tabs\Tab::make($polyclinic->name)
                ->icon('heroicon-o-building-office-2')
                ->badge(VisitQueue::today()->where('polyclinic_id', $polyclinic->id)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('polyclinic_id', $polyclinic->id));
        }

        return $tabs;
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'waiting';
    }

    protected function refreshData(): void
    {
        $this->resetTable();
    }
}
