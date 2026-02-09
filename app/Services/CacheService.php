<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Service
 * 
 * Centralized caching service for SIMRS application.
 * Provides methods for caching frequently accessed data
 * with Redis as the primary cache driver.
 */
class CacheService
{
    /**
     * Default cache TTL in seconds (1 hour)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Short cache TTL in seconds (5 minutes)
     */
    private const SHORT_TTL = 300;

    /**
     * Long cache TTL in seconds (24 hours)
     */
    private const LONG_TTL = 86400;

    /**
     * Cache key prefixes
     */
    private const PREFIX_PATIENT = 'patient';
    private const PREFIX_QUEUE_STATS = 'queue:stats';
    private const PREFIX_ROOM_OCCUPANCY = 'rooms:occupancy';
    private const PREFIX_INDICATORS = 'indicators';
    private const PREFIX_VISIT_COUNT = 'visits:count';
    private const PREFIX_TOP_MEDICINES = 'medicines:top';
    private const PREFIX_ACTIVE_PATIENTS = 'patients:active';

    // ==================== PATIENT CACHE ====================

    /**
     * Get a patient from cache or database.
     *
     * @param int $id Patient ID
     * @return Patient|null
     */
    public static function getPatient(int $id): ?Patient
    {
        // Cache only the patient ID to avoid serializing PDO instances
        $patientId = Cache::remember(
            self::cacheKey(self::PREFIX_PATIENT, $id),
            self::DEFAULT_TTL,
            fn () => Patient::find($id)?->id
        );

        return $patientId ? Patient::with(['visits' => fn ($q) => $q->latest()->limit(5)])->find($patientId) : null;
    }

    /**
     * Store a patient in cache.
     *
     * @param Patient $patient
     * @param int|null $ttl Time to live in seconds
     * @return void
     */
    public static function putPatient(Patient $patient, ?int $ttl = null): void
    {
        // Cache only the patient ID to avoid serializing PDO instances
        Cache::put(
            self::cacheKey(self::PREFIX_PATIENT, $patient->id),
            $patient->id,
            $ttl ?? self::DEFAULT_TTL
        );
    }

    /**
     * Remove a patient from cache.
     *
     * @param int $id Patient ID
     * @return void
     */
    public static function forgetPatient(int $id): void
    {
        Cache::forget(self::cacheKey(self::PREFIX_PATIENT, $id));
    }

    // ==================== QUEUE CACHE ====================

    /**
     * Get queue statistics for a polyclinic.
     *
     * @param int $polyclinicId
     * @return array<string, mixed>
     */
    public static function getQueueStats(int $polyclinicId): array
    {
        return Cache::remember(
            self::cacheKey(self::PREFIX_QUEUE_STATS, $polyclinicId),
            self::SHORT_TTL,
            function () use ($polyclinicId) {
                    $today = now()->toDateString();
                    
                    $stats = VisitQueue::where('polyclinic_id', $polyclinicId)
                        ->whereDate('created_at', $today)
                        ->selectRaw('status, COUNT(*) as count')
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray();

                    return [
                        'polyclinic_id' => $polyclinicId,
                        'date' => $today,
                        'waiting' => $stats['waiting'] ?? 0,
                        'called' => $stats['called'] ?? 0,
                        'completed' => $stats['completed'] ?? 0,
                        'cancelled' => $stats['cancelled'] ?? 0,
                        'total' => array_sum($stats),
                        'avg_wait_time' => self::calculateAvgWaitTime($polyclinicId),
                    ];
                }
            );
    }

    /**
     * Store queue statistics in cache.
     *
     * @param int $polyclinicId
     * @param array<string, mixed> $stats
     * @return void
     */
    public static function putQueueStats(int $polyclinicId, array $stats): void
    {
        Cache::put(
            self::cacheKey(self::PREFIX_QUEUE_STATS, $polyclinicId),
            $stats,
            self::SHORT_TTL
        );
    }

    /**
     * Clear queue stats cache for a polyclinic.
     *
     * @param int $polyclinicId
     * @return void
     */
    public static function forgetQueueStats(int $polyclinicId): void
    {
        Cache::forget(
            self::cacheKey(self::PREFIX_QUEUE_STATS, $polyclinicId)
        );
    }

    // ==================== ROOM OCCUPANCY CACHE ====================

    /**
     * Get room occupancy data.
     *
     * @return array<string, mixed>
     */
    public static function getRoomOccupancy(): array
    {
        return Cache::remember(
            self::PREFIX_ROOM_OCCUPANCY,
            self::SHORT_TTL,
            function () {
                    $rooms = Room::active()
                        ->with(['beds' => fn ($q) => $q->select('id', 'room_id', 'bed_number', 'status', 'current_visit_id')])
                        ->get();

                    $totalBeds = 0;
                    $occupiedBeds = 0;
                    $roomData = [];

                    foreach ($rooms as $room) {
                        $roomBeds = $room->beds->count();
                        $roomOccupied = $room->beds->where('status', 'terisi')->count();
                        
                        $totalBeds += $roomBeds;
                        $occupiedBeds += $roomOccupied;

                        $roomData[] = [
                            'id' => $room->id,
                            'name' => $room->name,
                            'class' => $room->room_class,
                            'total_beds' => $roomBeds,
                            'occupied' => $roomOccupied,
                            'available' => $roomBeds - $roomOccupied,
                            'occupancy_rate' => $roomBeds > 0 
                                ? round(($roomOccupied / $roomBeds) * 100, 2) 
                                : 0.0,
                        ];
                    }

                    return [
                        'timestamp' => now()->toIso8601String(),
                        'total_rooms' => $rooms->count(),
                        'total_beds' => $totalBeds,
                        'occupied_beds' => $occupiedBeds,
                        'available_beds' => $totalBeds - $occupiedBeds,
                        'occupancy_rate' => $totalBeds > 0 
                            ? round(($occupiedBeds / $totalBeds) * 100, 2) 
                            : 0.0,
                        'rooms' => $roomData,
                    ];
                }
            );
    }

    /**
     * Store room occupancy data in cache.
     *
     * @param array<string, mixed> $data
     * @return void
     */
    public static function putRoomOccupancy(array $data): void
    {
        Cache::put(
            self::PREFIX_ROOM_OCCUPANCY,
            $data,
            self::SHORT_TTL
        );
    }

    /**
     * Clear room occupancy cache.
     *
     * @return void
     */
    public static function forgetRoomOccupancy(): void
    {
        Cache::forget(self::PREFIX_ROOM_OCCUPANCY);
    }

    // ==================== INDICATORS CACHE ====================

    /**
     * Get cached indicators for a date.
     *
     * @param string $date Date in Y-m-d format
     * @return array<string, mixed>|null
     */
    public static function getIndicators(string $date): ?array
    {
        return Cache::get(self::cacheKey(self::PREFIX_INDICATORS, $date));
    }

    /**
     * Store indicators in cache.
     *
     * @param string $date Date in Y-m-d format
     * @param array<string, mixed> $data
     * @return void
     */
    public static function putIndicators(string $date, array $data): void
    {
        Cache::put(
            self::cacheKey(self::PREFIX_INDICATORS, $date),
            $data,
            self::LONG_TTL
        );
    }

    // ==================== VISIT COUNT CACHE ====================

    /**
     * Get today's visit count.
     *
     * @return int
     */
    public static function getTodayVisitCount(): int
    {
        $cacheKey = self::PREFIX_VISIT_COUNT . ':' . now()->toDateString();

        return Cache::remember(
            $cacheKey,
            self::SHORT_TTL,
            fn () => Visit::today()->count()
        );
    }

    /**
     * Get visit counts by type.
     *
     * @return array<string, int>
     */
    public static function getTodayVisitCountsByType(): array
    {
        $cacheKey = self::PREFIX_VISIT_COUNT . ':by_type:' . now()->toDateString();

        return Cache::remember(
            $cacheKey,
            self::SHORT_TTL,
            function () {
                $counts = Visit::today()
                    ->selectRaw('visit_type, COUNT(*) as count')
                    ->groupBy('visit_type')
                    ->pluck('count', 'visit_type')
                    ->toArray();

                return [
                    'rawat_jalan' => $counts['rawat_jalan'] ?? 0,
                    'rawat_inap' => $counts['rawat_inap'] ?? 0,
                    'igd' => $counts['igd'] ?? 0,
                    'mcu' => $counts['mcu'] ?? 0,
                    'total' => array_sum($counts),
                ];
            }
        );
    }

    // ==================== TOP MEDICINES CACHE ====================

    /**
     * Get top prescribed medicines.
     *
     * @param int $limit
     * @return Collection<int, Medicine>
     */
    public static function getTopMedicines(int $limit = 10): Collection
    {
        // Cache only the medicine IDs to avoid serializing PDO instances
        $medicineIds = Cache::remember(
            self::PREFIX_TOP_MEDICINES . ':' . $limit,
            self::DEFAULT_TTL,
            fn () => Medicine::active()
                ->where('prescription_count', '>', 0)
                ->orderByDesc('prescription_count')
                ->limit($limit)
                ->pluck('id')
                ->toArray()
        );

        if (empty($medicineIds)) {
            return new Collection();
        }

        // Re-fetch models from database to avoid cached PDO connections
        return Medicine::whereIn('id', $medicineIds)
            ->orderByDesc('prescription_count')
            ->get(['id', 'code', 'name', 'stock', 'prescription_count']);
    }

    // ==================== ACTIVE PATIENTS CACHE ====================

    /**
     * Get active patients (currently in hospital).
     *
     * @return Collection<int, Patient>
     */
    public static function getActivePatients(): Collection
    {
        // Cache only the patient IDs to avoid serializing PDO instances
        $patientIds = Cache::remember(
            self::PREFIX_ACTIVE_PATIENTS,
            self::SHORT_TTL,
            function () {
                return Visit::where('is_completed', false)
                    ->pluck('patient_id')
                    ->toArray();
            }
        );

        if (empty($patientIds)) {
            return new Collection();
        }

        // Re-fetch models from database to avoid cached PDO connections
        return Patient::whereIn('id', $patientIds)
            ->with(['visits' => fn ($q) => $q->where('is_completed', false)])
            ->get();
    }

    // ==================== CACHE MANAGEMENT ====================

    /**
     * Flush all SIMRS cache.
     *
     * @return void
     */
    public static function flush(): void
    {
        // Flush specific cache keys
        $patterns = [
            self::PREFIX_PATIENT . '*',
            self::PREFIX_QUEUE_STATS . '*',
            self::PREFIX_ROOM_OCCUPANCY . '*',
            self::PREFIX_INDICATORS . '*',
            self::PREFIX_VISIT_COUNT . '*',
            self::PREFIX_TOP_MEDICINES . '*',
            self::PREFIX_ACTIVE_PATIENTS . '*',
        ];

        foreach ($patterns as $pattern) {
            self::flushPattern($pattern);
        }

        Log::info('SIMRS cache flushed');
    }

    /**
     * Flush cache by pattern.
     *
     * @param string $pattern
     * @return void
     */
    public static function flushPattern(string $pattern): void
    {
        try {
            $store = Cache::getStore();
            if (! method_exists($store, 'getRedis')) {
                // Non-Redis stores do not support key pattern deletion.
                return;
            }

            $redis = Cache::getRedis();
            $keys = $redis->keys(config('cache.prefix') . $pattern);

            if (!empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to flush cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public static function getStats(): array
    {
        try {
            $store = Cache::getStore();
            if (! method_exists($store, 'getRedis')) {
                return [
                    'driver' => 'redis',
                    'memory_used' => 'N/A',
                    'memory_peak' => 'N/A',
                    'connected_clients' => 0,
                    'cached_keys' => 0,
                ];
            }

            $redis = Cache::getRedis();
            $info = $redis->info('memory');

            return [
                'driver' => 'redis',
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'cached_keys' => count($redis->keys(config('cache.prefix') . '*')),
            ];
        } catch (\Throwable $e) {
            return [
                'driver' => 'redis',
                'error' => $e->getMessage(),
            ];
        }
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Generate cache key.
     *
     * @param string $prefix
     * @param string|int $identifier
     * @return string
     */
    private static function cacheKey(string $prefix, string|int $identifier): string
    {
        return "{$prefix}:{$identifier}";
    }

    /**
     * Calculate average wait time for a polyclinic.
     *
     * @param int $polyclinicId
     * @return int Average wait time in minutes
     */
    private static function calculateAvgWaitTime(int $polyclinicId): int
    {
        $queues = VisitQueue::where('polyclinic_id', $polyclinicId)
            ->whereDate('created_at', now()->toDateString())
            ->whereNotNull('called_at')
            ->whereNotNull('created_at')
            ->get(['created_at', 'called_at']);

        if ($queues->isEmpty()) {
            return 0;
        }

        $totalMinutes = 0.0;
        foreach ($queues as $queue) {
            $calledAt = $queue->called_at;
            $createdAt = $queue->created_at;
            if ($calledAt && $createdAt) {
                $totalMinutes += $createdAt->diffInMinutes($calledAt);
            }
        }

        return (int) round($totalMinutes / max(1, $queues->count()));
    }
}
