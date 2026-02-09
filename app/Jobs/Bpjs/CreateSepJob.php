<?php

declare(strict_types=1);

namespace App\Jobs\Bpjs;

use RuntimeException;
use App\Models\BpjsLog;
use App\Models\Patient\Visit;
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
 * Create SEP (Surat Eligibilitas Peserta) Job
 *
 * Background job to create SEP asynchronously via BPJS VClaim API.
 * Updates the visit record with the generated SEP number.
 *
 * @package App\Jobs\Bpjs
 */
class CreateSepJob implements ShouldQueue
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
     * @param int $visitId The visit ID to create SEP for
     */
    public function __construct(
        public int $visitId
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
        $visit = Visit::with(['patient', 'polyclinic', 'doctor'])->findOrFail($this->visitId);

        try {
            Log::info('Starting SEP creation', [
                'visit_id' => $this->visitId,
                'visit_number' => $visit->visit_number,
            ]);

            // Prepare SEP data
            $sepData = $this->prepareSepData($visit);

            // Create SEP via BPJS API
            $response = $bpjsService->createSep($sepData);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if (isset($response['response']['sep']['noSep'])) {
                $sepNumber = $response['response']['sep']['noSep'];

                // Update visit with SEP number
                $visit->update(['bpjs_sep_number' => $sepNumber]);

                // Log successful request
                BpjsLog::create([
                    'service_type' => 'vclaim',
                    'endpoint' => 'SEP/2.0/insert',
                    'method' => 'POST',
                    'request_data' => Crypt::encryptString(json_encode($sepData)),
                    'response_data' => Crypt::encryptString(json_encode($response)),
                    'http_status' => 200,
                    'execution_time_ms' => $executionTime,
                    'user_id' => auth()->id(),
                    'executed_at' => now(),
                ]);

                Log::info('SEP created successfully', [
                    'visit_id' => $this->visitId,
                    'sep_number' => $sepNumber,
                    'execution_time_ms' => $executionTime,
                ]);
            } else {
                throw new RuntimeException('SEP number not found in response: ' . json_encode($response));
            }
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log failed request
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => 'SEP/2.0/insert',
                'method' => 'POST',
                'request_data' => Crypt::encryptString(json_encode($this->prepareSepData($visit))),
                'response_data' => null,
                'http_status' => 500,
                'error_message' => $e->getMessage(),
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
                'executed_at' => now(),
            ]);

            Log::error('SEP creation failed', [
                'visit_id' => $this->visitId,
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
        Log::error('SEP creation job failed after retries', [
            'visit_id' => $this->visitId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Prepare SEP data from visit.
     *
     * @param Visit $visit The visit model
     * @return array<string, mixed> The prepared SEP data
     */
    private function prepareSepData(Visit $visit): array
    {
        $patient = $visit->patient;
        $ppkPelayanan = config('bpjs.ppk_code', '1234567');

        return [
            'noKartu' => $patient->bpjs_card_number ?? '',
            'tglSep' => $visit->visit_date->format('Y-m-d'),
            'ppkPelayanan' => $ppkPelayanan,
            'jnsPelayanan' => $visit->visit_type === 'rawat_inap' ? '1' : '2',
            'klsRawat' => '3',
            'klsRawatHak' => '3',
            'noMR' => $patient->medical_record_number,
            'asalRujukan' => $visit->referral_from === 'faskes_1' ? '1' : '2',
            'tglRujukan' => $visit->referral_from ? $visit->visit_date->format('Y-m-d') : '',
            'noRujukan' => $visit->referral_number ?? '',
            'ppkRujukan' => '',
            'catatan' => $visit->complaint ?? '',
            'diagAwal' => '', // Should be filled from assessment
            'poliTujuan' => $visit->polyclinic?->bpjs_code ?? '',
            'poliEksekutif' => '0',
            'cob' => '0',
            'katarak' => '0',
            'lakaLantas' => '0',
            'noTelp' => $patient->phone ?? '',
            'user' => auth()->user()?->name ?? 'system',
        ];
    }
}
