<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\PrescriptionResource;
use App\Models\Clinical\Prescription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Prescription API Controller.
 * 
 * Handles prescription management including creation, verification,
 * processing, and dispensing operations.
 */
class PrescriptionController extends BaseController
{
    /**
     * Display a listing of prescriptions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Prescription::query()
            ->with(['patient', 'doctor', 'medicalRecord', 'items.medicine'])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('patient', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('medical_record_number', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->medical_record_id, fn($q, $m) => $q->where('medical_record_id', $m))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        $prescriptions = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginateResponse($prescriptions);
    }

    /**
     * Store a newly created prescription.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medical_record_id' => ['required', 'exists:medical_records,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['required', 'exists:visits,id'],
            'type' => ['required', 'in:regular,narcotic,psychotropic,compound'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.dosage' => ['required', 'string'],
            'items.*.frequency' => ['required', 'string'],
            'items.*.duration' => ['required', 'string'],
            'items.*.instructions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $prescription = Prescription::create([
                'medical_record_id' => $validated['medical_record_id'],
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'],
                'doctor_id' => $request->user()->id,
                'type' => $validated['type'],
                'status' => 'pending',
                'prescription_number' => $this->generatePrescriptionNumber(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $prescription->items()->create($item);
            }

            DB::commit();

            return $this->createdResponse(
                new PrescriptionResource($prescription->load(['patient', 'doctor', 'items.medicine'])),
                'Prescription created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create prescription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified prescription.
     *
     * @param Prescription $prescription
     * @return JsonResponse
     */
    public function show(Prescription $prescription): JsonResponse
    {
        return $this->successResponse(
            new PrescriptionResource($prescription->load([
                'patient',
                'doctor',
                'medicalRecord',
                'items.medicine',
                'verifier',
                'processor',
                'dispenser',
            ]))
        );
    }

    /**
     * Verify prescription.
     *
     * @param Request $request
     * @param Prescription $prescription
     * @return JsonResponse
     */
    public function verify(Request $request, Prescription $prescription): JsonResponse
    {
        if (!in_array($prescription->status, ['pending'])) {
            return $this->errorResponse('Prescription cannot be verified', 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $prescription->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse(
            new PrescriptionResource($prescription->fresh()->load(['patient', 'doctor', 'items.medicine'])),
            'Prescription verified successfully'
        );
    }

    /**
     * Process prescription.
     *
     * @param Request $request
     * @param Prescription $prescription
     * @return JsonResponse
     */
    public function process(Request $request, Prescription $prescription): JsonResponse
    {
        if (!in_array($prescription->status, ['verified'])) {
            return $this->errorResponse('Prescription must be verified before processing', 422);
        }

        $validated = $request->validate([
            'processed_items' => ['required', 'array'],
            'processed_items.*.item_id' => ['required', 'exists:prescription_items,id'],
            'processed_items.*.quantity' => ['required', 'integer', 'min:1'],
            'processed_items.*.batch_number' => ['nullable', 'string'],
            'processed_items.*.expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['processed_items'] as $processedItem) {
                $item = $prescription->items()->find($processedItem['item_id']);
                if ($item) {
                    $item->update([
                        'processed_quantity' => $processedItem['quantity'],
                        'batch_number' => $processedItem['batch_number'] ?? null,
                        'expiry_date' => $processedItem['expiry_date'] ?? null,
                    ]);

                    // Update stock
                    $item->medicine->decrement('stock', $processedItem['quantity']);
                }
            }

            $prescription->update([
                'status' => 'processed',
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
                'processing_notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return $this->successResponse(
                new PrescriptionResource($prescription->fresh()->load(['patient', 'doctor', 'items.medicine'])),
                'Prescription processed successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to process prescription: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Dispense prescription.
     *
     * @param Request $request
     * @param Prescription $prescription
     * @return JsonResponse
     */
    public function dispense(Request $request, Prescription $prescription): JsonResponse
    {
        if (!in_array($prescription->status, ['processed'])) {
            return $this->errorResponse('Prescription must be processed before dispensing', 422);
        }

        $validated = $request->validate([
            'dispensed_to' => ['required', 'string'],
            'dispensed_to_relation' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $prescription->update([
            'status' => 'dispensed',
            'dispensed_by' => $request->user()->id,
            'dispensed_at' => now(),
            'dispensed_to' => $validated['dispensed_to'],
            'dispensed_to_relation' => $validated['dispensed_to_relation'] ?? null,
            'dispensing_notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse(
            new PrescriptionResource($prescription->fresh()->load(['patient', 'doctor', 'items.medicine'])),
            'Prescription dispensed successfully'
        );
    }

    /**
     * Reject prescription.
     *
     * @param Request $request
     * @param Prescription $prescription
     * @return JsonResponse
     */
    public function reject(Request $request, Prescription $prescription): JsonResponse
    {
        if (in_array($prescription->status, ['dispensed', 'cancelled'])) {
            return $this->errorResponse('Cannot reject dispensed or cancelled prescription', 422);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $prescription->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return $this->successResponse(
            new PrescriptionResource($prescription->fresh()),
            'Prescription rejected'
        );
    }

    /**
     * Generate unique prescription number.
     *
     * @return string
     */
    private function generatePrescriptionNumber(): string
    {
        $prefix = 'RCP';
        $date = date('Ymd');
        $lastPrescription = Prescription::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPrescription ? ((int) substr($lastPrescription->prescription_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
}
