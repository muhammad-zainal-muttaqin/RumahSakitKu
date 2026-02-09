<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PaymentResource;
use App\Models\Financial\Payment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

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
                ->badge(Payment::count()),

            'today' => \Filament\Schemas\Components\Tabs\Tab::make('Hari Ini')
                ->icon('heroicon-o-calendar')
                ->badge(Payment::today()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->today()),

            'cash' => \Filament\Schemas\Components\Tabs\Tab::make('Tunai')
                ->icon('heroicon-o-banknotes')
                ->badge(Payment::where('payment_method', 'cash')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_method', 'cash')),

            'card' => \Filament\Schemas\Components\Tabs\Tab::make('Kartu')
                ->icon('heroicon-o-credit-card')
                ->badge(Payment::whereIn('payment_method', ['credit_card', 'debit_card'])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('payment_method', ['credit_card', 'debit_card'])),

            'transfer' => \Filament\Schemas\Components\Tabs\Tab::make('Transfer')
                ->icon('heroicon-o-building-library')
                ->badge(Payment::where('payment_method', 'bank_transfer')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_method', 'bank_transfer')),

            'bpjs' => \Filament\Schemas\Components\Tabs\Tab::make('BPJS')
                ->icon('heroicon-o-identification')
                ->badge(Payment::where('payment_method', 'bpjs')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_method', 'bpjs')),

            'insurance' => \Filament\Schemas\Components\Tabs\Tab::make('Asuransi')
                ->icon('heroicon-o-shield-check')
                ->badge(Payment::where('payment_method', 'insurance')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_method', 'insurance')),

            'refunded' => \Filament\Schemas\Components\Tabs\Tab::make('Refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->badge(Payment::refunded()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->refunded()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'today';
    }
}
