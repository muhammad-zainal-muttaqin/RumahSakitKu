<?php

declare(strict_types=1);

namespace App\Jobs\SatuSehat;

use RuntimeException;
use InvalidArgumentException;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\SatuSehatLog;
use App\Services\SatuSehat\SatuSehatEncounterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync Encounter to Satu Sehat Job
 *
 * Background job to synchronize visit/encounter data to Satu Sehat (FHIR).
 * Supports create and update actions for encounter resources.
 *
 * @package App\Jobs\SatuSehat
 */
class SyncEncounterJob implements ShouldQueue
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
     * Create a new job instance.
     *
     * @param int $visitId The visit ID to sync
     * @param string $action The action to perform (create/update)
     * @param string|null $encounterId Existing encounter ID for updates
     */
    public function __construct(
        public int $visitId,
        public string $action = 'create',
        public ?string $encounterId = null
    ) {
    }

    /**
     * Execute the job.
     *
     * @param SatuSehatEncounterService $encounterService The Satu Sehat encounter service
     * @throws Throwable
     */
    public function handle(SatuSehatEncounterService $encounterService): void
    {
        $startTime = microtime(true);
        $visit = Visit::with(['patient', 'polyclinic', 'doctor'])->findOrFail($this->visitId);

        try {
            Log::info('Starting Satu Sehat encounter sync', [
                'visit_id' => $this->visitId,
                'visit_number' => $visit->visit_number,
                'action' => $this->action,
            ]);

            // Get patient IHS number
            $patientIhsNumber = $this->getPatientIhsNumber($visit);

            if (empty($patientIhsNumber)) {
                throw new RuntimeException('Patient IHS number not found. Please sync patient first.');
            }

            $result = match ($this->action) {
                'create' => $this->createEncounter($encounterService, $visit, $patientIhsNumber),
                'update' => $this->updateEncounter($encounterService),
                default => throw new InvalidArgumentException("Invalid action: {$this->action}"),
            };

            $executionTime = round((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                // Log successful sync
                SatuSehatLog::log(
                    resourceType: 'Encounter',
                    localType: Visit::class,
                    localId: $this->visitId,
                    action: $this->action,
                    requestData: ['visit_number' => $visit->visit_number],
                    responseData: $result['data'] ?? null,
                    status: 'success',
                    responseTimeMs: $executionTime
                );

                Log::info('Satu Sehat encounter sync completed successfully', [
                    'visit_id' => $this->visitId,
                    'action' => $this->action,
                    'encounter_id' => $result['data']['id'] ?? null,
                    'execution_time_ms' => $executionTime,
                ]);
            } else {
                throw new RuntimeException($result['error'] ?? 'Failed to sync encounter');
            }
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Log failed sync
            SatuSehatLog::log(
                resourceType: 'Encounter',
                localType: Visit::class,
                localId: $this->visitId,
                action: $this->action,
                requestData: [
                    'visit_number' => $visit->visit_number,
                    'action' => $this->action,
                ],
                responseData: null,
                status: 'failed',
                errorMessage: $e->getMessage(),
                responseTimeMs: $executionTime
            );

            Log::error('Satu Sehat encounter sync failed', [
                'visit_id' => $this->visitId,
                'action' => $this->action,
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
        Log::error('Satu Sehat encounter sync job failed after retries', [
            'visit_id' => $this->visitId,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Create encounter in Satu Sehat.
     *
     * @param SatuSehatEncounterService $encounterService The encounter service
     * @param Visit $visit The visit model
     * @param string $patientIhsNumber The patient IHS number
     * @return array<string, mixed> The result
     */
    private function createEncounter(
        SatuSehatEncounterService $encounterService,
        Visit $visit,
        string $patientIhsNumber
    ): array {
        $locationId = config('satusehat.location_id');

        return $encounterService->createEncounter($visit, $patientIhsNumber, $locationId);
    }

    /**
     * Update encounter in Satu Sehat.
     *
     * @param SatuSehatEncounterService $encounterService The encounter service
     * @return array<string, mixed> The result
     */
    private function updateEncounter(SatuSehatEncounterService $encounterService): array
    {
        if (empty($this->encounterId)) {
            throw new RuntimeException('Encounter ID is required for update action');
        }

        // Get current visit status and map to encounter status
        $visit = Visit::findOrFail($this->visitId);
        $status = $this->mapVisitStatusToEncounter($visit->status);

        return $encounterService->updateEncounterStatus($this->encounterId, $status);
    }

    /**
     * Get patient IHS number from SatuSehatLog.
     *
     * @param Visit $visit The visit model
     * @return string|null The IHS number
     */
    private function getPatientIhsNumber(Visit $visit): ?string
    {
        // First check if patient has ihs_number field
        if (!empty($visit->patient->ihs_number)) {
            return $visit->patient->ihs_number;
        }

        // Otherwise, try to get from SatuSehatLog
        return SatuSehatLog::getFhirId(
            localType: Patient::class,
            localId: $visit->patient_id,
            resourceType: 'Patient'
        );
    }

    /**
     * Map visit status to FHIR encounter status.
     *
     * @param string|null $visitStatus The visit status
     * @return string The FHIR encounter status
     */
    private function mapVisitStatusToEncounter(?string $visitStatus): string
    {
        return match (strtolower($visitStatus ?? '')) {
            'registered', 'pendaftaran' => 'arrived',
            'waiting', 'menunggu' => 'triaged',
            'in-progress', 'dalam proses', 'pelayanan' => 'in-progress',
            'completed', 'selesai', 'done' => 'finished',
            'cancelled', 'dibatalkan' => 'cancelled',
            default => 'in-progress',
        };
    }
}
