<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\AlertsWidget;
use App\Filament\Widgets\PatientDistributionChart;
use App\Filament\Widgets\RecentVisitsTable;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RoomOccupancyChart;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TopDiseasesChart;
use App\Filament\Widgets\VisitTrendChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Dashboard';
    }

    public function getWidgets(): array
    {
        $period = $this->filters['period'] ?? 'today';

        return [
            StatsOverviewWidget::class => ['period' => $period],
            AlertsWidget::class,
            VisitTrendChart::class => ['period' => $period],
            PatientDistributionChart::class => ['period' => $period],
            RevenueChart::class => ['period' => $period],
            RoomOccupancyChart::class,
            TopDiseasesChart::class => ['period' => $period],
            RecentVisitsTable::class,
        ];
    }

    public function filtersForm(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        Select::make('period')
                            ->label('Periode')
                            ->options([
                                'today' => 'Hari Ini',
                                'week' => 'Minggu Ini',
                                'month' => 'Bulan Ini',
                                'year' => 'Tahun Ini',
                            ])
                            ->default('today')
                            ->live()
                            ->selectablePlaceholder(false),
                    ])
                    ->columns(3),
            ]);
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
}
