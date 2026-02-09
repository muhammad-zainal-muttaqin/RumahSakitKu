<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssessmentResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AssessmentResource;
use App\Models\Clinical\Assessment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAssessments extends ListRecords
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Asesmen'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Assessment::count()),

            'triage' => \Filament\Schemas\Components\Tabs\Tab::make('Triase')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(Assessment::where('assessment_type', 'triage')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assessment_type', 'triage')),

            'awal_perawat' => \Filament\Schemas\Components\Tabs\Tab::make('Awal Perawat')
                ->icon('heroicon-o-user')
                ->badge(Assessment::where('assessment_type', 'awal_perawat')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assessment_type', 'awal_perawat')),

            'awal_dokter' => \Filament\Schemas\Components\Tabs\Tab::make('Awal Dokter')
                ->icon('heroicon-o-user-circle')
                ->badge(Assessment::where('assessment_type', 'awal_dokter')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assessment_type', 'awal_dokter')),

            'lanjutan' => \Filament\Schemas\Components\Tabs\Tab::make('Lanjutan')
                ->icon('heroicon-o-arrow-path')
                ->badge(Assessment::where('assessment_type', 'lanjutan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assessment_type', 'lanjutan')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Widgets can be added here if needed
        ];
    }
}
