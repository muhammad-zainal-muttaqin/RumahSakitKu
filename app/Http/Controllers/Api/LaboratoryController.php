<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\LabOrderResource;
use App\Models\Clinical\LaboratoryOrder as LabOrder;
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
            ->with(['patient', 'doctor', 'visit', 'results.labTest'])
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
                'doctor_id' => $request->user()->employee_id,
                'order_number' => $this->generateOrderNumber(),
                'order_date' => now(),
                'priority' => $validated['priority'],
                'status' => 'pending',
                'diagnosis_notes' => $validated['clinical_diagnosis'] ?? null,
                'clinical_notes' => $validated['notes'] ?? null,
                'is_cito' => $validated['priority'] === 'cito',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $order->results()->create([
                    'lab_test_id' => $item['lab_test_id'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            return $this->createdResponse(
                new LabOrderResource($order->load(['patient', 'doctor', 'visit', 'results.labTest'])),
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
     * @param LabOrder $order
     * @return JsonResponse
     */
    public function show(LabOrder $order): JsonResponse
    {
        return $this->successResponse(
            new LabOrderResource($order->load([
                'patient',
                'doctor',
                'visit',
                'medicalRecord',
                'results.labTest',
                'results.validatedBy',
            ]))
        );
    }

    /**
     * Update lab order results.
     *
     * @param Request $request
     * @param LabOrder $order
     * @return JsonResponse
     */
    public function results(Request $request, LabOrder $order): JsonResponse
    {
        if (!in_array($order->status, ['pending', 'in_progress'])) {
            return $this->errorResponse('Cannot update results for this order', 422);
        }

        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1'],
            'results.*.item_id' => ['required', 'exists:laboratory_results,id'],
            'results.*.value' => ['required', 'string'],
            'results.*.reference_range' => ['nullable', 'string'],
            'results.*.unit' => ['nullable', 'string'],
            'results.*.flag' => ['nullable', 'in:normal,low,high,critical'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['results'] as $result) {
                $item = $order->results()->findOrFail($result['item_id']);
                $value = (string) $result['value'];
                $isNumeric = is_numeric($value);

                $item->update([
                    'result_value' => $isNumeric ? (float) $value : null,
                    'result_text' => $isNumeric ? null : $value,
                    'reference_range' => $result['reference_range'] ?? null,
                    'unit' => $result['unit'] ?? null,
                    'flag' => $result['flag'] ?? 'normal',
                    'notes' => $result['notes'] ?? null,
                    'updated_by' => $request->user()->id,
                ]);
            }

            // Update order status if all results have values
            $allCompleted = $order->results()
                ->whereNull('result_value')
                ->whereNull('result_text')
                ->doesntExist();
            if ($allCompleted) {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_by' => $request->user()->id,
                ]);
            } else {
                $order->update([
                    'status' => 'in_progress',
                    'updated_by' => $request->user()->id,
                ]);
            }

            DB::commit();

            return $this->successResponse(
                new LabOrderResource($order->fresh()->load(['patient', 'doctor', 'visit', 'results.labTest', 'results.validatedBy'])),
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
     * @param LabOrder $order
     * @return JsonResponse
     */
    public function validateOrder(Request $request, LabOrder $order): JsonResponse
    {
        if ($order->status !== 'completed') {
            return $this->errorResponse('Order must be completed before validation', 422);
        }

        $order->results()
            ->whereNull('validated_at')
            ->update([
                'validated_by' => $request->user()->employee_id,
                'validated_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

        $order->update([
            'status' => 'validated',
            'updated_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new LabOrderResource($order->fresh()->load(['patient', 'doctor', 'results.labTest', 'results.validatedBy'])),
            'Lab order validated successfully'
        );
    }

    /**
     * Cancel a lab order.
     *
     * @param Request $request
     * @param LabOrder $order
     * @return JsonResponse
     */
    public function cancel(Request $request, LabOrder $order): JsonResponse
    {
        if (in_array($order->status, ['completed', 'validated', 'cancelled'])) {
            return $this->errorResponse('Cannot cancel this order', 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string'],
        ]);

        $order->update([
            'status' => 'cancelled',
            'clinical_notes' => trim((string) ($order->clinical_notes . "\n[DIBATALKAN] " . $validated['cancellation_reason'])),
            'updated_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new LabOrderResource($order->fresh()),
            'Lab order cancelled'
        );
    }

    /**
     * Print lab order results.
     *
     * @param Request $request
     * @param LabOrder $order
     * @return JsonResponse
     */
    public function print(Request $request, LabOrder $order): JsonResponse
    {
        if ($order->status !== 'validated') {
            return $this->errorResponse('Results must be validated before printing', 422);
        }

        // This would typically return a PDF URL or stream
        // For now, return the data that would be printed
        return $this->successResponse([
            'order' => new LabOrderResource($order->load([
                'patient',
                'doctor',
                'visit',
                'results.labTest',
                'results.validatedBy',
            ])),
            'print_url' => url("/api/lab/orders/{$order->id}/print-pdf"),
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
