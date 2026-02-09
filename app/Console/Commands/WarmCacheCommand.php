<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use App\Models\MasterData\Polyclinic;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\MasterData\Medicine;
use App\Services\CacheService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Warm Cache Command
 * 
 * Pre-populates cache with frequently accessed data.
 * Run this command during low-traffic periods or after cache clear.
 * 
 * Usage: php artisan cache:warm [--sections=all,queues,rooms,medicines,patients,indicators]
 */
class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--sections=all : Comma-separated list of sections to warm (all,queues,rooms,medicines,patients,indicators)}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm the cache by pre-populating frequently accessed data';

    /**
     * Sections that can be warmed.
     *
     * @var array<string, string>
     */
    private array $availableSections = [
        'queues' => 'Queue statistics',
        'rooms' => 'Room occupancy',
        'medicines' => 'Top medicines',
        'patients' => 'Active patients',
        'indicators' => 'Hospital indicators',
        'visits' => 'Visit statistics',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sections = $this->option('sections');
        $sectionsToWarm = $sections === 'all' 
            ? array_keys($this->availableSections)
            : explode(',', $sections);

        // Validate sections
        $invalidSections = array_diff($sectionsToWarm, array_keys($this->availableSections));
        if (!empty($invalidSections)) {
            $this->error('Invalid sections: ' . implode(', ', $invalidSections));
            $this->line('Available sections: ' . implode(', ', array_keys($this->availableSections)));
            return self::FAILURE;
        }

        // Confirmation prompt
        if (!$this->option('force') && !$this->confirm(
            'This will warm the following sections: ' . implode(', ', $sectionsToWarm) . '. Continue?'
        )) {
            $this->info('Cache warming cancelled.');
            return self::SUCCESS;
        }

        $this->info('Starting cache warm-up...');
        $this->newLine();

        $startTime = microtime(true);
        $results = [];

        foreach ($sectionsToWarm as $section) {
            $sectionStart = microtime(true);
            $this->info("Warming {$this->availableSections[$section]}...");

            try {
                $count = match ($section) {
                    'queues' => $this->warmQueueStats(),
                    'rooms' => $this->warmRoomOccupancy(),
                    'medicines' => $this->warmMedicines(),
                    'patients' => $this->warmActivePatients(),
                    'indicators' => $this->warmIndicators(),
                    'visits' => $this->warmVisitStats(),
                    default => 0,
                };

                $duration = round((microtime(true) - $sectionStart) * 1000, 2);
                $results[$section] = ['count' => $count, 'duration_ms' => $duration];
                
                $this->info("  ✓ Cached {$count} items in {$duration}ms");
            } catch (Exception $e) {
                $results[$section] = ['error' => $e->getMessage()];
                $this->error("  ✗ Failed: {$e->getMessage()}");
                Log::error('Cache warm failed for section: ' . $section, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000, 2);

        $this->newLine();
        $this->info('Cache warm-up completed!');
        $this->table(
            ['Section', 'Items', 'Duration (ms)', 'Status'],
            collect($results)->map(function ($result, $section) {
                return [
                    $this->availableSections[$section] ?? $section,
                    $result['count'] ?? '-',
                    $result['duration_ms'] ?? '-',
                    isset($result['error']) ? "Error: {$result['error']}" : '✓ Success',
                ];
            })->toArray()
        );
        $this->info("Total time: {$totalDuration}ms");

        Log::info('Cache warm-up completed', [
            'sections' => $sectionsToWarm,
            'results' => $results,
            'total_duration_ms' => $totalDuration,
        ]);

        return self::SUCCESS;
    }

    /**
     * Warm queue statistics cache.
     *
     * @return int Number of items cached
     */
    private function warmQueueStats(): int
    {
        $polyclinics = Polyclinic::active()->pluck('id');
        
        foreach ($polyclinics as $polyclinicId) {
            CacheService::forgetQueueStats($polyclinicId);
            CacheService::getQueueStats($polyclinicId);
        }

        return $polyclinics->count();
    }

    /**
     * Warm room occupancy cache.
     *
     * @return int Number of rooms cached
     */
    private function warmRoomOccupancy(): int
    {
        CacheService::forgetRoomOccupancy();
        $occupancy = CacheService::getRoomOccupancy();
        
        return $occupancy['total_rooms'] ?? 0;
    }

    /**
     * Warm medicines cache.
     *
     * @return int Number of medicines cached
     */
    private function warmMedicines(): int
    {
        $medicines = CacheService::getTopMedicines(50);
        return $medicines->count();
    }

    /**
     * Warm active patients cache.
     *
     * @return int Number of patients cached
     */
    private function warmActivePatients(): int
    {
        $patients = CacheService::getActivePatients();
        return $patients->count();
    }

    /**
     * Warm hospital indicators cache.
     *
     * @return int Number of indicator sets cached
     */
    private function warmIndicators(): int
    {
        $reportService = app(ReportService::class);
        $dates = [
            now()->toDateString(),
            now()->subDay()->toDateString(),
            now()->startOfWeek()->toDateString(),
            now()->startOfMonth()->toDateString(),
        ];

        foreach ($dates as $date) {
            $dateObj = Carbon::parse($date);
            
            $indicators = [
                'date' => $date,
                'bor' => $reportService->calculateBOR($dateObj->copy()->startOfMonth(), $dateObj),
                'los' => $reportService->calculateLOS($dateObj->copy()->startOfMonth(), $dateObj),
                'toi' => $reportService->calculateTOI($dateObj->copy()->startOfMonth(), $dateObj),
                'bto' => $reportService->calculateBTO($dateObj->copy()->startOfMonth(), $dateObj),
                'visit_counts' => $reportService->getVisitCountsByType($dateObj->copy()->startOfDay(), $dateObj->copy()->endOfDay()),
                'bed_stats' => $reportService->getHospitalBedStatistics(),
            ];

            CacheService::putIndicators($date, $indicators);
        }

        return count($dates);
    }

    /**
     * Warm visit statistics cache.
     *
     * @return int Number of stats cached
     */
    private function warmVisitStats(): int
    {
        // Warm today's visit count
        CacheService::getTodayVisitCount();
        
        // Warm visit counts by type
        CacheService::getTodayVisitCountsByType();

        return 2;
    }
}
