<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VisitResource\Widgets\TodayVisitsStats;
use App\Filament\Resources\VisitResource;
use App\Services\VisitMetricsService;
use Filament\Resources\Pages\ListRecords;
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
        $metrics = app(VisitMetricsService::class)->getTabBadgeCounts();

        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge($metrics['all'] ?? 0),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar')
                ->badge($metrics['today'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'registered' => \Filament\Schemas\Components\Tabs\Tab::make('Terdaftar')
                ->icon('heroicon-o-clipboard-document')
                ->badge($metrics['registered'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_status', VisitMetricsService::legacyStatusToVisitStatus('registered'))),

            'waiting' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu')
                ->icon('heroicon-o-clock')
                ->badge($metrics['waiting'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_status', VisitMetricsService::legacyStatusToVisitStatus('waiting'))),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dilayani')
                ->icon('heroicon-o-play')
                ->badge($metrics['in_progress'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_status', VisitMetricsService::legacyStatusToVisitStatus('in_progress'))),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->icon('heroicon-o-check-circle')
                ->badge($metrics['completed'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_status', VisitMetricsService::legacyStatusToVisitStatus('completed'))),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->badge($metrics['cancelled'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_status', VisitMetricsService::legacyStatusToVisitStatus('cancelled'))),

            'rawat_jalan' => \Filament\Schemas\Components\Tabs\Tab::make('Rawat Jalan')
                ->icon('heroicon-o-building-office')
                ->badge($metrics['rawat_jalan'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', 'rawat_jalan')),

            'igd' => \Filament\Schemas\Components\Tabs\Tab::make('IGD')
                ->icon('heroicon-o-truck')
                ->badge($metrics['igd'] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('visit_type', 'igd')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
}
