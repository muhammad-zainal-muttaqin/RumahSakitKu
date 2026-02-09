<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MedicalRecordResource;
use App\Models\Clinical\MedicalRecord;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMedicalRecords extends ListRecords
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat EMR Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(MedicalRecord::count()),

            'draft' => \Filament\Schemas\Components\Tabs\Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->badge(MedicalRecord::draft()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->draft()),

            'finalized' => \Filament\Schemas\Components\Tabs\Tab::make('Finalized')
                ->icon('heroicon-o-check-circle')
                ->badge(MedicalRecord::finalized()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->finalized()),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar')
                ->badge(MedicalRecord::whereDate('visit_date', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('visit_date', today())),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
