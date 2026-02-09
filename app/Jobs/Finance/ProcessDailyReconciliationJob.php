<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use Exception;
use Illuminate\Support\Collection;
use App\Models\Financial\Payment;
use App\Models\User;
use App\Notifications\DailyReconciliationNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Process Daily Reconciliation Job
 *
 * Daily job to reconcile all payments for a specific date.
 * Generates reconciliation report and notifies finance staff.
 *
 * @package App\Jobs\Finance
 */
class ProcessDailyReconciliationJob implements ShouldQueue
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
    public int $backoff = 120;

    /**
     * Create a new job instance.
     *
     * @param string $date The date to reconcile (Y-m-d format)
     * @param array<int> $notifyUserIds User IDs to notify upon completion
     */
    public function __construct(
        public string $date,
        public array $notifyUserIds = []
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
            $reconciliationDate = Carbon::parse($this->date);

            Log::info('Starting daily reconciliation', [
                'date' => $this->date,
            ]);

            // Get all payments for the date
            $payments = $this->getPaymentsForDate($reconciliationDate);

            // Calculate reconciliation summary
            $summary = $this->calculateSummary($payments);

            // Generate detailed report
            $report = $this->generateReport($payments, $summary);

            // Store report
            $reportPath = $this->storeReport($report, $reconciliationDate);

            // Store reconciliation record
            $this->storeReconciliationRecord($summary, $reportPath);

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Daily reconciliation completed', [
                'date' => $this->date,
                'total_payments' => $payments->count(),
                'total_amount' => $summary['total_amount'],
                'report_path' => $reportPath,
                'execution_time_seconds' => $executionTime,
            ]);

            // Notify users
            $this->notifyUsers($summary, $reportPath);
        } catch (Throwable $e) {
            Log::error('Daily reconciliation failed', [
                'date' => $this->date,
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
        Log::error('Daily reconciliation job failed after retries', [
            'date' => $this->date,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get all payments for the specified date.
     *
     * @param Carbon $date The date
     * @return Collection
     */
    private function getPaymentsForDate(Carbon $date)
    {
        return Payment::with(['invoice.visit.patient'])
            ->whereDate('payment_date', $date)
            ->notRefunded()
            ->orderBy('payment_time')
            ->get();
    }

    /**
     * Calculate reconciliation summary.
     *
     * @param Collection $payments The payments
     * @return array<string, mixed> The summary
     */
    private function calculateSummary($payments): array
    {
        $summary = [
            'date' => $this->date,
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'by_payment_method' => [],
            'by_payment_type' => [],
            'hourly_breakdown' => [],
        ];

        // Group by payment method
        $summary['by_payment_method'] = $payments
            ->groupBy('payment_method')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })
            ->toArray();

        // Group by payment type
        $summary['by_payment_type'] = $payments
            ->groupBy('payment_type')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })
            ->toArray();

        // Hourly breakdown
        $summary['hourly_breakdown'] = $payments
            ->groupBy(function ($payment) {
                return $payment->payment_time
                    ? $payment->payment_time->format('H')
                    : '00';
            })
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })
            ->toArray();

        // Calculate statistics
        $summary['statistics'] = [
            'average_payment' => $payments->count() > 0
                ? round($payments->sum('amount') / $payments->count(), 2)
                : 0,
            'min_payment' => $payments->min('amount') ?? 0,
            'max_payment' => $payments->max('amount') ?? 0,
        ];

        return $summary;
    }

    /**
     * Generate detailed report.
     *
     * @param Collection $payments The payments
     * @param array<string, mixed> $summary The summary
     * @return array<string, mixed> The report
     */
    private function generateReport($payments, array $summary): array
    {
        return [
            'summary' => $summary,
            'payments' => $payments->map(function ($payment) {
                return [
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date->toDateString(),
                    'payment_time' => $payment->payment_time?->format('H:i:s'),
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_type' => $payment->payment_type,
                    'reference_number' => $payment->reference_number,
                    'received_by' => $payment->received_by,
                    'patient_name' => $payment->invoice?->visit?->patient?->name,
                    'visit_number' => $payment->invoice?->visit?->visit_number,
                ];
            })->toArray(),
            'generated_at' => now()->toIso8601String(),
            'generated_by' => 'system',
        ];
    }

    /**
     * Store reconciliation report.
     *
     * @param array<string, mixed> $report The report
     * @param Carbon $date The date
     * @return string The file path
     */
    private function storeReport(array $report, Carbon $date): string
    {
        $directory = "reconciliations/{$date->year}/{$date->month}";
        $fileName = "reconciliation_{$this->date}.json";
        $filePath = "{$directory}/{$fileName}";

        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        Storage::put($filePath, json_encode($report, JSON_PRETTY_PRINT));

        return $filePath;
    }

    /**
     * Store reconciliation record in database.
     *
     * @param array<string, mixed> $summary The summary
     * @param string $reportPath The report file path
     */
    private function storeReconciliationRecord(array $summary, string $reportPath): void
    {
        try {
            DB::table('daily_reconciliations')->updateOrInsert(
                ['date' => $this->date],
                [
                    'total_payments' => $summary['total_payments'],
                    'total_amount' => $summary['total_amount'],
                    'summary_data' => json_encode($summary),
                    'report_path' => $reportPath,
                    'status' => 'completed',
                    'processed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        } catch (Exception $e) {
            // Table might not exist, just log warning
            Log::warning('Could not store reconciliation record', [
                'error' => $e->getMessage(),
                'note' => 'Consider creating daily_reconciliations table',
            ]);
        }
    }

    /**
     * Notify users about reconciliation completion.
     *
     * @param array<string, mixed> $summary The summary
     * @param string $reportPath The report file path
     */
    private function notifyUsers(array $summary, string $reportPath): void
    {
        if (empty($this->notifyUserIds)) {
            // Get finance and admin users
            $users = User::role(['finance', 'admin', 'keuangan'])->get();
        } else {
            $users = User::whereIn('id', $this->notifyUserIds)->get();
        }

        if ($users->isEmpty()) {
            Log::warning('No users found to notify about daily reconciliation');
            return;
        }

        $notification = new DailyReconciliationNotification(
            date: $this->date,
            summary: $summary,
            reportPath: $reportPath
        );

        Notification::send($users, $notification);

        Log::info('Daily reconciliation notification sent', [
            'recipients_count' => $users->count(),
        ]);
    }
}
