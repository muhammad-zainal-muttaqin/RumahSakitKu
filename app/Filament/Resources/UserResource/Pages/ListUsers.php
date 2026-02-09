<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pengguna'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->badge(User::query()->count()),

            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Aktif')
                ->badge(User::query()->where('is_active', true)->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => \Filament\Schemas\Components\Tabs\Tab::make('Nonaktif')
                ->badge(User::query()->where('is_active', false)->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'with_employee' => \Filament\Schemas\Components\Tabs\Tab::make('Link Pegawai')
                ->badge(User::query()->whereNotNull('employee_id')->count())
                ->badgeColor('info')
                ->icon('heroicon-o-briefcase')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('employee_id')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'active';
    }
}
