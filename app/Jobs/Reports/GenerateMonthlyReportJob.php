<?php

declare(strict_types=1);

namespace App\Jobs\Reports;

use InvalidArgumentException;
use App\Models\User;
use App\Notifications\ReportGeneratedNotification;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Generate Monthly RL Report Job
 *
 * Background job to generate monthly hospital reports (RL 1, RL 3, RL 4, RL 5).
 * Stores generated files in storage and notifies user upon completion.
 *
 * @package App\Jobs\Reports
 */
class GenerateMonthlyReportJob implements ShouldQueue
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
     * @param int $month The month (1-12)
     * @param int $year The year (e.g., 2024)
     * @param int $userId The user ID to notify when complete
     * @param array<string> $reportTypes The report types to generate (rl1, rl3, rl4, rl5)
     */
    public function __construct(
        public int $month,
        public int $year,
        public int $userId,
        public array $reportTypes = ['rl1', 'rl3', 'rl4', 'rl5']
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
        $user = User::findOrFail($this->userId);

        try {
            Log::info('Starting monthly report generation', [
                'month' => $this->month,
                'year' => $this->year,
                'user_id' => $this->userId,
                'report_types' => $this->reportTypes,
            ]);

            $startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $generatedFiles = [];

            foreach ($this->reportTypes as $reportType) {
                $filePath = $this->generateReport($reportService, $reportType, $startDate, $endDate);
                if ($filePath) {
                    $generatedFiles[$reportType] = $filePath;
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Monthly report generation completed', [
                'month' => $this->month,
                'year' => $this->year,
                'generated_files' => count($generatedFiles),
                'execution_time_seconds' => $executionTime,
            ]);

            // Notify user
            $user->notify(new ReportGeneratedNotification(
                month: $this->month,
                year: $this->year,
                files: $generatedFiles,
                success: true
            ));
        } catch (Throwable $e) {
            Log::error('Monthly report generation failed', [
                'month' => $this->month,
                'year' => $this->year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Notify user of failure
            $user->notify(new ReportGeneratedNotification(
                month: $this->month,
                year: $this->year,
                files: [],
                success: false,
                errorMessage: $e->getMessage()
            ));

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
        Log::error('Monthly report generation job failed after retries', [
            'month' => $this->month,
            'year' => $this->year,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Generate individual report.
     *
     * @param ReportService $reportService The report service
     * @param string $reportType The report type (rl1, rl3, rl4, rl5)
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return string|null The file path or null on failure
     */
    private function generateReport(
        ReportService $reportService,
        string $reportType,
        Carbon $startDate,
        Carbon $endDate
    ): ?string {
        $reportName = "RL_{$reportType}_{$this->month}_{$this->year}";
        $fileName = "{$reportName}.xlsx";
        $directory = "reports/{$this->year}/{$this->month}";
        $filePath = "{$directory}/{$fileName}";

        // Ensure directory exists
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $data = match ($reportType) {
            'rl1' => $this->generateRL1Data($reportService, $startDate, $endDate),
            'rl3' => $this->generateRL3Data($reportService, $startDate, $endDate),
            'rl4' => $this->generateRL4Data($reportService, $startDate, $endDate),
            'rl5' => $this->generateRL5Data($reportService, $startDate, $endDate),
            default => throw new InvalidArgumentException("Unknown report type: {$reportType}"),
        };

        // Generate Excel file using Laravel Excel or similar
        // For now, we store JSON data that can be converted later
        Storage::put("{$directory}/{$reportName}.json", json_encode($data, JSON_PRETTY_PRINT));

        Log::info("Generated {$reportType} report", [
            'file_path' => $filePath,
            'records' => count($data),
        ]);

        return $filePath;
    }

    /**
     * Generate RL 1 (Hospital Statistics) data.
     *
     * @param ReportService $reportService The report service
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return array<string, mixed>
     */
    private function generateRL1Data(ReportService $reportService, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'indicators' => [
                'bor' => $reportService->calculateBOR($startDate, $endDate),
                'los' => $reportService->calculateLOS($startDate, $endDate),
                'toi' => $reportService->calculateTOI($startDate, $endDate),
                'bto' => $reportService->calculateBTO($startDate, $endDate),
                'gdr' => $reportService->calculateGDR($startDate, $endDate),
                'ndr' => $reportService->calculateNDR($startDate, $endDate),
            ],
            'bed_statistics' => $reportService->getHospitalBedStatistics(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate RL 3 (Service Statistics) data.
     *
     * @param ReportService $reportService The report service
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return array<string, mixed>
     */
    private function generateRL3Data(ReportService $reportService, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
            ],
            'service_statistics' => $reportService->getServiceStatistics($startDate, $endDate),
            'employee_statistics' => $reportService->getEmployeeStatistics(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate RL 4 (Morbidity Statistics) data.
     *
     * @param ReportService $reportService The report service
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return array<string, mixed>
     */
    private function generateRL4Data(ReportService $reportService, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
            ],
            'morbidity' => $reportService->getMorbidityStatistics($startDate, $endDate),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate RL 5 (Mortality Statistics) data.
     *
     * @param ReportService $reportService The report service
     * @param Carbon $startDate The start date
     * @param Carbon $endDate The end date
     * @return array<string, mixed>
     */
    private function generateRL5Data(ReportService $reportService, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'month' => $this->month,
                'year' => $this->year,
            ],
            'mortality' => $reportService->getMortalityStatistics($startDate, $endDate),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
