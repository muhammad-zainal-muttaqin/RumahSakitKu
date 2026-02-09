<?php

declare(strict_types=1);

namespace App\Jobs\Inpatient;

use Exception;
use Illuminate\Support\Collection;
use App\Models\MasterData\Bed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Check Expired Bed Reservations Job
 *
 * Hourly job to check and release expired bed reservations.
 * Reservations older than 2 hours are automatically released.
 *
 * @package App\Jobs\Inpatient
 */
class CheckExpiredReservationsJob implements ShouldQueue
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
     * Reservation expiry time in hours.
     */
    public int $reservationExpiryHours = 2;

    /**
     * Create a new job instance.
     *
     * @param int $expiryHours Hours after which reservations expire
     */
    public function __construct(
        public ?int $expiryHours = null
    ) {
        if ($expiryHours) {
            $this->reservationExpiryHours = $expiryHours;
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
            Log::info('Starting expired reservation check', [
                'expiry_hours' => $this->reservationExpiryHours,
            ]);

            // Find reserved beds that have expired
            $expiredBeds = $this->getExpiredReservedBeds();

            $releasedCount = 0;
            $failedReleases = [];

            foreach ($expiredBeds as $bed) {
                try {
                    $this->releaseReservation($bed);
                    $releasedCount++;

                    Log::info('Released expired bed reservation', [
                        'bed_id' => $bed->id,
                        'bed_number' => $bed->bed_number,
                        'reserved_at' => $bed->updated_at->toIso8601String(),
                        'hours_reserved' => $bed->updated_at->diffInHours(now()),
                    ]);
                } catch (Exception $e) {
                    $failedReleases[] = [
                        'bed_id' => $bed->id,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to release bed reservation', [
                        'bed_id' => $bed->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Expired reservation check completed', [
                'expired_beds_found' => $expiredBeds->count(),
                'released_count' => $releasedCount,
                'failed_releases' => count($failedReleases),
                'execution_time_seconds' => $executionTime,
            ]);
        } catch (Throwable $e) {
            Log::error('Expired reservation check failed', [
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
        Log::error('Expired reservation check job failed after retries', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Get beds with expired reservations.
     *
     * @return Collection
     */
    private function getExpiredReservedBeds()
    {
        return Bed::active()
            ->where('status', 'reserved')
            ->whereNull('current_visit_id')
            ->where('updated_at', '<=', now()->subHours($this->reservationExpiryHours))
            ->with('room')
            ->get();
    }

    /**
     * Release a bed reservation.
     *
     * @param Bed $bed The bed to release
     */
    private function releaseReservation(Bed $bed): void
    {
        $oldStatus = $bed->status;

        $bed->update([
            'status' => 'kosong',
            'notes' => 'Auto-released: Reservation expired after ' . $this->reservationExpiryHours . ' hours',
        ]);

        // Log the release in audit log if available
        if (method_exists($bed, 'logAudit')) {
            $bed->logAudit('reservation_released', [
                'old_status' => $oldStatus,
                'new_status' => 'kosong',
                'reason' => 'reservation_expired',
                'expiry_hours' => $this->reservationExpiryHours,
            ]);
        }
    }
}
