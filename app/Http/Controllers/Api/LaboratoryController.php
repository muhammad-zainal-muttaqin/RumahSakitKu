<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\LabOrderResource;
use App\Models\Clinical\LabOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Laboratory Orders API Controller.
 * 
 * Handles laboratory test orders, result entry, and validation.
 */
class LaboratoryController extends BaseController
{
    /**
     * Display a listing of lab orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = LabOrder::query()
            ->with(['patient', 'doctor', 'visit', 'items.test'])
            ->when($request->search, function ($q, $search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('order_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('order_date', '<=', $d));

        $orders = $query->latest('order_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($orders);
    }

    /**
     * Store a newly created lab order.
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
            'priority' => ['required', 'in:normal,urgent,cito'],
            'clinical_diagnosis' => ['nullable', 'string'],
            'specimen_type' => ['nullable', 'string'],
            'specimen_collection_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.lab_test_id' => ['required', 'exists:lab_tests,id'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $order = LabOrder::create([
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'],
                'medical_record_id' => $validated['medical_record_id'] ?? null,
                'doctor_id' => $request->user()->id,
                'order_number' => $this->generateOrderNumber(),
                'order_date' => now(),
                'priority' => $validated['priority'],
                'status' => 'pending',
                'clinical_diagnosis' => $validated['clinical_diagnosis'] ?? null,
                'specimen_type' => $validated['specimen_type'] ?? null,
                'specimen_collection_time' => $validated['specimen_collection_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'lab_test_id' => $item['lab_test_id'],
                    'status' => 'pending',
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return $this->createdResponse(
                new LabOrderResource($order->load(['patient', 'doctor', 'items.test'])),
                'Lab order created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create lab order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified lab order.
     *
     * @param LabOrder $labOrder
     * @return JsonResponse
     */
    public function show(LabOrder $labOrder): JsonResponse
    {
        return $this->successResponse(
            new LabOrderResource($labOrder->load([
                'patient',
                'doctor',
                'visit',
                'medicalRecord',
                'items.test',
                'items.results',
                'validator',
            ]))
        );
    }

    /**
     * Update lab order results.
     *
     * @param Request $request
     * @param LabOrder $labOrder
     * @return JsonResponse
     */
    public function results(Request $request, LabOrder $labOrder): JsonResponse
    {
        if (!in_array($labOrder->status, ['pending', 'in_progress'])) {
            return $this->errorResponse('Cannot update results for this order', 422);
        }

        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1'],
            'results.*.item_id' => ['required', 'exists:lab_order_items,id'],
            'results.*.value' => ['required', 'string'],
            'results.*.reference_range' => ['nullable', 'string'],
            'results.*.unit' => ['nullable', 'string'],
            'results.*.flag' => ['nullable', 'in:normal,low,high,critical'],
            'results.*.notes' => ['nullable', 'string'],
            'equipment_used' => ['nullable', 'string'],
            'method_used' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['results'] as $result) {
                $item = $labOrder->items()->findOrFail($result['item_id']);
                
                $item->results()->create([
                    'value' => $result['value'],
                    'reference_range' => $result['reference_range'] ?? null,
                    'unit' => $result['unit'] ?? null,
                    'flag' => $result['flag'] ?? 'normal',
                    'notes' => $result['notes'] ?? null,
                    'tested_by' => $request->user()->id,
                    'tested_at' => now(),
                ]);

                $item->update([
                    'status' => 'completed',
                    'result_value' => $result['value'],
                    'result_flag' => $result['flag'] ?? 'normal',
                ]);
            }

            // Update order status if all items have results
            $allCompleted = $labOrder->items()->where('status', '!=', 'completed')->doesntExist();
            if ($allCompleted) {
                $labOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'equipment_used' => $validated['equipment_used'] ?? null,
                    'method_used' => $validated['method_used'] ?? null,
                ]);
            } else {
                $labOrder->update([
                    'status' => 'in_progress',
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new LabOrderResource($labOrder->fresh()->load(['patient', 'items.test', 'items.results'])),
                'Results saved successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to save results: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate lab order results.
     *
     * @param Request $request
     * @param LabOrder $labOrder
     * @return JsonResponse
     */
    public function validate(Request $request, LabOrder $labOrder): JsonResponse
    {
        if ($labOrder->status !== 'completed') {
            return $this->errorResponse('Order must be completed before validation', 422);
        }

        if ($labOrder->validated_at) {
            return $this->errorResponse('Order is already validated', 422);
        }

        $validated = $request->validate([
            'validation_notes' => ['nullable', 'string'],
        ]);

        $labOrder->update([
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'validation_notes' => $validated['validation_notes'] ?? null,
        ]);

        return $this->successResponse(
            new LabOrderResource($labOrder->fresh()->load(['patient', 'validator'])),
            'Lab order validated successfully'
        );
    }

    /**
     * Cancel a lab order.
     *
     * @param Request $request
     * @param LabOrder $labOrder
     * @return JsonResponse
     */
    public function cancel(Request $request, LabOrder $labOrder): JsonResponse
    {
        if (in_array($labOrder->status, ['completed', 'validated', 'cancelled'])) {
            return $this->errorResponse('Cannot cancel this order', 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string'],
        ]);

        $labOrder->update([
            'status' => 'cancelled',
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return $this->successResponse(
            new LabOrderResource($labOrder->fresh()),
            'Lab order cancelled'
        );
    }

    /**
     * Print lab order results.
     *
     * @param Request $request
     * @param LabOrder $labOrder
     * @return JsonResponse
     */
    public function print(Request $request, LabOrder $labOrder): JsonResponse
    {
        if (!$labOrder->validated_at) {
            return $this->errorResponse('Results must be validated before printing', 422);
        }

        // This would typically return a PDF URL or stream
        // For now, return the data that would be printed
        return $this->successResponse([
            'order' => new LabOrderResource($labOrder->load([
                'patient',
                'doctor',
                'items.test',
                'items.results',
                'validator',
            ])),
            'print_url' => url("/api/lab/orders/{$labOrder->id}/print-pdf"),
        ]);
    }

    /**
     * Generate unique lab order number.
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'LAB';
        $date = date('Ymd');
        $lastOrder = LabOrder::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? ((int) substr($lastOrder->order_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
}
