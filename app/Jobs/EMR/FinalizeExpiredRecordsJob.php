<?php

declare(strict_types=1);

namespace App\Jobs\EMR;

use Exception;
use Illuminate\Support\Collection;
use App\Models\Clinical\MedicalRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finalize Expired Medical Records Job
 *
 * Daily job to auto-finalize medical records that have not been
 * finalized within 24 hours of creation.
 *
 * @package App\Jobs\EMR
 */
class FinalizeExpiredRecordsJob implements ShouldQueue
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
     * Hours after which records are auto-finalized.
     */
    public int $autoFinalizeAfterHours = 24;

    /**
     * Create a new job instance.
     *
     * @param int|null $hoursAfter Hours after which to auto-finalize
     * @param bool $addWarning Whether to add warning note
     */
    public function __construct(
        ?int $hoursAfter = null,
        public bool $addWarning = true
    ) {
        if ($hoursAfter) {
            $this->autoFinalizeAfterHours = $hoursAfter;
        }
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
            Log::info('Starting expired medical records finalization', [
                'hours_threshold' => $this->autoFinalizeAfterHours,
            ]);

            // Get records that need to be finalized
            $expiredRecords = $this->getExpiredRecords();

            $finalizedCount = 0;
            $failedFinalizations = [];

            foreach ($expiredRecords as $record) {
                try {
                    $this->finalizeRecord($record);
                    $finalizedCount++;

                    Log::info('Auto-finalized medical record', [
                        'record_id' => $record->id,
                        'record_number' => $record->record_number,
                        'patient_id' => $record->patient_id,
                        'created_at' => $record->created_at->toIso8601String(),
                        'hours_pending' => $record->created_at->diffInHours(now()),
                    ]);
                } catch (Exception $e) {
                    $failedFinalizations[] = [
                        'record_id' => $record->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to auto-finalize medical record', [
                        'record_id' => $record->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Expired medical records finalization completed', [
                'records_found' => $expiredRecords->count(),
                'finalized_count' => $finalizedCount,
                'failed_count' => count($failedFinalizations),
                'execution_time_seconds' => $executionTime,
            ]);
        } catch (Throwable $e) {
            Log::error('Expired medical records finalization failed', [
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
        Log::error('Expired medical records finalization job failed after retries', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get medical records that need to be auto-finalized.
     *
     * @return Collection
     */
    private function getExpiredRecords()
    {
        return MedicalRecord::draft()
            ->where('created_at', '<=', now()->subHours($this->autoFinalizeAfterHours))
            ->with(['patient', 'visit'])
            ->get();
    }

    /**
     * Finalize a medical record.
     *
     * @param MedicalRecord $record The medical record
     */
    private function finalizeRecord(MedicalRecord $record): void
    {
        $warningNote = $this->addWarning
            ? "\n\n[AUTO-FINALIZED] This record was automatically finalized after {$this->autoFinalizeAfterHours} hours."
            : '';

        $record->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => null, // System finalized
            'notes' => $record->notes . $warningNote,
        ]);

        // Log the action in audit log if available
        if (method_exists($record, 'logAudit')) {
            $record->logAudit('auto_finalized', [
                'reason' => 'expiration',
                'hours_pending' => $record->created_at->diffInHours(now()),
                'warning_added' => $this->addWarning,
            ]);
        }
    }
}
