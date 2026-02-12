<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\RadiologyOrderResource;
use App\Models\Clinical\RadiologyOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Radiology Orders API Controller.
 * 
 * Handles radiology test orders, result entry, and image uploads.
 */
class RadiologyController extends BaseController
{
    /**
     * Display a listing of radiology orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = RadiologyOrder::query()
            ->with(['patient', 'doctor', 'visit', 'examination'])
            ->when($request->search, function ($q, $search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->when($request->modality, fn($q, $m) => $q->where('modality', $m))
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('order_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('order_date', '<=', $d));

        $orders = $query->latest('order_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($orders);
    }

    /**
     * Store a newly created radiology order.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['required', 'exists:visits,id'],
            'medical_record_id' => ['nullable', 'exists:medical_records,id'],
            'radiology_examination_id' => ['required', 'exists:radiology_examinations,id'],
            'priority' => ['required', 'in:normal,urgent,cito'],
            'modality' => ['required', 'in:xray,ct,mri,usg,mammography,dexa,fluoroscopy'],
            'clinical_diagnosis' => ['nullable', 'string'],
            'examination_indication' => ['nullable', 'string'],
            'clinical_history' => ['nullable', 'string'],
            'allergy_contrast' => ['boolean'],
            'pregnant' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $order = RadiologyOrder::create([
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'],
                'medical_record_id' => $validated['medical_record_id'] ?? null,
                'doctor_id' => $request->user()->id,
                'radiology_examination_id' => $validated['radiology_examination_id'],
                'order_number' => $this->generateOrderNumber(),
                'order_date' => now(),
                'priority' => $validated['priority'],
                'modality' => $validated['modality'],
                'status' => 'pending',
                'clinical_diagnosis' => $validated['clinical_diagnosis'] ?? null,
                'examination_indication' => $validated['examination_indication'] ?? null,
                'clinical_history' => $validated['clinical_history'] ?? null,
                'allergy_contrast' => $validated['allergy_contrast'] ?? false,
                'pregnant' => $validated['pregnant'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return $this->createdResponse(
                new RadiologyOrderResource($order->load(['patient', 'doctor', 'examination'])),
                'Radiology order created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create radiology order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified radiology order.
     *
     * @param RadiologyOrder $radiologyOrder
     * @return JsonResponse
     */
    public function show(RadiologyOrder $radiologyOrder): JsonResponse
    {
        return $this->successResponse(
            new RadiologyOrderResource($radiologyOrder->load([
                'patient',
                'doctor',
                'visit',
                'medicalRecord',
                'examination',
                'images',
                'radiologist',
            ]))
        );
    }

    /**
     * Submit radiology results.
     *
     * @param Request $request
     * @param RadiologyOrder $radiologyOrder
     * @return JsonResponse
     */
    public function results(Request $request, RadiologyOrder $radiologyOrder): JsonResponse
    {
        if (!in_array($radiologyOrder->status, ['pending', 'in_progress'])) {
            return $this->errorResponse('Cannot update results for this order', 422);
        }

        $validated = $request->validate([
            'examination_result' => ['required', 'string'],
            'conclusion' => ['required', 'string'],
            'suggestion' => ['nullable', 'string'],
            'icd10_code' => ['nullable', 'string', 'exists:icd10s,code'],
            'examination_quality' => ['nullable', 'in:good,fair,poor'],
            'contrast_used' => ['boolean'],
            'contrast_amount' => ['nullable', 'string'],
            'side_effects' => ['nullable', 'string'],
        ]);

        $radiologyOrder->update([
            'examination_result' => $validated['examination_result'],
            'conclusion' => $validated['conclusion'],
            'suggestion' => $validated['suggestion'] ?? null,
            'icd10_code' => $validated['icd10_code'] ?? null,
            'examination_quality' => $validated['examination_quality'] ?? 'good',
            'contrast_used' => $validated['contrast_used'] ?? false,
            'contrast_amount' => $validated['contrast_amount'] ?? null,
            'side_effects' => $validated['side_effects'] ?? null,
            'radiologist_id' => $request->user()->id,
            'examination_completed_at' => now(),
            'status' => 'completed',
        ]);

        return $this->successResponse(
            new RadiologyOrderResource($radiologyOrder->fresh()->load(['patient', 'radiologist'])),
            'Results saved successfully'
        );
    }

    /**
     * Upload radiology images.
     *
     * @param Request $request
     * @param RadiologyOrder $radiologyOrder
     * @return JsonResponse
     */
    public function upload(Request $request, RadiologyOrder $radiologyOrder): JsonResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:10240'], // Max 10MB per image
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            $uploadedImages = [];

            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('radiology/' . $radiologyOrder->order_number, 'private');

                $imageRecord = $radiologyOrder->images()->create([
                    'file_path' => $path,
                    'file_name' => $image->getClientOriginalName(),
                    'file_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'caption' => $validated['captions'][$index] ?? null,
                    'uploaded_by' => $request->user()->id,
                ]);

                $uploadedImages[] = [
                    'id' => $imageRecord->id,
                    'url' => Storage::disk('private')->url($path),
                    'caption' => $imageRecord->caption,
                ];
            }

            // Update order status to in_progress if it was pending
            if ($radiologyOrder->status === 'pending') {
                $radiologyOrder->update([
                    'status' => 'in_progress',
                    'examination_started_at' => now(),
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'images' => $uploadedImages,
                'total_uploaded' => count($uploadedImages),
            ], 'Images uploaded successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to upload images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate radiology results.
     *
     * @param Request $request
     * @param RadiologyOrder $radiologyOrder
     * @return JsonResponse
     */
    public function validateOrder(Request $request, RadiologyOrder $radiologyOrder): JsonResponse
    {
        if ($radiologyOrder->status !== 'completed') {
            return $this->errorResponse('Order must be completed before validation', 422);
        }

        if ($radiologyOrder->validated_at) {
            return $this->errorResponse('Order is already validated', 422);
        }

        $validated = $request->validate([
            'validation_notes' => ['nullable', 'string'],
        ]);

        $radiologyOrder->update([
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'validation_notes' => $validated['validation_notes'] ?? null,
            'status' => 'validated',
        ]);

        return $this->successResponse(
            new RadiologyOrderResource($radiologyOrder->fresh()->load(['patient', 'radiologist'])),
            'Radiology order validated successfully'
        );
    }

    /**
     * Cancel a radiology order.
     *
     * @param Request $request
     * @param RadiologyOrder $radiologyOrder
     * @return JsonResponse
     */
    public function cancel(Request $request, RadiologyOrder $radiologyOrder): JsonResponse
    {
        if (in_array($radiologyOrder->status, ['completed', 'validated', 'cancelled'])) {
            return $this->errorResponse('Cannot cancel this order', 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string'],
        ]);

        $radiologyOrder->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return $this->successResponse(
            new RadiologyOrderResource($radiologyOrder->fresh()),
            'Radiology order cancelled'
        );
    }

    /**
     * Delete a radiology image.
     *
     * @param Request $request
     * @param RadiologyOrder $radiologyOrder
     * @param int $imageId
     * @return JsonResponse
     */
    public function deleteImage(Request $request, RadiologyOrder $radiologyOrder, int $imageId): JsonResponse
    {
        $image = $radiologyOrder->images()->findOrFail($imageId);

        try {
            Storage::disk('private')->delete($image->file_path);
            $image->delete();

            return $this->successResponse(null, 'Image deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique radiology order number.
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'RAD';
        $date = date('Ymd');
        $lastOrder = RadiologyOrder::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? ((int) substr($lastOrder->order_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
}
