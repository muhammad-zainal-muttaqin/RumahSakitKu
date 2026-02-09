<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\Prescription;
use App\Models\Clinical\RadiologyOrder;
use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Medicine;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Report Service
 * 
 * Generates hospital statistics and analytics reports.
 * Calculates key performance indicators (KPIs) for hospital management.
 * 
 * Includes:
 * - BOR (Bed Occupancy Rate)
 * - LOS (Length of Stay)
 * - TOI (Turn Over Interval)
 * - BTO (Bed Turn Over)
 * - GDR (Gross Death Rate)
 * - NDR (Net Death Rate)
 * 
 * All heavy calculations are cached for performance.
 */

class ReportService
{
    /**
     * Cache TTL for report calculations in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Cache wrapper that bypasses persistent cache during tests.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    private function remember(string $key, int $ttl, callable $callback)
    {
        if (app()->environment('testing')) {
            return $callback();
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Apply an inclusive date-only range filter to avoid timezone/time precision boundary issues.
     */
    private function applyInclusiveDateRange($query, string $column, Carbon $startDate, Carbon $endDate)
    {
        return $query
            ->whereDate($column, '>=', $startDate->toDateString())
            ->whereDate($column, '<=', $endDate->toDateString());
    }

    private function getTotalBeds(?int $roomId = null): int
    {
        $bedQuery = Bed::active();
        $roomQuery = Room::active();

        if ($roomId) {
            $bedQuery->where('room_id', $roomId);
            $roomQuery->where('id', $roomId);
        }

        $bedCount = (int) $bedQuery->count();
        $roomConfiguredBeds = (int) $roomQuery->sum('total_beds');

        return max($bedCount, $roomConfiguredBeds);
    }

    /**
     * Generate cache key for date range.
     */
    private function getCacheKey(string $prefix, Carbon $startDate, Carbon $endDate, ?int $roomId = null): string
    {
        $key = "{$prefix}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        if ($roomId) {
            $key .= ":room_{$roomId}";
        }
        return $key;
    }

    /**
     * Calculate Bed Occupancy Rate (BOR)
     * Formula: (Total days of care / (Number of beds x Period days)) x 100%
     */
    public function calculateBOR(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $cacheKey = $this->getCacheKey('bor', $startDate, $endDate, $roomId);
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $roomId) {
            return $this->performBORCalculation($startDate, $endDate, $roomId);
        });
    }

    /**
     * Perform actual BOR calculation.
     */
    private function performBORCalculation(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        
        $totalBeds = $this->getTotalBeds($roomId);
        
        if ($totalBeds === 0) {
            return 0.0;
        }

        $totalBedDaysAvailable = $totalBeds * $daysInPeriod;
        
        // Calculate actual days of care
        $totalDaysOfCare = Visit::where('visit_type', 'rawat_inap')
            ->whereNotNull('admission_date')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('admission_date', [$startDate, $endDate])
                    ->orWhereBetween('discharge_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('admission_date', '<=', $startDate)
                            ->where(function ($sq) use ($endDate) {
                                $sq->whereNull('discharge_date')
                                    ->orWhere('discharge_date', '>=', $endDate);
                            });
                    });
            })
            ->when($roomId, function ($query) use ($roomId) {
                return $query->where('room_id', $roomId);
            })
            ->get()
            ->sum(function ($visit) use ($startDate, $endDate) {
                $admission = Carbon::parse($visit->admission_date)->max($startDate);
                $discharge = $visit->discharge_date 
                    ? Carbon::parse($visit->discharge_date)->min($endDate) 
                    : $endDate;
                
                return $admission->diffInDays($discharge) + 1;
            });

        return round(($totalDaysOfCare / $totalBedDaysAvailable) * 100, 2);
    }

    /**
     * Calculate Average Length of Stay (LOS)
     * Formula: Total days of care / Number of discharged patients
     */
    public function calculateLOS(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $cacheKey = $this->getCacheKey('los', $startDate, $endDate, $roomId);
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $roomId) {
            return $this->performLOSCalculation($startDate, $endDate, $roomId);
        });
    }

    /**
     * Perform actual LOS calculation.
     */
    private function performLOSCalculation(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $dischargedPatients = Visit::where('visit_type', 'rawat_inap')
            ->whereNotNull('discharge_date')
            ->whereBetween('discharge_date', [$startDate, $endDate])
            ->when($roomId, function ($query) use ($roomId) {
                return $query->where('room_id', $roomId);
            })
            ->get();

        if ($dischargedPatients->isEmpty()) {
            return 0.0;
        }

        $totalDaysOfCare = $dischargedPatients->sum(function ($visit) {
            return Carbon::parse($visit->admission_date)->diffInDays($visit->discharge_date);
        });

        return round($totalDaysOfCare / $dischargedPatients->count(), 2);
    }

    /**
     * Calculate Turn Over Interval (TOI)
     * Formula: (Available bed days - Days of care) / Number of discharged patients
     */
    public function calculateTOI(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $cacheKey = $this->getCacheKey('toi', $startDate, $endDate, $roomId);
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $roomId) {
            return $this->performTOICalculation($startDate, $endDate, $roomId);
        });
    }

    /**
     * Perform actual TOI calculation.
     */
    private function performTOICalculation(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        
        $totalBeds = $this->getTotalBeds($roomId);
        
        $dischargedCount = Visit::where('visit_type', 'rawat_inap')
            ->whereNotNull('discharge_date')
            ->whereBetween('discharge_date', [$startDate, $endDate])
            ->when($roomId, function ($query) use ($roomId) {
                return $query->where('room_id', $roomId);
            })
            ->count();

        if ($dischargedCount === 0) {
            return 0.0;
        }

        $bor = $this->calculateBOR($startDate, $endDate, $roomId);
        $availableBedDays = ($totalBeds * $daysInPeriod) * ((100 - $bor) / 100);

        return round($availableBedDays / $dischargedCount, 2);
    }

    /**
     * Calculate Bed Turn Over (BTO)
     * Formula: Number of discharged patients / Number of beds
     */
    public function calculateBTO(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $cacheKey = $this->getCacheKey('bto', $startDate, $endDate, $roomId);
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $roomId) {
            return $this->performBTOCalculation($startDate, $endDate, $roomId);
        });
    }

    /**
     * Perform actual BTO calculation.
     */
    private function performBTOCalculation(Carbon $startDate, Carbon $endDate, ?int $roomId = null): float
    {
        $totalBeds = $this->getTotalBeds($roomId);

        if ($totalBeds === 0) {
            return 0.0;
        }

        $dischargedCount = Visit::where('visit_type', 'rawat_inap')
            ->whereNotNull('discharge_date')
            ->whereBetween('discharge_date', [$startDate, $endDate])
            ->when($roomId, function ($query) use ($roomId) {
                return $query->where('room_id', $roomId);
            })
            ->count();

        return round($dischargedCount / $totalBeds, 2);
    }

    /**
     * Calculate Gross Death Rate (GDR)
     * Formula: (Total deaths / Total discharged patients) x 100%
     */
    public function calculateGDR(Carbon $startDate, Carbon $endDate, ?string $visitType = null): float
    {
        $typeSuffix = $visitType ?? 'all';
        $cacheKey = "gdr:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$typeSuffix}";
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $visitType) {
            return $this->performGDRCalculation($startDate, $endDate, $visitType);
        });
    }

    /**
     * Perform actual GDR calculation.
     */
    private function performGDRCalculation(Carbon $startDate, Carbon $endDate, ?string $visitType = null): float
    {
        $query = Visit::whereNotNull('discharge_date');
        $this->applyInclusiveDateRange($query, 'discharge_date', $startDate, $endDate);

        if ($visitType) {
            $query->where('visit_type', $visitType);
        }

        $totalDischarged = (clone $query)->count();

        if ($totalDischarged === 0) {
            return 0.0;
        }

        $totalDeaths = (clone $query)
            ->where('discharge_status', 'meninggal')
            ->count();

        return round(($totalDeaths / $totalDischarged) * 100, 2);
    }

    /**
     * Calculate Net Death Rate (NDR)
     * Formula: (Deaths > 48 hours / (Total discharged - deaths <= 48 hours)) x 100%
     */
    public function calculateNDR(Carbon $startDate, Carbon $endDate, ?string $visitType = null): float
    {
        $typeSuffix = $visitType ?? 'all';
        $cacheKey = "ndr:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$typeSuffix}";
        
        return $this->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $visitType) {
            return $this->performNDRCalculation($startDate, $endDate, $visitType);
        });
    }

    /**
     * Perform actual NDR calculation.
     */
    private function performNDRCalculation(Carbon $startDate, Carbon $endDate, ?string $visitType = null): float
    {
        $query = Visit::where('visit_type', 'rawat_inap')
            ->whereNotNull('discharge_date');
        $this->applyInclusiveDateRange($query, 'discharge_date', $startDate, $endDate);

        // Get all deaths
        $deaths = (clone $query)
            ->where('discharge_status', 'meninggal')
            ->get();

        $deathsOver48h = $deaths->filter(function ($visit) {
            $los = Carbon::parse($visit->admission_date)->diffInHours($visit->discharge_date);
            return $los > 48;
        })->count();

        $deathsUnder48h = $deaths->filter(function ($visit) {
            $los = Carbon::parse($visit->admission_date)->diffInHours($visit->discharge_date);
            return $los <= 48;
        })->count();

        $totalDischarged = (clone $query)->count();
        $denominator = $totalDischarged - $deathsUnder48h;

        if ($denominator === 0) {
            return 0.0;
        }

        return round(($deathsOver48h / $denominator) * 100, 2);
    }

    /**
     * Clear all report cache.
     */
    public function clearCache(): void
    {
        // Safe fallback for non-Redis test environments.
        Cache::flush();
    }

    /**
     * Get visit counts by type and date range (cached).
     */
    public function getVisitCountsByType(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "visits:counts:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return $this->remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            return $this->performVisitCountsByType($startDate, $endDate);
        });
    }

    /**
     * Perform visit counts calculation.
     */
    private function performVisitCountsByType(Carbon $startDate, Carbon $endDate): array
    {
        $counts = Visit::whereBetween('registration_date', [$startDate, $endDate])
            ->select('visit_type', DB::raw('COUNT(*) as count'))
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

    /**
     * Get daily visit trend for chart
     */
    public function getDailyVisitTrend(Carbon $startDate, Carbon $endDate): Collection
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $data = collect();

        foreach ($period as $date) {
            $counts = Visit::whereDate('registration_date', $date)
                ->select('visit_type', DB::raw('COUNT(*) as count'))
                ->groupBy('visit_type')
                ->pluck('count', 'visit_type');

            $data->push([
                'date' => $date->format('Y-m-d'),
                'rawat_jalan' => $counts['rawat_jalan'] ?? 0,
                'rawat_inap' => $counts['rawat_inap'] ?? 0,
                'igd' => $counts['igd'] ?? 0,
                'mcu' => $counts['mcu'] ?? 0,
            ]);
        }

        return $data;
    }

    /**
     * Get patient distribution by polyclinic
     */
    public function getPatientDistributionByPolyclinic(Carbon $startDate, Carbon $endDate): Collection
    {
        return Visit::whereBetween('registration_date', [$startDate, $endDate])
            ->whereNotNull('polyclinic_id')
            ->with('polyclinic')
            ->select('polyclinic_id', DB::raw('COUNT(*) as count'))
            ->groupBy('polyclinic_id')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'polyclinic' => $item->polyclinic?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get revenue by payment method
     */
    public function getRevenueByPaymentMethod(Carbon $startDate, Carbon $endDate): array
    {
        $revenue = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('is_refunded', false)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        return [
            'cash' => $revenue['cash'] ?? 0,
            'card' => ($revenue['credit_card'] ?? 0) + ($revenue['debit_card'] ?? 0),
            'transfer' => $revenue['bank_transfer'] ?? 0,
            'bpjs' => $revenue['bpjs'] ?? 0,
            'insurance' => $revenue['insurance'] ?? 0,
            'mobile_payment' => $revenue['mobile_payment'] ?? 0,
            'total' => array_sum($revenue),
        ];
    }

    /**
     * Get daily revenue trend
     */
    public function getDailyRevenueTrend(Carbon $startDate, Carbon $endDate): Collection
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $data = collect();

        foreach ($period as $date) {
            $payments = Payment::whereDate('payment_date', $date)
                ->where('is_refunded', false)
                ->select('payment_method', DB::raw('SUM(amount) as total'))
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method');

            $data->push([
                'date' => $date->format('Y-m-d'),
                'cash' => $payments['cash'] ?? 0,
                'bpjs' => $payments['bpjs'] ?? 0,
                'insurance' => $payments['insurance'] ?? 0,
                'other' => ($payments['credit_card'] ?? 0) + ($payments['debit_card'] ?? 0) + ($payments['bank_transfer'] ?? 0) + ($payments['mobile_payment'] ?? 0),
            ]);
        }

        return $data;
    }

    /**
     * Get room occupancy data by class
     */
    public function getRoomOccupancyByClass(): Collection
    {
        return Room::active()
            ->select('room_class')
            ->selectRaw('SUM(total_beds) as total_beds')
            ->selectRaw('SUM(available_beds) as available_beds')
            ->groupBy('room_class')
            ->get()
            ->map(function ($room) {
                $occupied = $room->total_beds - $room->available_beds;
                $occupancyRate = $room->total_beds > 0 
                    ? round(($occupied / $room->total_beds) * 100, 2) 
                    : 0;

                return [
                    'class' => $room->room_class,
                    'total_beds' => $room->total_beds,
                    'available_beds' => $room->available_beds,
                    'occupied_beds' => $occupied,
                    'occupancy_rate' => $occupancyRate,
                ];
            });
    }

    /**
     * Get top ICD10 diseases
     */
    public function getTopDiseases(Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return Assessment::whereBetween('assessed_at', [$startDate, $endDate])
            ->whereNotNull('primary_diagnosis_code')
            ->select('primary_diagnosis_code', 'primary_diagnosis_name', DB::raw('COUNT(*) as count'))
            ->groupBy('primary_diagnosis_code', 'primary_diagnosis_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'code' => $item->primary_diagnosis_code,
                    'name' => $item->primary_diagnosis_name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get recent visits
     */
    public function getRecentVisits(int $limit = 10): Collection
    {
        return Visit::with(['patient', 'polyclinic', 'doctor'])
            ->orderByDesc('registration_date')
            ->limit($limit)
            ->get()
            ->map(function ($visit) {
                return [
                    'visit_number' => $visit->visit_number,
                    'patient_name' => $visit->patient?->name ?? 'Unknown',
                    'patient_medical_record' => $visit->patient?->medical_record_number ?? '-',
                    'visit_type' => $visit->visit_type,
                    'polyclinic' => $visit->polyclinic?->name ?? '-',
                    'doctor' => $visit->doctor?->name ?? '-',
                    'visit_date' => $visit->registration_date,
                    'status' => $visit->visit_status,
                ];
            });
    }

    /**
     * Get low stock medicines
     */
    public function getLowStockMedicines(int $limit = 10): Collection
    {
        return Medicine::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit($limit)
            ->get(['code', 'name', 'stock', 'min_stock', 'unit']);
    }

    /**
     * Get expiring BPJS patients (Jatuh Tempo)
     */
    public function getExpiringBpjsPatients(int $days = 30, int $limit = 10): Collection
    {
        // Note: This assumes there's a bpjs_expiry_date field or similar
        // Adjust based on actual BPJS data structure
        return collect(); // Placeholder - implement based on actual BPJS model
    }

    /**
     * Get long stay patients (LOS > threshold days)
     */
    public function getLongStayPatients(int $threshold = 7): Collection
    {
        return Visit::where('visit_type', 'rawat_inap')
            ->whereNull('discharge_date')
            ->where('admission_date', '<=', now()->subDays($threshold))
            ->with(['patient', 'room', 'bed'])
            ->get()
            ->map(function ($visit) use ($threshold) {
                $los = Carbon::parse($visit->admission_date)->diffInDays(now());
                
                return [
                    'visit_number' => $visit->visit_number,
                    'patient_name' => $visit->patient?->name ?? 'Unknown',
                    'medical_record_number' => $visit->patient?->medical_record_number ?? '-',
                    'room' => $visit->room?->name ?? '-',
                    'bed' => $visit->bed?->bed_number ?? '-',
                    'admission_date' => $visit->admission_date,
                    'los_days' => $los,
                    'threshold' => $threshold,
                ];
            });
    }

    /**
     * Get employee statistics for RL 2
     */
    public function getEmployeeStatistics(): array
    {
        $doctors = Employee::where('is_doctor', true)->where('status', 'aktif')->count();
        $specialists = Employee::where('is_doctor', true)
            ->where('status', 'aktif')
            ->whereNotNull('specialist_polyclinic_id')
            ->count();
        $gpDoctors = $doctors - $specialists;
        
        $nurses = Employee::where('is_nurse', true)->where('status', 'aktif')->count();
        $pharmacists = Employee::where('profession', 'like', '%farmasi%')->where('status', 'aktif')->count();
        $midwives = Employee::where('profession', 'like', '%bidan%')->where('status', 'aktif')->count();
        
        $totalEmployees = Employee::where('status', 'aktif')->count();

        return [
            'doctors' => [
                'total' => $doctors,
                'specialists' => $specialists,
                'general_practitioners' => $gpDoctors,
            ],
            'nurses' => $nurses,
            'pharmacists' => $pharmacists,
            'midwives' => $midwives,
            'total_employees' => $totalEmployees,
        ];
    }

    /**
     * Get hospital bed statistics
     */
    public function getHospitalBedStatistics(): array
    {
        $totalBeds = Bed::active()->count();

        if ($totalBeds > 0) {
            $occupiedBeds = Bed::active()->occupied()->count();
            $availableBeds = $totalBeds - $occupiedBeds;
        } else {
            $totalBeds = (int) Room::active()->sum('total_beds');
            $availableBeds = (int) Room::active()->sum('available_beds');
            $occupiedBeds = max(0, $totalBeds - $availableBeds);
        }
        
        $classDistribution = Room::active()
            ->select('room_class', DB::raw('SUM(total_beds) as beds'))
            ->groupBy('room_class')
            ->pluck('beds', 'room_class')
            ->toArray();

        return [
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => $availableBeds,
            'occupancy_rate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0,
            'class_distribution' => $classDistribution,
        ];
    }

    /**
     * Get service statistics for RL 3
     */
    public function getServiceStatistics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'rawat_inap' => [
                'admissions' => Visit::where('visit_type', 'rawat_inap')
                    ->whereBetween('admission_date', [$startDate, $endDate])
                    ->count(),
                'discharges' => Visit::where('visit_type', 'rawat_inap')
                    ->whereNotNull('discharge_date')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->count(),
                'deaths' => Visit::where('visit_type', 'rawat_inap')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->count(),
            ],
            'rawat_jalan' => [
                'visits' => Visit::where('visit_type', 'rawat_jalan')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->count(),
                'new_patients' => Visit::where('visit_type', 'rawat_jalan')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->whereDoesntHave('patient.visits', function ($q) use ($startDate) {
                        $q->where('registration_date', '<', $startDate);
                    })
                    ->count(),
                'return_patients' => Visit::where('visit_type', 'rawat_jalan')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->whereHas('patient.visits', function ($q) use ($startDate) {
                        $q->where('registration_date', '<', $startDate);
                    })
                    ->count(),
            ],
            'igd' => [
                'visits' => Visit::where('visit_type', 'igd')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->count(),
                'deaths' => Visit::where('visit_type', 'igd')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->count(),
            ],
            'laboratory' => [
                'orders' => LaboratoryOrder::whereBetween('order_date', [$startDate, $endDate])->count(),
                'cito_orders' => LaboratoryOrder::whereBetween('order_date', [$startDate, $endDate])
                    ->where('is_cito', true)
                    ->count(),
            ],
            'radiology' => [
                'orders' => RadiologyOrder::whereBetween('order_date', [$startDate, $endDate])->count(),
            ],
            'pharmacy' => [
                'prescriptions' => Prescription::whereBetween('prescription_date', [$startDate, $endDate])->count(),
            ],
        ];
    }

    /**
     * Get morbidity statistics (RL 4)
     */
    public function getMorbidityStatistics(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->getTopDiseases($startDate, $endDate, 10);
    }

    /**
     * Get mortality statistics (RL 5)
     */
    public function getMortalityStatistics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'rawat_inap' => [
                'total_deaths' => Visit::where('visit_type', 'rawat_inap')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->count(),
                'under_48h' => Visit::where('visit_type', 'rawat_inap')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->get()
                    ->filter(function ($v) {
                        return Carbon::parse($v->admission_date)->diffInHours($v->discharge_date) <= 48;
                    })
                    ->count(),
                'over_48h' => Visit::where('visit_type', 'rawat_inap')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->get()
                    ->filter(function ($v) {
                        return Carbon::parse($v->admission_date)->diffInHours($v->discharge_date) > 48;
                    })
                    ->count(),
            ],
            'igd' => [
                'total_deaths' => Visit::where('visit_type', 'igd')
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween('registration_date', [$startDate, $endDate])
                    ->count(),
            ],
            'rawat_jalan' => [
                'total_deaths' => 0, // Usually 0 for outpatient
            ],
        ];
    }

    /**
     * Format period label
     */
    public function formatPeriodLabel(string $period, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        return match ($period) {
            'today' => 'Hari Ini (' . now()->format('d M Y') . ')',
            'week' => 'Minggu Ini (' . now()->startOfWeek()->format('d M') . ' - ' . now()->endOfWeek()->format('d M Y') . ')',
            'month' => 'Bulan Ini (' . now()->format('F Y') . ')',
            'year' => 'Tahun Ini (' . now()->format('Y') . ')',
            'custom' => $startDate && $endDate 
                ? 'Periode (' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') . ')' 
                : 'Kustom',
            default => 'Hari Ini',
        };
    }

    /**
     * Get date range based on period
     */
    public function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }
}
