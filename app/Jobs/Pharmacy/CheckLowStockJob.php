<?php

declare(strict_types=1);

namespace App\Jobs\Pharmacy;

use Illuminate\Support\Collection;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Medicine;
use App\Notifications\LowStockNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Check Low Medicine Stock Job
 *
 * Daily job to check for low medicine stock levels and notify pharmacy staff.
 * Runs on schedule to proactively alert about inventory issues.
 *
 * @package App\Jobs\Pharmacy
 */
class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Threshold percentage for critical low stock.
     */
    public float $criticalThreshold = 0.2;

    /**
     * Create a new job instance.
     *
     * @param bool $includeExpiring Whether to include expiring medicines
     * @param int $expiringDays Number of days to consider for expiring medicines
     */
    public function __construct(
        public bool $includeExpiring = true,
        public int $expiringDays = 30
    ) {
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting low stock check');

            // Get low stock medicines
            $lowStockMedicines = $this->getLowStockMedicines();

            // Get out of stock medicines
            $outOfStockMedicines = $this->getOutOfStockMedicines();

            // Get expiring medicines
            $expiringMedicines = $this->includeExpiring
                ? $this->getExpiringMedicines()
                : collect();

            // Get critical stock medicines (below 20% of minimum)
            $criticalStockMedicines = $this->getCriticalStockMedicines();

            $executionTime = round(microtime(true) - $startTime, 2);

            // Log summary
            Log::info('Low stock check completed', [
                'low_stock_count' => $lowStockMedicines->count(),
                'out_of_stock_count' => $outOfStockMedicines->count(),
                'expiring_count' => $expiringMedicines->count(),
                'critical_count' => $criticalStockMedicines->count(),
                'execution_time_seconds' => $executionTime,
            ]);

            // If there are issues, notify pharmacy staff
            if ($lowStockMedicines->isNotEmpty() ||
                $outOfStockMedicines->isNotEmpty() ||
                $expiringMedicines->isNotEmpty() ||
                $criticalStockMedicines->isNotEmpty()) {
                $this->notifyPharmacyStaff(
                    $lowStockMedicines,
                    $outOfStockMedicines,
                    $expiringMedicines,
                    $criticalStockMedicines
                );
            }
        } catch (Throwable $e) {
            Log::error('Low stock check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception The exception that caused the failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Low stock check job failed after retries', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get low stock medicines.
     *
     * @return Collection
     */
    private function getLowStockMedicines()
    {
        return Medicine::active()
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->get(['id', 'code', 'name', 'stock', 'min_stock', 'unit', 'expired_date']);
    }

    /**
     * Get out of stock medicines.
     *
     * @return Collection
     */
    private function getOutOfStockMedicines()
    {
        return Medicine::active()
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'stock', 'min_stock', 'unit']);
    }

    /**
     * Get expiring medicines.
     *
     * @return Collection
     */
    private function getExpiringMedicines()
    {
        return Medicine::active()
            ->whereNotNull('expired_date')
            ->where('expired_date', '<=', now()->addDays($this->expiringDays))
            ->where('expired_date', '>=', now())
            ->orderBy('expired_date')
            ->get(['id', 'code', 'name', 'stock', 'expired_date', 'unit']);
    }

    /**
     * Get critical stock medicines (below 20% of minimum).
     *
     * @return Collection
     */
    private function getCriticalStockMedicines()
    {
        return Medicine::active()
            ->whereColumn('stock', '<=', DB::raw("min_stock * {$this->criticalThreshold}"))
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->get(['id', 'code', 'name', 'stock', 'min_stock', 'unit']);
    }

    /**
     * Notify pharmacy staff about stock issues.
     *
     * @param Collection $lowStockMedicines Low stock medicines
     * @param Collection $outOfStockMedicines Out of stock medicines
     * @param Collection $expiringMedicines Expiring medicines
     * @param Collection $criticalStockMedicines Critical stock medicines
     */
    private function notifyPharmacyStaff(
        $lowStockMedicines,
        $outOfStockMedicines,
        $expiringMedicines,
        $criticalStockMedicines
    ): void {
        // Get pharmacy staff
        $pharmacyStaff = Employee::where('profession', 'like', '%farmasi%')
            ->orWhere('profession', 'like', '%apoteker%')
            ->orWhereHas('user.roles', function ($query) {
                $query->whereIn('name', ['pharmacy', 'apoteker', 'admin']);
            })
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        if ($pharmacyStaff->isEmpty()) {
            Log::warning('No pharmacy staff found to notify about low stock');
            return;
        }

        $notification = new LowStockNotification(
            lowStockMedicines: $lowStockMedicines,
            outOfStockMedicines: $outOfStockMedicines,
            expiringMedicines: $expiringMedicines,
            criticalStockMedicines: $criticalStockMedicines
        );

        Notification::send($pharmacyStaff, $notification);

        Log::info('Low stock notification sent to pharmacy staff', [
            'recipients_count' => $pharmacyStaff->count(),
        ]);
    }
}
