<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStats;
use App\Filament\Resources\InvoiceResource;
use App\Models\Financial\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->icon('heroicon-o-list-bullet')
                ->badge(Invoice::count()),

            'draft' => \Filament\Schemas\Components\Tabs\Tab::make('Draft')
                ->icon('heroicon-o-document')
                ->badge(Invoice::where('status', 'draft')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),

            'sent' => \Filament\Schemas\Components\Tabs\Tab::make('Terkirim')
                ->icon('heroicon-o-paper-airplane')
                ->badge(Invoice::where('status', 'sent')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent')),

            'partial' => \Filament\Schemas\Components\Tabs\Tab::make('Sebagian')
                ->icon('heroicon-o-banknotes')
                ->badge(Invoice::where('status', 'partial')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'partial')),

            'paid' => \Filament\Schemas\Components\Tabs\Tab::make('Lunas')
                ->icon('heroicon-o-check-circle')
                ->badge(Invoice::where('status', 'paid')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),

            'overdue' => \Filament\Schemas\Components\Tabs\Tab::make('Jatuh Tempo')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(Invoice::overdue()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue()),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->icon('heroicon-o-x-circle')
                ->badge(Invoice::where('status', 'cancelled')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }
}
