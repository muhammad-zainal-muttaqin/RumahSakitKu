<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VisitMetricsService
{
    private const CACHE_TTL_SECONDS = 120;

    /**
     * @var array<string, string>
     */
    private const STATUS_ALIAS_TO_LEGACY = [
        'registered' => 'registered',
        'pendaftaran' => 'registered',
        'waiting' => 'waiting',
        'menunggu' => 'waiting',
        'in_progress' => 'in_progress',
        'proses' => 'in_progress',
        'completed' => 'completed',
        'selesai' => 'completed',
        'cancelled' => 'cancelled',
        'batal' => 'cancelled',
    ];

    /**
     * @var array<string, string>
     */
    private const LEGACY_TO_VISIT_STATUS = [
        'registered' => 'pendaftaran',
        'waiting' => 'menunggu',
        'in_progress' => 'proses',
        'completed' => 'selesai',
        'cancelled' => 'batal',
    ];

    /**
     * @return array<string, int>
     */
    public function getTodayStatusCounts(): array
    {
        $today = now()->toDateString();
        $cacheKey = "visits:metrics:today-status:{$today}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function (): array {
            $date = now()->startOfDay();
            $rawCounts = $this->queryStatusCounts($date);
            $normalized = $this->normalizeLegacyStatusCounts($rawCounts);
            $normalized['total'] = array_sum($rawCounts);

            return $normalized;
        });
    }

    /**
     * @return array<string, int>
     */
    public function getTabBadgeCounts(): array
    {
        $today = now()->toDateString();
        $cacheKey = "visits:metrics:tab-badges:{$today}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function (): array {
            $allRawStatusCounts = $this->queryStatusCounts();
            $allStatusCounts = $this->normalizeLegacyStatusCounts($allRawStatusCounts);
            $todayStatusCounts = $this->getTodayStatusCounts();
            $visitTypeCounts = $this->queryVisitTypeCounts();

            return [
                'all' => array_sum($allRawStatusCounts),
                'today' => $todayStatusCounts['total'] ?? 0,
                'registered' => $allStatusCounts['registered'] ?? 0,
                'waiting' => $allStatusCounts['waiting'] ?? 0,
                'in_progress' => $allStatusCounts['in_progress'] ?? 0,
                'completed' => $allStatusCounts['completed'] ?? 0,
                'cancelled' => $allStatusCounts['cancelled'] ?? 0,
                'rawat_jalan' => $visitTypeCounts['rawat_jalan'] ?? 0,
                'igd' => $visitTypeCounts['igd'] ?? 0,
            ];
        });
    }

    public static function legacyStatusToVisitStatus(string $status): string
    {
        return self::LEGACY_TO_VISIT_STATUS[$status] ?? $status;
    }

    /**
     * @return array<string, int>
     */
    private function queryStatusCounts(?Carbon $date = null): array
    {
        $query = Visit::query();

        if ($date) {
            $start = $date->copy()->startOfDay();
            $endExclusive = $start->copy()->addDay();

            $query
                ->where('registration_date', '>=', $start)
                ->where('registration_date', '<', $endExclusive);
        }

        return $query
            ->select('visit_status', DB::raw('COUNT(*) as total'))
            ->groupBy('visit_status')
            ->pluck('total', 'visit_status')
            ->map(fn ($count): int => (int) $count)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function queryVisitTypeCounts(): array
    {
        return Visit::query()
            ->select('visit_type', DB::raw('COUNT(*) as total'))
            ->groupBy('visit_type')
            ->pluck('total', 'visit_type')
            ->map(fn ($count): int => (int) $count)
            ->toArray();
    }

    /**
     * @param array<string, int> $rawCounts
     * @return array<string, int>
     */
    private function normalizeLegacyStatusCounts(array $rawCounts): array
    {
        $normalized = [
            'registered' => 0,
            'waiting' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($rawCounts as $status => $count) {
            $legacyStatus = self::STATUS_ALIAS_TO_LEGACY[(string) $status] ?? null;
            if ($legacyStatus === null) {
                continue;
            }

            $normalized[$legacyStatus] += (int) $count;
        }

        return $normalized;
    }
}
