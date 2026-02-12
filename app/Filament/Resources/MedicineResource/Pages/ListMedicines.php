<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicineResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MedicineResource;
use App\Models\MasterData\Medicine;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMedicines extends ListRecords
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Medicine::count()),

            'obat_bebas' => \Filament\Schemas\Components\Tabs\Tab::make('Obat Bebas')
                ->icon('heroicon-o-check-circle')
                ->badge(Medicine::where('classification', 'obat_bebas')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('classification', 'obat_bebas'))
                ->badgeColor('success'),

            'obat_keras' => \Filament\Schemas\Components\Tabs\Tab::make('Obat Keras')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(Medicine::where('classification', 'obat_keras')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('classification', 'obat_keras'))
                ->badgeColor('warning'),

            'narkotika' => \Filament\Schemas\Components\Tabs\Tab::make('Narkotika')
                ->icon('heroicon-o-shield-exclamation')
                ->badge(Medicine::where('classification', 'narkotika')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('classification', 'narkotika'))
                ->badgeColor('danger'),

            'low_stock' => \Filament\Schemas\Components\Tabs\Tab::make('Stok Rendah')
                ->icon('heroicon-o-arrow-trending-down')
                ->badge(Medicine::whereColumn('stock', '<=', 'min_stock')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('stock', '<=', 'min_stock'))
                ->badgeColor('warning'),

            'expiring_soon' => \Filament\Schemas\Components\Tabs\Tab::make('Segera Kadaluarsa')
                ->icon('heroicon-o-clock')
                ->badge(Medicine::where('expired_date', '>=', now())
                    ->where('expired_date', '<=', now()->addDays(30))
                    ->count())
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('expired_date', '>=', now())
                    ->where('expired_date', '<=', now()->addDays(30)))
                ->badgeColor('danger'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
