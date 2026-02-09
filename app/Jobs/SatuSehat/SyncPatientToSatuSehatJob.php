<?php

declare(strict_types=1);

namespace App\Jobs\SatuSehat;

use RuntimeException;
use App\Models\Patient\Patient;
use App\Models\SatuSehatLog;
use App\Services\SatuSehat\SatuSehatPatientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync Patient to Satu Sehat Job
 *
 * Background job to synchronize patient data to Satu Sehat (FHIR).
 * Generates or retrieves IHS (Indonesia Health Services) number for the patient.
 *
 * @package App\Jobs\SatuSehat
 */
class SyncPatientToSatuSehatJob implements ShouldQueue
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
     * @param int $patientId The patient ID to sync
     */
    public function __construct(
        public int $patientId
    ) {
    }

    /**
     * Execute the job.
     *
     * @param SatuSehatPatientService $patientService The Satu Sehat patient service
     * @throws Throwable
     */
    public function handle(SatuSehatPatientService $patientService): void
    {
        $startTime = microtime(true);
        $patient = Patient::findOrFail($this->patientId);

        try {
            Log::info('Starting Satu Sehat patient sync', [
                'patient_id' => $this->patientId,
                'patient_name' => $patient->name,
                'nik' => $patient->nik,
            ]);

            // Call service to generate/get IHS number
            $result = $patientService->generateNIK($patient);

            $executionTime = round((microtime(true) - $startTime) * 1000);

            if ($result['success'] && !empty($result['ihs_number'])) {
                // Update patient with IHS number
                // Note: This assumes a field 'ihs_number' exists on patients table
                // If not, you may need to add this migration
                if (in_array('ihs_number', $patient->getFillable(), true)) {
                    $patient->update(['ihs_number' => $result['ihs_number']]);
                }

                // Log successful sync
                SatuSehatLog::log(
                    resourceType: 'Patient',
                    localType: Patient::class,
                    localId: $this->patientId,
                    action: 'generate_ihs',
                    requestData: ['nik' => $patient->nik],
                    responseData: ['ihs_number' => $result['ihs_number']],
                    status: 'success',
                    responseTimeMs: $executionTime
                );

                Log::info('Satu Sehat patient sync completed successfully', [
                    'patient_id' => $this->patientId,
                    'ihs_number' => $result['ihs_number'],
                    'execution_time_ms' => $executionTime,
                ]);
            } else {
                throw new RuntimeException($result['error'] ?? 'Failed to generate IHS number');
            }
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Log failed sync
            SatuSehatLog::log(
                resourceType: 'Patient',
                localType: Patient::class,
                localId: $this->patientId,
                action: 'generate_ihs',
                requestData: ['nik' => $patient->nik ?? null],
                responseData: null,
                status: 'failed',
                errorMessage: $e->getMessage(),
                responseTimeMs: $executionTime
            );

            Log::error('Satu Sehat patient sync failed', [
                'patient_id' => $this->patientId,
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
        Log::error('Satu Sehat patient sync job failed after retries', [
            'patient_id' => $this->patientId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark as permanently failed in log
        SatuSehatLog::log(
            resourceType: 'Patient',
            localType: Patient::class,
            localId: $this->patientId,
            action: 'generate_ihs',
            requestData: ['retry_exhausted' => true],
            responseData: null,
            status: 'failed',
            errorMessage: 'Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage()
        );
    }
}
