<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\MasterData\Bed;
use Exception;
use App\Models\Patient\Visit as Inpatient;
use App\Models\Patient\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Inpatient Management API Controller.
 * 
 * Handles inpatient admission, transfer, discharge, and billing.
 */
class InpatientController extends BaseController
{
    /**
     * Display a listing of inpatients.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inpatient::query()
            ->with(['patient', 'bed.room', 'doctor', 'visit'])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('patient', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('medical_record_number', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->room_id, fn($q, $r) => $q->whereHas('bed', fn($sq) => $sq->where('room_id', $r)))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->is_active !== null, fn($q, $a) => $q->where('is_active', $a));

        $inpatients = $query->latest('admission_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($inpatients);
    }

    /**
     * Admit a patient for inpatient care.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function admit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'bed_id' => ['required', 'exists:beds,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'admission_date' => ['required', 'date'],
            'admission_diagnosis' => ['required', 'string'],
            'referral_type' => ['nullable', 'in:self,emergency,referral,transfer'],
            'referral_from' => ['nullable', 'string'],
            'companion_name' => ['nullable', 'string', 'max:100'],
            'companion_relation' => ['nullable', 'string', 'max:50'],
            'companion_phone' => ['nullable', 'string', 'max:20'],
            'guarantor' => ['nullable', 'in:personal,bpjs,insurance'],
            'guarantor_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // Check bed availability
            $bed = Bed::findOrFail($validated['bed_id']);
            if (!$bed->is_available) {
                return $this->errorResponse('Selected bed is not available', 422);
            }

            // Generate inpatient number
            $validated['inpatient_number'] = $this->generateInpatientNumber();
            $validated['status'] = 'admitted';
            $validated['is_active'] = true;
            $validated['registered_by'] = $request->user()->id;

            $inpatient = Inpatient::create($validated);

            // Mark bed as occupied
            $bed->update([
                'is_available' => false,
                'patient_id' => $validated['patient_id'],
            ]);

            // Update visit status if provided
            if (!empty($validated['visit_id'])) {
                Visit::where('id', $validated['visit_id'])->update([
                    'status' => 'in_progress',
                    'is_inpatient' => true,
                ]);
            }

            DB::commit();

            return $this->createdResponse(
                $inpatient->load(['patient', 'bed.room', 'doctor']),
                'Patient admitted successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to admit patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transfer patient to another bed.
     *
     * @param Request $request
     * @param Inpatient $inpatient
     * @return JsonResponse
     */
    public function transfer(Request $request, Inpatient $inpatient): JsonResponse
    {
        if (!$inpatient->is_active || !in_array($inpatient->status, ['admitted', 'transferred'])) {
            return $this->errorResponse('Patient cannot be transferred', 422);
        }

        $validated = $request->validate([
            'new_bed_id' => ['required', 'exists:beds,id'],
            'transfer_reason' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $newBed = Bed::findOrFail($validated['new_bed_id']);
            if (!$newBed->is_available) {
                return $this->errorResponse('Selected bed is not available', 422);
            }

            $oldBed = $inpatient->bed;

            // Record transfer history
            $inpatient->transfers()->create([
                'from_bed_id' => $oldBed->id,
                'to_bed_id' => $newBed->id,
                'transfer_date' => now(),
                'transfer_reason' => $validated['transfer_reason'],
                'transferred_by' => $request->user()->id,
            ]);

            // Update bed statuses
            $oldBed->update([
                'is_available' => true,
                'patient_id' => null,
            ]);

            $newBed->update([
                'is_available' => false,
                'patient_id' => $inpatient->patient_id,
            ]);

            // Update inpatient
            $inpatient->update([
                'bed_id' => $newBed->id,
                'status' => 'transferred',
            ]);

            DB::commit();

            return $this->successResponse(
                $inpatient->fresh()->load(['patient', 'bed.room', 'doctor']),
                'Patient transferred successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to transfer patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Discharge a patient.
     *
     * @param Request $request
     * @param Inpatient $inpatient
     * @return JsonResponse
     */
    public function discharge(Request $request, Inpatient $inpatient): JsonResponse
    {
        if (!$inpatient->is_active || !in_array($inpatient->status, ['admitted', 'transferred'])) {
            return $this->errorResponse('Patient cannot be discharged', 422);
        }

        $validated = $request->validate([
            'discharge_date' => ['required', 'date'],
            'discharge_status' => ['required', 'in:recovered,improved,not_improved,died,referred'],
            'discharge_diagnosis' => ['required', 'string'],
            'icd10_code' => ['nullable', 'string', 'exists:icd10s,code'],
            'discharge_notes' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_instructions' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            // Free the bed
            $bed = $inpatient->bed;
            $bed->update([
                'is_available' => true,
                'patient_id' => null,
            ]);

            // Update inpatient
            $inpatient->update([
                'status' => 'discharged',
                'is_active' => false,
                'discharge_date' => $validated['discharge_date'],
                'discharge_status' => $validated['discharge_status'],
                'discharge_diagnosis' => $validated['discharge_diagnosis'],
                'icd10_code' => $validated['icd10_code'] ?? null,
                'discharge_notes' => $validated['discharge_notes'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'follow_up_instructions' => $validated['follow_up_instructions'] ?? null,
                'discharged_by' => $request->user()->id,
            ]);

            // Update visit if exists
            if ($inpatient->visit_id) {
                $inpatient->visit->update([
                    'status' => 'completed',
                ]);
            }

            DB::commit();

            return $this->successResponse(
                $inpatient->fresh()->load(['patient', 'bed.room', 'doctor']),
                'Patient discharged successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to discharge patient: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get inpatient billing information.
     *
     * @param Request $request
     * @param Inpatient $inpatient
     * @return JsonResponse
     */
    public function bill(Request $request, Inpatient $inpatient): JsonResponse
    {
        $inpatient->load(['patient', 'bed.room', 'charges', 'payments']);

        $roomCharges = $inpatient->charges()->where('type', 'room')->sum('amount');
        $procedureCharges = $inpatient->charges()->where('type', 'procedure')->sum('amount');
        $medicineCharges = $inpatient->charges()->where('type', 'medicine')->sum('amount');
        $labCharges = $inpatient->charges()->where('type', 'lab')->sum('amount');
        $radiologyCharges = $inpatient->charges()->where('type', 'radiology')->sum('amount');
        $otherCharges = $inpatient->charges()->where('type', 'other')->sum('amount');

        $totalCharges = $roomCharges + $procedureCharges + $medicineCharges +
            $labCharges + $radiologyCharges + $otherCharges;

        $totalPayments = $inpatient->payments()->sum('amount');

        return $this->successResponse([
            'inpatient' => [
                'id' => $inpatient->id,
                'inpatient_number' => $inpatient->inpatient_number,
                'patient' => $inpatient->patient,
            ],
            'stay_duration' => $inpatient->discharge_date
                ? $inpatient->admission_date->diffInDays($inpatient->discharge_date)
                : $inpatient->admission_date->diffInDays(now()),
            'charges' => [
                'room' => $roomCharges,
                'procedure' => $procedureCharges,
                'medicine' => $medicineCharges,
                'lab' => $labCharges,
                'radiology' => $radiologyCharges,
                'other' => $otherCharges,
                'total' => $totalCharges,
            ],
            'payments' => [
                'total_paid' => $totalPayments,
                'balance' => $totalCharges - $totalPayments,
            ],
            'charge_details' => $inpatient->charges()->with('itemable')->latest()->paginate(20),
        ]);
    }

    /**
     * Display the specified inpatient.
     *
     * @param Inpatient $inpatient
     * @return JsonResponse
     */
    public function show(Inpatient $inpatient): JsonResponse
    {
        return $this->successResponse(
            $inpatient->load([
                'patient',
                'bed.room',
                'doctor',
                'visit',
                'transfers.fromBed.room',
                'transfers.toBed.room',
            ])
        );
    }

    /**
     * Generate unique inpatient number.
     *
     * @return string
     */
    private function generateInpatientNumber(): string
    {
        $prefix = 'RI';
        $year = date('Y');
        $lastInpatient = Inpatient::whereYear('admission_date', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInpatient ? ((int) substr($lastInpatient->inpatient_number, -6) + 1) : 1;

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }
}
