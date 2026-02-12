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

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $reportService = app(ReportService::class);
        
        // Low stock medicines
        $lowStockMedicines = Cache::remember('alerts_low_stock', 300, function () {
            return Medicine::where('is_active', true)
                ->whereColumn('stock', '<=', 'min_stock')
                ->orderByRaw('(stock / min_stock)')
                ->limit(5)
                ->get();
        });

        // Long stay patients (>7 days)
        $longStayPatients = Cache::remember('alerts_long_stay', 300, function () use ($reportService) {
            return $reportService->getLongStayPatients(7)->take(5);
        });

        // Expiring SIP/STR (within 30 days)
        $expiringLicenses = Cache::remember('alerts_expiring_licenses', 300, function () {
            return Employee::where('is_doctor', true)
                ->where('status', 'aktif')
                ->where(function ($query) {
                    $query->whereBetween('sip_expiry_date', [now(), now()->addDays(30)])
                        ->orWhereBetween('str_expiry_date', [now(), now()->addDays(30)]);
                })
                ->limit(5)
                ->get();
        });

        // Overdue invoices
        $overdueInvoices = Cache::remember('alerts_overdue_invoices', 300, function () {
            return Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->with('patient')
                ->orderBy('due_date')
                ->limit(5)
                ->get();
        });

        return [
            'lowStockMedicines' => $lowStockMedicines,
            'lowStockCount' => $lowStockMedicines->count(),
            'longStayPatients' => $longStayPatients,
            'longStayCount' => $reportService->getLongStayPatients(7)->count(),
            'expiringLicenses' => $expiringLicenses,
            'expiringLicenseCount' => Employee::where('is_doctor', true)
                ->where('status', 'aktif')
                ->where(function ($query) {
                    $query->whereBetween('sip_expiry_date', [now(), now()->addDays(30)])
                        ->orWhereBetween('str_expiry_date', [now(), now()->addDays(30)]);
                })
                ->count(),
            'overdueInvoices' => $overdueInvoices,
            'overdueInvoiceCount' => Invoice::where('status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
