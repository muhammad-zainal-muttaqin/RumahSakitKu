<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\MasterData\Employee;
use App\Models\Financial\Invoice;
use App\Models\MasterData\Medicine;
use App\Services\ReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.alerts-widget';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $reportService = app(ReportService::class);
        
        // Low stock medicines
        $lowStockData = Cache::remember('alerts_low_stock_v2', 300, function () {
            $query = Medicine::where('is_active', true)
                ->whereColumn('stock', '<=', 'min_stock')
                ->orderByRaw('(stock / min_stock)');

            return [
                'count' => (clone $query)->count(),
                'items' => $query->limit(5)->get(),
            ];
        });

        // Long stay patients (>7 days)
        $longStayData = Cache::remember('alerts_long_stay_v2', 300, function () use ($reportService) {
            $patients = $reportService->getLongStayPatients(7);

            return [
                'count' => $patients->count(),
                'items' => $patients->take(5)->values(),
            ];
        });

        // Expiring SIP/STR (within 30 days)
        $expiringLicensesData = Cache::remember('alerts_expiring_licenses_v2', 300, function () {
            $query = Employee::where('is_doctor', true)
                ->where('status', 'aktif')
                ->where(function ($query) {
                    $query->whereBetween('sip_expiry_date', [now(), now()->addDays(30)])
                        ->orWhereBetween('str_expiry_date', [now(), now()->addDays(30)]);
                });

            return [
                'count' => (clone $query)->count(),
                'items' => $query->limit(5)->get(),
            ];
        });

        // Overdue invoices
        $overdueInvoicesData = Cache::remember('alerts_overdue_invoices_v2', 300, function () {
            $query = Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->orderBy('due_date');

            return [
                'count' => (clone $query)->count(),
                'items' => $query->with('patient')->limit(5)->get(),
            ];
        });

        return [
            'lowStockMedicines' => $lowStockData['items'],
            'lowStockCount' => $lowStockData['count'],
            'longStayPatients' => $longStayData['items'],
            'longStayCount' => $longStayData['count'],
            'expiringLicenses' => $expiringLicensesData['items'],
            'expiringLicenseCount' => $expiringLicensesData['count'],
            'overdueInvoices' => $overdueInvoicesData['items'],
            'overdueInvoiceCount' => $overdueInvoicesData['count'],
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
