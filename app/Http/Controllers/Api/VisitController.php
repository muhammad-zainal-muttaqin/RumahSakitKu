<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\VisitResource;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Visit Management API Controller.
 * 
 * Handles patient visit management including registration, status updates,
 * check-in and check-out operations.
 */
class VisitController extends BaseController
{
    /**
     * Display a listing of visits.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Visit::query()
            ->with([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name',
                'visitType:id,name'
            ])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('patient', function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('medical_record_number', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->clinic_id, fn($q, $c) => $q->where('clinic_id', $c))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->visit_date, fn($q, $d) => $q->whereDate('visit_date', $d))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('visit_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('visit_date', '<=', $d));

        $visits = $query->latest('visit_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($visits);
    }

    /**
     * Store a newly created visit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'visit_type_id' => ['required', 'exists:visit_types,id'],
            'visit_date' => ['required', 'date'],
            'complaint' => ['nullable', 'string'],
            'is_emergency' => ['boolean'],
            'is_bpjs' => ['boolean'],
            'bpjs_number' => ['nullable', 'string', 'max:20'],
            'reference_number' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::beginTransaction();

            // Generate visit number
            $validated['visit_number'] = $this->generateVisitNumber();
            $validated['status'] = 'registered';
            $validated['registered_by'] = $request->user()->id;
            $validated['registered_at'] = now();

            $visit = Visit::create($validated);

            // Create initial queue entry if needed
            if ($request->boolean('create_queue', true)) {
                $this->createQueueEntry($visit);
            }

            DB::commit();

            return $this->createdResponse(
                new VisitResource($visit->load([
                    'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                    'doctor:id,name',
                    'clinic:id,name',
                    'visitType:id,name'
                ])),
                'Visit registered successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to register visit: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified visit.
     *
     * @param Visit $visit
     * @return JsonResponse
     */
    public function show(Visit $visit): JsonResponse
    {
        return $this->successResponse(
            new VisitResource($visit->load([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name',
                'visitType:id,name',
                'medicalRecord',
                'prescriptions',
                'labOrders',
                'radiologyOrders',
            ]))
        );
    }

    /**
     * Update visit status.
     *
     * @param Request $request
     * @param Visit $visit
     * @return JsonResponse
     */
    public function updateStatus(Request $request, Visit $visit): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:registered,waiting,called,in_progress,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit->update([
            'status' => $validated['status'],
            'status_notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse(
            new VisitResource($visit->fresh()->load([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name'
            ])),
            'Visit status updated successfully'
        );
    }

    /**
     * Get today's visits.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $query = Visit::query()
            ->with([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name',
                'visitType:id,name'
            ])
            ->whereDate('visit_date', today())
            ->when($request->clinic_id, fn($q, $c) => $q->where('clinic_id', $c))
            ->when($request->doctor_id, fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->status, fn($q, $s) => $q->where('status', $s));

        $visits = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginateResponse($visits);
    }

    /**
     * Check-in a patient.
     *
     * @param Request $request
     * @param Visit $visit
     * @return JsonResponse
     */
    public function checkin(Request $request, Visit $visit): JsonResponse
    {
        if (!in_array($visit->status, ['registered', 'waiting'])) {
            return $this->errorResponse('Visit cannot be checked in', 422);
        }

        $visit->update([
            'status' => 'waiting',
            'checked_in_at' => now(),
            'checked_in_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new VisitResource($visit->fresh()->load([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name'
            ])),
            'Patient checked in successfully'
        );
    }

    /**
     * Check-out a patient.
     *
     * @param Request $request
     * @param Visit $visit
     * @return JsonResponse
     */
    public function checkout(Request $request, Visit $visit): JsonResponse
    {
        if ($visit->status !== 'in_progress') {
            return $this->errorResponse('Visit must be in progress to check out', 422);
        }

        $visit->update([
            'status' => 'completed',
            'checked_out_at' => now(),
            'checked_out_by' => $request->user()->id,
        ]);

        return $this->successResponse(
            new VisitResource($visit->fresh()->load([
                'patient:id,name,medical_record_number,nik,phone,gender,birth_date',
                'doctor:id,name',
                'clinic:id,name'
            ])),
            'Patient checked out successfully'
        );
    }

    /**
     * Cancel a visit.
     *
     * @param Request $request
     * @param Visit $visit
     * @return JsonResponse
     */
    public function cancel(Request $request, Visit $visit): JsonResponse
    {
        if (in_array($visit->status, ['completed', 'cancelled'])) {
            return $this->errorResponse('Visit cannot be cancelled', 422);
        }

        $request->validate([
            'cancellation_reason' => ['required', 'string'],
        ]);

        $visit->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
        ]);

        return $this->successResponse(
            new VisitResource($visit->fresh()),
            'Visit cancelled successfully'
        );
    }

    /**
     * Generate unique visit number.
     *
     * @return string
     */
    private function generateVisitNumber(): string
    {
        $prefix = 'VIS';
        $date = date('Ymd');
        $lastVisit = Visit::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastVisit ? ((int) substr($lastVisit->visit_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Create queue entry for visit.
     *
     * @param Visit $visit
     * @return void
     */
    private function createQueueEntry(Visit $visit): void
    {
        // Implementation depends on Queue model
        // This is a placeholder for queue creation logic
    }
}
