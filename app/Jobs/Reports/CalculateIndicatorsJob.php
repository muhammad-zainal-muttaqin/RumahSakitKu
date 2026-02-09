<?php

declare(strict_types=1);

namespace App\Jobs\Reports;

use Exception;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Calculate Hospital Indicators Job
 *
 * Background job to calculate hospital performance indicators (BOR, LOS, TOI, BTO, etc.)
 * Stores results in cache and database for quick access.
 *
 * @package App\Jobs\Reports
 */
class CalculateIndicatorsJob implements ShouldQueue
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
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param bool $storeInDatabase Whether to store results in database
     * @param bool $storeInCache Whether to store results in cache
     * @param int $cacheTtl Cache TTL in minutes
     */
    public function __construct(
        public string $startDate,
        public string $endDate,
        public bool $storeInDatabase = true,
        public bool $storeInCache = true,
        public int $cacheTtl = 1440 // 24 hours
    ) {
    }

    /**
     * Execute the job.
     *
     * @param ReportService $reportService The report service
     * @throws Throwable
     */
    public function handle(ReportService $reportService): void
    {
        $startTime = microtime(true);

        try {
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);

            Log::info('Starting hospital indicators calculation', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            // Calculate all indicators
            $indicators = $this->calculateAllIndicators($reportService, $startDate, $endDate);

            // Store in cache
            if ($this->storeInCache) {
                $this->storeInCache($indicators);
            }

            // Store in database
            if ($this->storeInDatabase) {
                $this->storeInDatabase($indicators);
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Hospital indicators calculation completed', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'execution_time_seconds' => $executionTime,
                'indicators_calculated' => count($indicators),
            ]);
        } catch (Throwable $e) {
            Log::error('Hospital indicators calculation failed', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
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
        Log::error('Hospital indicators calculation job failed after retries', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Calculate all hospital indicators.
     *
     * @param ReportService $reportService The report service
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return array<string, mixed> The calculated indicators
     */
    private function calculateAllIndicators(
        ReportService $reportService,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $cacheKey = "indicators_{$this->startDate}_{$this->endDate}";

        return [
            'period' => [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'bed_occupancy' => [
                'bor' => $reportService->calculateBOR($startDate, $endDate),
                'los' => $reportService->calculateLOS($startDate, $endDate),
                'toi' => $reportService->calculateTOI($startDate, $endDate),
                'bto' => $reportService->calculateBTO($startDate, $endDate),
            ],
            'mortality' => [
                'gdr' => $reportService->calculateGDR($startDate, $endDate),
                'ndr' => $reportService->calculateNDR($startDate, $endDate),
            ],
            'visits' => $reportService->getVisitCountsByType($startDate, $endDate),
            'revenue' => $reportService->getRevenueByPaymentMethod($startDate, $endDate),
            'room_occupancy' => $reportService->getRoomOccupancyByClass(),
            'top_diseases' => $reportService->getTopDiseases($startDate, $endDate, 10),
            'calculated_at' => now()->toIso8601String(),
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Store indicators in cache.
     *
     * @param array<string, mixed> $indicators The indicators to store
     */
    private function storeInCache(array $indicators): void
    {
        $cacheKey = "indicators_{$this->startDate}_{$this->endDate}";

        Cache::put($cacheKey, $indicators, now()->addMinutes($this->cacheTtl));

        // Also store individual indicators for quick access
        foreach ($indicators['bed_occupancy'] ?? [] as $key => $value) {
            Cache::put("indicator_{$key}_{$this->startDate}_{$this->endDate}", $value, now()->addMinutes($this->cacheTtl));
        }

        Log::debug('Indicators stored in cache', [
            'cache_key' => $cacheKey,
            'ttl_minutes' => $this->cacheTtl,
        ]);
    }

    /**
     * Store indicators in database.
     *
     * @param array<string, mixed> $indicators The indicators to store
     */
    private function storeInDatabase(array $indicators): void
    {
        // Store in a database table if it exists
        // This is a flexible implementation that handles missing tables gracefully
        try {
            DB::table('hospital_indicators')->updateOrInsert(
                [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                ],
                [
                    'bor' => $indicators['bed_occupancy']['bor'] ?? 0,
                    'los' => $indicators['bed_occupancy']['los'] ?? 0,
                    'toi' => $indicators['bed_occupancy']['toi'] ?? 0,
                    'bto' => $indicators['bed_occupancy']['bto'] ?? 0,
                    'gdr' => $indicators['mortality']['gdr'] ?? 0,
                    'ndr' => $indicators['mortality']['ndr'] ?? 0,
                    'raw_data' => json_encode($indicators),
                    'calculated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Log::debug('Indicators stored in database', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);
        } catch (Exception $e) {
            // Table might not exist, log warning but don't fail
            Log::warning('Could not store indicators in database', [
                'error' => $e->getMessage(),
                'note' => 'Consider creating hospital_indicators table if needed',
            ]);
        }
    }
}
