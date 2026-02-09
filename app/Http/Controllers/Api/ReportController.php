<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Clinical\MedicalRecord;
use App\Models\Financial\Invoice;
use App\Models\Patient\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reports API Controller.
 * 
 * Handles various hospital reports including daily/monthly statistics,
 * revenue reports, and RL indicators.
 */
class ReportController extends BaseController
{
    /**
     * Get daily statistics report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        // Visits statistics
        $visits = Visit::whereDate('visit_date', $date);
        $totalVisits = $visits->count();
        $visitsByType = $visits->selectRaw('visit_type_id, count(*) as count')
            ->groupBy('visit_type_id')
            ->with('visitType:id,name')
            ->get();
        $visitsByClinic = $visits->selectRaw('clinic_id, count(*) as count')
            ->groupBy('clinic_id')
            ->with('clinic:id,name')
            ->get();

        // New vs old patients
        $newPatients = Visit::whereDate('visit_date', $date)
            ->where('is_new_patient', true)
            ->count();
        $oldPatients = $totalVisits - $newPatients;

        // BPJS vs non-BPJS
        $bpjsVisits = Visit::whereDate('visit_date', $date)
            ->where('is_bpjs', true)
            ->count();
        $nonBpjsVisits = $totalVisits - $bpjsVisits;

        // Gender distribution
        $genderDistribution = Visit::whereDate('visit_date', $date)
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->selectRaw('patients.gender, count(*) as count')
            ->groupBy('patients.gender')
            ->pluck('count', 'patients.gender');

        // Revenue
        $revenue = Invoice::whereDate('invoice_date', $date)
            ->where('status', '!=', 'voided')
            ->selectRaw('sum(total) as total, sum(paid_amount) as paid, count(*) as count')
            ->first();

        return $this->successResponse([
            'date' => $date,
            'visits' => [
                'total' => $totalVisits,
                'new_patients' => $newPatients,
                'old_patients' => $oldPatients,
                'bpjs' => $bpjsVisits,
                'non_bpjs' => $nonBpjsVisits,
                'by_type' => $visitsByType,
                'by_clinic' => $visitsByClinic,
            ],
            'gender_distribution' => $genderDistribution,
            'revenue' => [
                'total_invoices' => $revenue->count ?? 0,
                'total_amount' => (float) ($revenue->total ?? 0),
                'paid_amount' => (float) ($revenue->paid ?? 0),
                'pending_amount' => (float) (($revenue->total ?? 0) - ($revenue->paid ?? 0)),
            ],
        ]);
    }

    /**
     * Get monthly statistics report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthly(Request $request): JsonResponse
    {
        $month = $request->input('month', today()->month);
        $year = $request->input('year', today()->year);

        $startDate = "{$year}-{$month}-01";
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

        // Daily breakdown
        $dailyStats = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('DATE(visit_date) as date, count(*) as visits')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Total monthly statistics
        $totalVisits = Visit::whereBetween('visit_date', [$startDate, $endDate])->count();
        $newPatients = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('is_new_patient', true)
            ->count();
        $bpjsVisits = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('is_bpjs', true)
            ->count();

        // Revenue by day
        $dailyRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', '!=', 'voided')
            ->selectRaw('DATE(invoice_date) as date, sum(total) as revenue, count(*) as invoices')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top clinics
        $topClinics = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('clinic_id, count(*) as visits')
            ->groupBy('clinic_id')
            ->with('clinic:id,name')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        // Top doctors
        $topDoctors = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->whereNotNull('doctor_id')
            ->selectRaw('doctor_id, count(*) as visits')
            ->groupBy('doctor_id')
            ->with('doctor:id,name')
            ->orderByDesc('visits')
            ->limit(10)
            ->get();

        return $this->successResponse([
            'month' => $month,
            'year' => $year,
            'summary' => [
                'total_visits' => $totalVisits,
                'new_patients' => $newPatients,
                'bpjs_visits' => $bpjsVisits,
                'average_daily_visits' => round($totalVisits / count($dailyStats), 2),
            ],
            'daily_stats' => $dailyStats,
            'daily_revenue' => $dailyRevenue,
            'top_clinics' => $topClinics,
            'top_doctors' => $topDoctors,
        ]);
    }

    /**
     * Get RL 3 indicators report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rl3(Request $request): JsonResponse
    {
        $month = $request->input('month', today()->month);
        $year = $request->input('year', today()->year);

        $startDate = "{$year}-{$month}-01";
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

        // RL 3.1 - General patient data
        $generalData = [
            'new_visits' => Visit::whereBetween('visit_date', [$startDate, $endDate])
                ->where('is_new_patient', true)
                ->count(),
            'return_visits' => Visit::whereBetween('visit_date', [$startDate, $endDate])
                ->where('is_new_patient', false)
                ->count(),
            'total_visits' => Visit::whereBetween('visit_date', [$startDate, $endDate])->count(),
        ];

        // RL 3.2 - Visits by age and gender
        $visitsByAgeGender = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->join('patients', 'visits.patient_id', '=', 'patients.id')
            ->selectRaw('
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 1 THEN "0-1"
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 5 THEN "1-4"
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 15 THEN "5-14"
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 25 THEN "15-24"
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 45 THEN "25-44"
                    WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 65 THEN "45-64"
                    ELSE "65+"
                END as age_group,
                patients.gender,
                count(*) as count
            ')
            ->groupBy('age_group', 'patients.gender')
            ->get();

        // RL 3.3 - Top 10 diseases
        $topDiseases = MedicalRecord::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('icd10_code')
            ->selectRaw('icd10_code, count(*) as cases')
            ->groupBy('icd10_code')
            ->with('icd10:code,name')
            ->orderByDesc('cases')
            ->limit(10)
            ->get();

        // RL 3.4 - Visits by clinic type
        $visitsByClinic = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->selectRaw('clinic_id, count(*) as visits')
            ->groupBy('clinic_id')
            ->with('clinic:id,name,type')
            ->get();

        // RL 3.5 - Emergency visits
        $emergencyVisits = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('is_emergency', true)
            ->count();

        return $this->successResponse([
            'month' => $month,
            'year' => $year,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'rl_3_1_general' => $generalData,
            'rl_3_2_age_gender' => $visitsByAgeGender,
            'rl_3_3_top_diseases' => $topDiseases,
            'rl_3_4_by_clinic' => $visitsByClinic,
            'rl_3_5_emergency' => $emergencyVisits,
        ]);
    }

    /**
     * Get revenue report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'group_by' => ['nullable', 'in:day,week,month'],
        ]);

        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $groupBy = $request->input('group_by', 'day');

        $query = Invoice::whereBetween('invoice_date', [$fromDate, $toDate])
            ->where('status', '!=', 'voided');

        switch ($groupBy) {
            case 'week':
                $groupFormat = '%Y-%u';
                $dateFormat = 'Y-W';
                break;
            case 'month':
                $groupFormat = '%Y-%m';
                $dateFormat = 'Y-m';
                break;
            case 'day':
            default:
                $groupFormat = '%Y-%m-%d';
                $dateFormat = 'Y-m-d';
                break;
        }

        $revenueData = (clone $query)
            ->selectRaw("DATE_FORMAT(invoice_date, '{$groupFormat}') as period")
            ->selectRaw('sum(subtotal) as subtotal')
            ->selectRaw('sum(discount) as discount')
            ->selectRaw('sum(tax) as tax')
            ->selectRaw('sum(total) as total')
            ->selectRaw('count(*) as invoices')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $paymentMethods = Invoice::whereBetween('invoice_date', [$fromDate, $toDate])
            ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
            ->where('invoices.status', '!=', 'voided')
            ->selectRaw('payments.payment_method, sum(payments.amount) as total')
            ->groupBy('payments.payment_method')
            ->get();

        $summary = [
            'total_invoices' => (clone $query)->count(),
            'total_subtotal' => (float) (clone $query)->sum('subtotal'),
            'total_discount' => (float) (clone $query)->sum('discount'),
            'total_tax' => (float) (clone $query)->sum('tax'),
            'total_revenue' => (float) (clone $query)->sum('total'),
            'total_paid' => (float) (clone $query)->sum('paid_amount'),
            'total_unpaid' => (float) (clone $query)->sum(DB::raw('total - paid_amount')),
        ];

        return $this->successResponse([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
                'group_by' => $groupBy,
            ],
            'summary' => $summary,
            'revenue_by_period' => $revenueData,
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * Get top diseases report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topDiseases(Request $request): JsonResponse
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $fromDate = $request->input('from_date', today()->subMonth()->toDateString());
        $toDate = $request->input('to_date', today()->toDateString());
        $limit = $request->input('limit', 20);

        $diseases = MedicalRecord::whereBetween('created_at', [$fromDate, $toDate])
            ->whereNotNull('icd10_code')
            ->selectRaw('icd10_code, count(*) as total_cases')
            ->groupBy('icd10_code')
            ->with(['icd10:code,name', 'icd10.category'])
            ->orderByDesc('total_cases')
            ->limit($limit)
            ->get()
            ->map(function ($record) use ($fromDate, $toDate) {
                // Get gender distribution
                $genderDist = MedicalRecord::whereBetween('created_at', [$fromDate, $toDate])
                    ->where('icd10_code', $record->icd10_code)
                    ->join('patients', 'medical_records.patient_id', '=', 'patients.id')
                    ->selectRaw('patients.gender, count(*) as count')
                    ->groupBy('patients.gender')
                    ->pluck('count', 'patients.gender');

                // Get age distribution
                $ageDist = MedicalRecord::whereBetween('created_at', [$fromDate, $toDate])
                    ->where('icd10_code', $record->icd10_code)
                    ->join('patients', 'medical_records.patient_id', '=', 'patients.id')
                    ->selectRaw('
                        CASE
                            WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 15 THEN "child"
                            WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 60 THEN "adult"
                            ELSE "elderly"
                        END as age_group,
                        count(*) as count
                    ')
                    ->groupBy('age_group')
                    ->pluck('count', 'age_group');

                return [
                    'icd10_code' => $record->icd10_code,
                    'icd10_name' => $record->icd10?->name,
                    'category' => $record->icd10?->category?->name,
                    'total_cases' => $record->total_cases,
                    'gender_distribution' => $genderDist,
                    'age_distribution' => $ageDist,
                ];
            });

        return $this->successResponse([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'total_diseases' => $diseases->count(),
            'diseases' => $diseases,
        ]);
    }
}
