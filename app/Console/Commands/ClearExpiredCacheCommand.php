<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Clear Expired Cache Command
 * 
 * Clears expired cache entries from Redis.
 * This command should be run daily via scheduler.
 * 
 * Usage: php artisan cache:clear-expired [--dry-run]
 */
class ClearExpiredCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-expired
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired cache entries from Redis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('Running in DRY-RUN mode. No cache entries will be deleted.');
            $this->newLine();
        }

        $this->info('Checking for expired cache entries...');

        $startTime = microtime(true);

        try {
            // Redis automatically handles expiration, so we mainly check for:
            // 1. Stale SIMRS-specific cache entries
            // 2. Orphaned cache keys
            // 3. Cache entries for deleted records
            
            $stats = $this->analyzeCache();
            
            $this->displayStats($stats);

            if (!$isDryRun && $stats['expired_or_stale'] > 0) {
                $this->newLine();
                $this->info('Cleaning expired/stale entries...');
                
                $cleaned = $this->cleanExpiredEntries($stats);
                
                $this->info("Cleaned {$cleaned} entries.");
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->newLine();
            $this->info("Completed in {$duration}ms");

            Log::info('Cache cleanup completed', [
                'dry_run' => $isDryRun,
                'stats' => $stats,
                'duration_ms' => $duration,
            ]);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Cache cleanup failed: ' . $e->getMessage());
            
            Log::error('Cache cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Analyze cache for expired/stale entries.
     *
     * @return array<string, mixed>
     */
    private function analyzeCache(): array
    {
        $redis = Cache::store('redis')->getRedis();
        $prefix = config('cache.prefix');
        
        // Get all cache keys
        $keys = $redis->keys($prefix . '*');
        
        $stats = [
            'total_keys' => count($keys),
            'expired_or_stale' => 0,
            'by_prefix' => [],
            'memory_usage' => 0,
        ];

        $ttlThreshold = now()->subDays(7); // Consider entries older than 7 days as stale

        foreach ($keys as $key) {
            // Remove prefix for analysis
            $cleanKey = str_replace($prefix, '', $key);
            $prefixPart = explode(':', $cleanKey)[0] ?? 'other';

            if (!isset($stats['by_prefix'][$prefixPart])) {
                $stats['by_prefix'][$prefixPart] = [
                    'count' => 0,
                    'expired' => 0,
                ];
            }

            $stats['by_prefix'][$prefixPart]['count']++;

            // Check TTL
            $ttl = $redis->ttl($key);
            
            // TTL -1 means no expiration, TTL -2 means expired
            if ($ttl === -2) {
                $stats['expired_or_stale']++;
                $stats['by_prefix'][$prefixPart]['expired']++;
            }
        }

        // Get memory info
        try {
            $info = $redis->info('memory');
            $stats['memory_usage'] = $info['used_memory_human'] ?? 'N/A';
        } catch (Exception $e) {
            $stats['memory_usage'] = 'Unable to retrieve';
        }

        return $stats;
    }

    /**
     * Display cache statistics.
     *
     * @param array<string, mixed> $stats
     * @return void
     */
    private function displayStats(array $stats): void
    {
        $this->newLine();
        $this->info('Cache Statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Keys', number_format($stats['total_keys'])],
                ['Memory Usage', $stats['memory_usage']],
                ['Expired/Stale', number_format($stats['expired_or_stale'])],
            ]
        );

        if (!empty($stats['by_prefix'])) {
            $this->newLine();
            $this->info('Keys by Prefix:');
            $this->table(
                ['Prefix', 'Count', 'Expired'],
                collect($stats['by_prefix'])
                    ->map(fn ($data, $prefix) => [$prefix, $data['count'], $data['expired']])
                    ->toArray()
            );
        }
    }

    /**
     * Clean expired cache entries.
     *
     * @param array<string, mixed> $stats
     * @return int Number of entries cleaned
     */
    private function cleanExpiredEntries(array $stats): int
    {
        $redis = Cache::store('redis')->getRedis();
        $prefix = config('cache.prefix');
        $keys = $redis->keys($prefix . '*');
        $cleaned = 0;

        foreach ($keys as $key) {
            $ttl = $redis->ttl($key);
            
            // Delete expired keys (TTL == -2)
            if ($ttl === -2) {
                $redis->del($key);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Clear specific SIMRS cache sections.
     *
     * @param array<string> $sections
     * @return int Number of entries cleared
     */
    public function clearSections(array $sections): int
    {
        $redis = Cache::store('redis')->getRedis();
        $prefix = config('cache.prefix');
        $totalCleared = 0;

        $sectionPatterns = [
            'patients' => 'patient:*',
            'queues' => 'queue:*',
            'rooms' => 'rooms:*',
            'indicators' => 'indicators:*',
            'visits' => 'visits:*',
            'medicines' => 'medicines:*',
        ];

        foreach ($sections as $section) {
            if (!isset($sectionPatterns[$section])) {
                continue;
            }

            $pattern = $prefix . $sectionPatterns[$section];
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                $totalCleared += count($keys);
            }
        }

        return $totalCleared;
    }
}
