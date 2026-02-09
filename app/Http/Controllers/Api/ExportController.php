<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Exports\FinancialReportExport;
use App\Exports\LabResultsExport;
use App\Exports\MedicalRecordsExport;
use App\Exports\PatientsExport;
use App\Exports\PrescriptionsExport;
use App\Exports\RL1Export;
use App\Exports\RL3Export;
use App\Exports\VisitsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export Controller
 * 
 * Handles all Excel export operations for the SIMRS system.
 */
class ExportController extends Controller
{
    /**
     * Export patients to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function patients(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'insurance_type' => 'nullable|string',
                'active_only' => 'nullable|boolean',
            ]);

            $export = new PatientsExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                insuranceType: $request->input('insurance_type'),
                activeOnly: $request->boolean('active_only', true)
            );

            $filename = 'patients_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Patient export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export visits to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function visits(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'polyclinic_id' => 'nullable|integer',
                'doctor_id' => 'nullable|integer',
                'status' => 'nullable|string',
            ]);

            $export = new VisitsExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                polyclinicId: $request->input('polyclinic_id'),
                doctorId: $request->input('doctor_id'),
                status: $request->input('status')
            );

            $filename = 'visits_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Visits export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export medical records to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function medicalRecords(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'doctor_id' => 'nullable|integer',
                'include_soap' => 'nullable|boolean',
            ]);

            $export = new MedicalRecordsExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                doctorId: $request->input('doctor_id'),
                includeSoap: $request->boolean('include_soap', false)
            );

            $filename = 'medical_records_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Medical records export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export prescriptions to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function prescriptions(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'status' => 'nullable|string',
                'doctor_id' => 'nullable|integer',
                'include_items' => 'nullable|boolean',
            ]);

            $export = new PrescriptionsExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                status: $request->input('status'),
                doctorId: $request->input('doctor_id'),
                includeItems: $request->boolean('include_items', false)
            );

            $filename = 'prescriptions_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Prescriptions export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export lab results to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function labResults(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'patient_id' => 'nullable|integer',
                'test_type' => 'nullable|string',
                'flag' => 'nullable|string|in:normal,abnormal,critical,low,high',
            ]);

            $export = new LabResultsExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                patientId: $request->input('patient_id'),
                testType: $request->input('test_type'),
                flag: $request->input('flag')
            );

            $filename = 'lab_results_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Lab results export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export financial report to Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function financial(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'payment_method' => 'nullable|string',
            ]);

            $export = new FinancialReportExport(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                paymentMethod: $request->input('payment_method')
            );

            $filename = 'financial_report_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('Financial report export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export RL 1 report (Data Dasar RS).
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function rl1(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $export = new RL1Export(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date')
            );

            $filename = 'RL1_DataDasar_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('RL 1 export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export RL 3 report (Pelayanan RS).
     *
     * @param Request $request
     * @return BinaryFileResponse|JsonResponse
     */
    public function rl3(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $export = new RL3Export(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date')
            );

            $filename = 'RL3_Pelayanan_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (Exception $e) {
            Log::error('RL 3 export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available export types.
     *
     * @return JsonResponse
     */
    public function availableExports(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'key' => 'patients',
                    'name' => 'Export Pasien',
                    'description' => 'Export data pasien dengan filter tanggal dan tipe asuransi',
                    'endpoint' => '/api/exports/patients',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'insurance_type', 'active_only'],
                ],
                [
                    'key' => 'visits',
                    'name' => 'Export Kunjungan',
                    'description' => 'Export data kunjungan dengan filter poliklinik dan dokter',
                    'endpoint' => '/api/exports/visits',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'polyclinic_id', 'doctor_id', 'status'],
                ],
                [
                    'key' => 'medical_records',
                    'name' => 'Export Rekam Medis',
                    'description' => 'Export data rekam medis dengan optional SOAP notes',
                    'endpoint' => '/api/exports/medical-records',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'doctor_id', 'include_soap'],
                ],
                [
                    'key' => 'prescriptions',
                    'name' => 'Export Resep',
                    'description' => 'Export data resep dengan detail items',
                    'endpoint' => '/api/exports/prescriptions',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'status', 'doctor_id', 'include_items'],
                ],
                [
                    'key' => 'lab_results',
                    'name' => 'Export Hasil Lab',
                    'description' => 'Export hasil pemeriksaan laboratorium',
                    'endpoint' => '/api/exports/lab-results',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'patient_id', 'test_type', 'flag'],
                ],
                [
                    'key' => 'financial',
                    'name' => 'Export Laporan Keuangan',
                    'description' => 'Export laporan keuangan dengan summary per metode pembayaran',
                    'endpoint' => '/api/exports/financial',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date', 'payment_method'],
                ],
                [
                    'key' => 'rl1',
                    'name' => 'Export RL 1 (Data Dasar RS)',
                    'description' => 'Export laporan RL 1.1 - Data Dasar Rumah Sakit',
                    'endpoint' => '/api/exports/rl1',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date'],
                ],
                [
                    'key' => 'rl3',
                    'name' => 'Export RL 3 (Pelayanan)',
                    'description' => 'Export laporan RL 3.1 - 3.15 - Pelayanan Rumah Sakit',
                    'endpoint' => '/api/exports/rl3',
                    'method' => 'POST',
                    'filters' => ['start_date', 'end_date'],
                ],
            ],
        ]);
    }
}
