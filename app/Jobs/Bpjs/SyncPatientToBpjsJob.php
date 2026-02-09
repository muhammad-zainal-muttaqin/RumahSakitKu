<?php

declare(strict_types=1);

namespace App\Jobs\Bpjs;

use App\Models\BpjsLog;
use App\Models\Patient\Patient;
use App\Services\BPJS\BpjsVclaimService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync Patient Data to BPJS Job
 *
 * Background job to synchronize patient data with BPJS system.
 * Handles participant verification and data updates to BPJS VClaim.
 *
 * @package App\Jobs\Bpjs
 */
class SyncPatientToBpjsJob implements ShouldQueue
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
     * @param array<string, mixed> $bpjsData Additional BPJS-specific data
     */
    public function __construct(
        public int $patientId,
        public array $bpjsData = []
    ) {
    }

    /**
     * Execute the job.
     *
     * @param BpjsVclaimService $bpjsService The BPJS VClaim service
     * @throws Throwable
     */
    public function handle(BpjsVclaimService $bpjsService): void
    {
        $startTime = microtime(true);
        $patient = Patient::findOrFail($this->patientId);

        try {
            Log::info('Starting BPJS patient sync', [
                'patient_id' => $this->patientId,
                'patient_name' => $patient->name,
            ]);

            // Get participant data from BPJS by NIK
            $tglSep = now()->format('Y-m-d');
            $response = $bpjsService->getPesertaByNik($patient->nik, $tglSep);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log successful request
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => "Peserta/nik/{$patient->nik}/tglSEP/{$tglSep}",
                'method' => 'GET',
                'request_data' => Crypt::encryptString(json_encode([
                    'patient_id' => $this->patientId,
                    'nik' => $patient->nik,
                ])),
                'response_data' => Crypt::encryptString(json_encode($response)),
                'http_status' => 200,
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
                'executed_at' => now(),
            ]);

            // Update patient with BPJS data if available
            if (isset($response['response']['peserta'])) {
                $peserta = $response['response']['peserta'];
                
                // Update BPJS card number if available
                if (!empty($peserta['noKartu']) && empty($patient->bpjs_card_number)) {
                    $patient->update(['bpjs_card_number' => $peserta['noKartu']]);
                }
            }

            Log::info('BPJS patient sync completed successfully', [
                'patient_id' => $this->patientId,
                'execution_time_ms' => $executionTime,
            ]);
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log failed request
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => "Peserta/nik/{$patient->nik}/tglSEP/" . now()->format('Y-m-d'),
                'method' => 'GET',
                'request_data' => Crypt::encryptString(json_encode([
                    'patient_id' => $this->patientId,
                    'nik' => $patient->nik,
                ])),
                'response_data' => null,
                'http_status' => 500,
                'error_message' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
                'executed_at' => now(),
            ]);

            Log::error('BPJS sync failed', [
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
        Log::error('BPJS sync job failed after retries', [
            'patient_id' => $this->patientId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Additional notification logic can be added here
        // e.g., notify administrators about the failed sync
    }
}
