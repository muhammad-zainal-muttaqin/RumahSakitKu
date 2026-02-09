<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\MedicalRecordResource;
use App\Models\Clinical\MedicalRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Electronic Medical Record (EMR) API Controller.
 * 
 * Handles medical record management including creation, updates,
 * finalization, and retrieval of related data like CPPTs and prescriptions.
 */
class MedicalRecordController extends BaseController
{
    /**
     * Display a listing of medical records.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicalRecord::query()
            ->with(['patient', 'visit', 'doctor', 'icd10', 'icd9'])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('patient', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('medical_record_number', 'like', "%{$search}%");
                });
            })
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->visit_id, fn($q, $v) => $q->where('visit_id', $v))
            ->when($request->is_finalized !== null, fn($q, $f) => $q->where('is_finalized', $f))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        $records = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginateResponse($records);
    }

    /**
     * Store a newly created medical record.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'chief_complaint' => ['required', 'string'],
            'present_illness_history' => ['nullable', 'string'],
            'past_medical_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'allergy_history' => ['nullable', 'string'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.systolic_bp' => ['nullable', 'numeric'],
            'vital_signs.diastolic_bp' => ['nullable', 'numeric'],
            'vital_signs.heart_rate' => ['nullable', 'numeric'],
            'vital_signs.respiratory_rate' => ['nullable', 'numeric'],
            'vital_signs.temperature' => ['nullable', 'numeric'],
            'vital_signs.oxygen_saturation' => ['nullable', 'numeric'],
            'vital_signs.weight' => ['nullable', 'numeric'],
            'vital_signs.height' => ['nullable', 'numeric'],
            'physical_examination' => ['nullable', 'string'],
            'supporting_examination' => ['nullable', 'string'],
            'assessment' => ['required', 'string'],
            'icd10_code' => ['nullable', 'string', 'exists:icd10s,code'],
            'icd9_code' => ['nullable', 'string', 'exists:icd9s,code'],
            'plan' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $validated['doctor_id'] = $request->user()->id;
            $validated['is_finalized'] = false;

            $record = MedicalRecord::create($validated);

            DB::commit();

            return $this->createdResponse(
                new MedicalRecordResource($record->load(['patient', 'visit', 'doctor', 'icd10'])),
                'Medical record created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create medical record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified medical record.
     *
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        return $this->successResponse(
            new MedicalRecordResource($medicalRecord->load([
                'patient',
                'visit',
                'doctor',
                'icd10',
                'icd9',
                'cppts',
                'prescriptions',
            ]))
        );
    }

    /**
     * Update the specified medical record.
     *
     * @param Request $request
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function update(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        if ($medicalRecord->is_finalized) {
            return $this->errorResponse('Cannot update finalized medical record', 403);
        }

        $validated = $request->validate([
            'chief_complaint' => ['string'],
            'present_illness_history' => ['nullable', 'string'],
            'past_medical_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'allergy_history' => ['nullable', 'string'],
            'vital_signs' => ['nullable', 'array'],
            'physical_examination' => ['nullable', 'string'],
            'supporting_examination' => ['nullable', 'string'],
            'assessment' => ['string'],
            'icd10_code' => ['nullable', 'string', 'exists:icd10s,code'],
            'icd9_code' => ['nullable', 'string', 'exists:icd9s,code'],
            'plan' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
        ]);

        $medicalRecord->update($validated);

        return $this->successResponse(
            new MedicalRecordResource($medicalRecord->fresh()->load(['patient', 'visit', 'doctor'])),
            'Medical record updated successfully'
        );
    }

    /**
     * Finalize the medical record.
     *
     * @param Request $request
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function finalize(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        if ($medicalRecord->is_finalized) {
            return $this->errorResponse('Medical record is already finalized', 422);
        }

        $medicalRecord->update([
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new MedicalRecordResource($medicalRecord->fresh()),
            'Medical record finalized successfully'
        );
    }

    /**
     * Get CPPTs (Catatan Perkembangan Pasien Terintegrasi) for the medical record.
     *
     * @param Request $request
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function cppts(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $cppts = $medicalRecord->cppts()
            ->with(['doctor', 'creator'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginateResponse($cppts);
    }

    /**
     * Get prescriptions for the medical record.
     *
     * @param Request $request
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function prescriptions(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $prescriptions = $medicalRecord->prescriptions()
            ->with(['items.medicine', 'doctor'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginateResponse($prescriptions);
    }

    /**
     * Unfinalize a medical record (requires special permission).
     *
     * @param Request $request
     * @param MedicalRecord $medicalRecord
     * @return JsonResponse
     */
    public function unfinalize(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        // Check if user has permission to unfinalize
        if (!$request->user()->can('medical-record.unfinalize')) {
            return $this->forbiddenResponse('You do not have permission to unfinalize medical records');
        }

        if (!$medicalRecord->is_finalized) {
            return $this->errorResponse('Medical record is not finalized', 422);
        }

        $medicalRecord->update([
            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,
            'unfinalized_at' => now(),
            'unfinalized_by' => $request->user()->id,
            'unfinalized_reason' => $request->reason,
        ]);

        return $this->successResponse(
            new MedicalRecordResource($medicalRecord->fresh()),
            'Medical record unfinalized successfully'
        );
    }
}
