<?php

declare(strict_types=1);

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PatientResource;
use App\Models\Patient\Patient;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pasien'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Patient::count()),

            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Aktif')
                ->icon('heroicon-o-check-circle')
                ->badge(Patient::where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => \Filament\Schemas\Components\Tabs\Tab::make('Tidak Aktif')
                ->icon('heroicon-o-x-circle')
                ->badge(Patient::where('is_active', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'umum' => \Filament\Schemas\Components\Tabs\Tab::make('Umum')
                ->icon('heroicon-o-user')
                ->badge(Patient::where('insurance_type', 'umum')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('insurance_type', 'umum')),

            'bpjs' => \Filament\Schemas\Components\Tabs\Tab::make('BPJS')
                ->icon('heroicon-o-identification')
                ->badge(Patient::where('insurance_type', 'bpjs')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('insurance_type', 'bpjs')),

            'asuransi' => \Filament\Schemas\Components\Tabs\Tab::make('Asuransi')
                ->icon('heroicon-o-shield-check')
                ->badge(Patient::where('insurance_type', 'asuransi')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('insurance_type', 'asuransi')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
