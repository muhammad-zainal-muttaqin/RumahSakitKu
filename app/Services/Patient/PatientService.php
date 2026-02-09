<?php

declare(strict_types=1);

namespace App\Services\Patient;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Patient Service
 *
 * Manages patient search, medical record number generation,
 * patient statistics, and duplicate patient merging.
 */
class PatientService
{
    /**
     * Search patients by name, NIK, or medical record number.
     *
     * @param string $search The search term
     * @param int $limit Maximum number of results to return
     * @return Collection
     */
    public function searchPatients(string $search, int $limit = 20)
    {
        try {
            return Patient::where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('medical_record_number', 'like', "%{$search}%");
            })
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            Log::error('PatientService: Error searching patients', [
                'search' => $search,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Generate the next medical record number.
     *
     * Format: RM-YYMMDD-XXXX where XXXX is a zero-padded sequential number
     * that resets daily.
     *
     * @return string The generated medical record number
     */
    public function generateMedicalRecordNumber(): string
    {
        try {
            $datePrefix = now()->format('ymd');
            $prefix = "RM-{$datePrefix}-";

            $lastPatient = Patient::where('medical_record_number', 'like', "{$prefix}%")
                ->orderBy('medical_record_number', 'desc')
                ->first();

            if ($lastPatient) {
                $lastSequence = (int) substr($lastPatient->medical_record_number, -4);
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            Log::error('PatientService: Error generating medical record number', [
                'error' => $e->getMessage(),
            ]);

            // Fallback with timestamp to avoid collisions
            return 'RM-' . now()->format('ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get statistics for a specific patient.
     *
     * Returns visit count, last visit date, total invoices,
     * total amount billed, and total amount paid.
     *
     * @param int $patientId The patient ID
     * @return array Patient statistics
     */
    public function getPatientStats(int $patientId): array
    {
        try {
            $patient = Patient::find($patientId);

            if (!$patient) {
                Log::warning('PatientService: Patient not found for stats', [
                    'patient_id' => $patientId,
                ]);

                return [
                    'visit_count' => 0,
                    'last_visit' => null,
                    'total_invoices' => 0,
                    'total_billed' => 0.0,
                    'total_paid' => 0.0,
                    'outstanding_balance' => 0.0,
                ];
            }

            $visitCount = Visit::where('patient_id', $patientId)->count();

            $lastVisit = Visit::where('patient_id', $patientId)
                ->orderBy('visit_date', 'desc')
                ->first();

            $invoiceStats = DB::table('invoices')
                ->where('patient_id', $patientId)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as total_invoices')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_billed')
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
                ->selectRaw('COALESCE(SUM(balance_due), 0) as outstanding_balance')
                ->first();

            return [
                'visit_count' => $visitCount,
                'last_visit' => $lastVisit?->visit_date?->toDateString(),
                'total_invoices' => (int) $invoiceStats->total_invoices,
                'total_billed' => (float) $invoiceStats->total_billed,
                'total_paid' => (float) $invoiceStats->total_paid,
                'outstanding_balance' => (float) $invoiceStats->outstanding_balance,
            ];
        } catch (Exception $e) {
            Log::error('PatientService: Error getting patient stats', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);

            return [
                'visit_count' => 0,
                'last_visit' => null,
                'total_invoices' => 0,
                'total_billed' => 0.0,
                'total_paid' => 0.0,
                'outstanding_balance' => 0.0,
            ];
        }
    }

    /**
     * Merge duplicate patient records.
     *
     * Moves all visits, medical records, prescriptions, and invoices
     * from the secondary patient to the primary patient, then
     * soft-deletes the secondary patient.
     *
     * @param int $primaryId The primary patient ID (records will be kept here)
     * @param int $secondaryId The secondary patient ID (will be soft-deleted)
     * @return bool True if merge was successful
     */
    public function mergePatients(int $primaryId, int $secondaryId): bool
    {
        if ($primaryId === $secondaryId) {
            Log::warning('PatientService: Cannot merge patient with itself', [
                'patient_id' => $primaryId,
            ]);
            return false;
        }

        try {
            DB::beginTransaction();

            $primary = Patient::find($primaryId);
            $secondary = Patient::find($secondaryId);

            if (!$primary || !$secondary) {
                Log::error('PatientService: Invalid patient IDs for merge', [
                    'primary_id' => $primaryId,
                    'secondary_id' => $secondaryId,
                ]);
                DB::rollBack();
                return false;
            }

            // Move visits to the primary patient
            DB::table('visits')
                ->where('patient_id', $secondaryId)
                ->update(['patient_id' => $primaryId, 'updated_at' => now()]);

            // Move medical records to the primary patient
            DB::table('medical_records')
                ->where('patient_id', $secondaryId)
                ->update(['patient_id' => $primaryId, 'updated_at' => now()]);

            // Move prescriptions to the primary patient
            DB::table('prescriptions')
                ->where('patient_id', $secondaryId)
                ->update(['patient_id' => $primaryId, 'updated_at' => now()]);

            // Move invoices to the primary patient
            DB::table('invoices')
                ->where('patient_id', $secondaryId)
                ->update(['patient_id' => $primaryId, 'updated_at' => now()]);

            // Move visit queues to the primary patient
            DB::table('visit_queues')
                ->where('patient_id', $secondaryId)
                ->update(['patient_id' => $primaryId, 'updated_at' => now()]);

            // Soft-delete the secondary patient
            $secondary->update([
                'is_active' => false,
            ]);
            $secondary->delete();

            DB::commit();

            Log::info('PatientService: Patients merged successfully', [
                'primary_id' => $primaryId,
                'secondary_id' => $secondaryId,
            ]);

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PatientService: Error merging patients', [
                'primary_id' => $primaryId,
                'secondary_id' => $secondaryId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
