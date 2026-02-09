<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Financial\Invoice;
use BackedEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use UnitEnum;

class InvoiceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalInvoices = Invoice::count();
        $totalAmount = Invoice::sum('total_amount');
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $paidAmount = Invoice::where('status', 'paid')->sum('total_amount');
        $unpaidInvoices = Invoice::whereIn('status', ['draft', 'sent', 'partial'])->count();
        $unpaidAmount = Invoice::whereIn('status', ['draft', 'sent', 'partial'])->sum('balance_due');
        $overdueInvoices = Invoice::overdue()->count();
        $overdueAmount = Invoice::overdue()->sum('balance_due');

        return [
            Stat::make('Total Tagihan', $totalInvoices)
                ->description('Rp ' . number_format($totalAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Lunas', $paidInvoices)
                ->description('Rp ' . number_format($paidAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Belum Lunas', $unpaidInvoices)
                ->description('Rp ' . number_format($unpaidAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Jatuh Tempo', $overdueInvoices)
                ->description('Rp ' . number_format($overdueAmount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueInvoices > 0 ? 'danger' : 'gray'),
        ];
    }
}
