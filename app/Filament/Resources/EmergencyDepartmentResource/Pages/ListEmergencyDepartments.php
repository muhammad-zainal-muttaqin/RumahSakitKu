<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Pages;

use App\Filament\Resources\EmergencyDepartmentResource\Widgets\TriageStats;
use App\Filament\Resources\EmergencyDepartmentResource\Widgets\LiveTriageBoard;
use Filament\Actions\CreateAction;
use App\Filament\Resources\EmergencyDepartmentResource;
use App\Filament\Resources\EmergencyDepartmentResource\Widgets;
use App\Models\Patient\Visit;
use App\Services\TriageService;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmergencyDepartments extends ListRecords
{
    protected static string $resource = EmergencyDepartmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TriageStats::class,
            LiveTriageBoard::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pasien IGD'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->count();
                }),

            'red' => \Filament\Schemas\Components\Tabs\Tab::make('Merah (Emergency)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'medicalRecord.assessments',
                    fn (Builder $query) => $query
                        ->where('assessment_type', 'triage')
                        ->where('triage_category', TriageService::CATEGORY_RED)
                ))
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->whereHas('medicalRecord.assessments', function (Builder $query) {
                            $query->where('assessment_type', 'triage')
                                ->where('triage_category', TriageService::CATEGORY_RED);
                        })
                        ->count();
                })
                ->badgeColor('danger'),

            'yellow' => \Filament\Schemas\Components\Tabs\Tab::make('Kuning (Urgent)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'medicalRecord.assessments',
                    fn (Builder $query) => $query
                        ->where('assessment_type', 'triage')
                        ->where('triage_category', TriageService::CATEGORY_YELLOW)
                ))
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->whereHas('medicalRecord.assessments', function (Builder $query) {
                            $query->where('assessment_type', 'triage')
                                ->where('triage_category', TriageService::CATEGORY_YELLOW);
                        })
                        ->count();
                })
                ->badgeColor('warning'),

            'green' => \Filament\Schemas\Components\Tabs\Tab::make('Hijau (Non-Urgent)')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'medicalRecord.assessments',
                    fn (Builder $query) => $query
                        ->where('assessment_type', 'triage')
                        ->where('triage_category', TriageService::CATEGORY_GREEN)
                ))
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->whereHas('medicalRecord.assessments', function (Builder $query) {
                            $query->where('assessment_type', 'triage')
                                ->where('triage_category', TriageService::CATEGORY_GREEN);
                        })
                        ->count();
                })
                ->badgeColor('success'),

            'in_progress' => \Filament\Schemas\Components\Tabs\Tab::make('Sedang Dilayani')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->where('status', 'in_progress')
                        ->count();
                })
                ->badgeColor('primary'),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['completed', 'cancelled']))
                ->badge(function (): int {
                    return Visit::where('visit_type', 'igd')
                        ->whereDate('visit_date', today())
                        ->whereIn('status', ['completed', 'cancelled'])
                        ->count();
                })
                ->badgeColor('gray'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where('visit_type', 'igd')
            ->whereDate('visit_date', today());
    }
}
