<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\SurgeryResource;
use App\Models\Clinical\Surgery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Surgery Management API Controller.
 * 
 * Handles surgery scheduling, execution, and completion.
 */
class SurgeryController extends BaseController
{
    /**
     * Display a listing of surgeries.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Surgery::query()
            ->with(['patient', 'surgeon', 'anesthetist', 'room', 'surgeryType'])
            ->when($request->search, function ($q, $search) {
                $q->where('surgery_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->when($request->room_id, fn($q, $r) => $q->where('surgery_room_id', $r))
            ->when($request->surgeon_id, fn($q, $s) => $q->where('surgeon_id', $s))
            ->when($request->surgery_date, fn($q, $d) => $q->whereDate('scheduled_date', $d))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('scheduled_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('scheduled_date', '<=', $d));

        $surgeries = $query->latest('scheduled_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($surgeries);
    }

    /**
     * Store a newly created surgery schedule.
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
            'surgery_room_id' => ['required', 'exists:surgery_rooms,id'],
            'surgeon_id' => ['required', 'exists:users,id'],
            'anesthetist_id' => ['nullable', 'exists:users,id'],
            'surgery_type_id' => ['required', 'exists:surgery_types,id'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'estimated_duration' => ['required', 'integer', 'min:15'], // in minutes
            'priority' => ['required', 'in:elective,urgent,emergency'],
            'anesthesia_type' => ['required', 'in:local,regional,general,sedation'],
            'preoperative_diagnosis' => ['required', 'string'],
            'planned_procedure' => ['required', 'string'],
            'indication' => ['nullable', 'string'],
            'preoperative_notes' => ['nullable', 'string'],
            'special_equipment' => ['nullable', 'string'],
            'assistants' => ['nullable', 'array'],
            'assistants.*' => ['exists:users,id'],
        ]);

        try {
            DB::beginTransaction();

            $surgery = Surgery::create([
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'],
                'medical_record_id' => $validated['medical_record_id'] ?? null,
                'surgery_room_id' => $validated['surgery_room_id'],
                'surgeon_id' => $validated['surgeon_id'],
                'anesthetist_id' => $validated['anesthetist_id'] ?? null,
                'surgery_type_id' => $validated['surgery_type_id'],
                'surgery_number' => $this->generateSurgeryNumber(),
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_start_time' => $validated['scheduled_start_time'],
                'estimated_duration' => $validated['estimated_duration'],
                'priority' => $validated['priority'],
                'anesthesia_type' => $validated['anesthesia_type'],
                'status' => 'scheduled',
                'preoperative_diagnosis' => $validated['preoperative_diagnosis'],
                'planned_procedure' => $validated['planned_procedure'],
                'indication' => $validated['indication'] ?? null,
                'preoperative_notes' => $validated['preoperative_notes'] ?? null,
                'special_equipment' => $validated['special_equipment'] ?? null,
                'scheduled_by' => $request->user()->id,
            ]);

            // Attach assistants if provided
            if (!empty($validated['assistants'])) {
                $surgery->assistants()->attach($validated['assistants']);
            }

            DB::commit();

            return $this->createdResponse(
                new SurgeryResource($surgery->load(['patient', 'surgeon', 'surgeryType'])),
                'Surgery scheduled successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to schedule surgery: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified surgery.
     *
     * @param Surgery $surgery
     * @return JsonResponse
     */
    public function show(Surgery $surgery): JsonResponse
    {
        return $this->successResponse(
            new SurgeryResource($surgery->load([
                'patient',
                'surgeon',
                'anesthetist',
                'room',
                'surgeryType',
                'assistants',
                'visit',
                'medicalRecord',
            ]))
        );
    }

    /**
     * Start surgery.
     *
     * @param Request $request
     * @param Surgery $surgery
     * @return JsonResponse
     */
    public function start(Request $request, Surgery $surgery): JsonResponse
    {
        if (!in_array($surgery->status, ['scheduled', 'confirmed'])) {
            return $this->errorResponse('Surgery cannot be started', 422);
        }

        $validated = $request->validate([
            'actual_start_time' => ['nullable', 'date_format:H:i'],
            'preoperative_checklist' => ['nullable', 'array'],
            'preoperative_vitals' => ['nullable', 'array'],
        ]);

        $surgery->update([
            'status' => 'in_progress',
            'actual_start_time' => $validated['actual_start_time'] ?? now()->format('H:i'),
            'started_at' => now(),
            'started_by' => $request->user()->id,
            'preoperative_checklist' => $validated['preoperative_checklist'] ?? null,
            'preoperative_vitals' => $validated['preoperative_vitals'] ?? null,
        ]);

        return $this->successResponse(
            new SurgeryResource($surgery->fresh()->load(['patient', 'surgeon', 'room'])),
            'Surgery started successfully'
        );
    }

    /**
     * Complete surgery.
     *
     * @param Request $request
     * @param Surgery $surgery
     * @return JsonResponse
     */
    public function complete(Request $request, Surgery $surgery): JsonResponse
    {
        if ($surgery->status !== 'in_progress') {
            return $this->errorResponse('Surgery must be in progress to complete', 422);
        }

        $validated = $request->validate([
            'actual_end_time' => ['nullable', 'date_format:H:i'],
            'actual_procedure' => ['required', 'string'],
            'postoperative_diagnosis' => ['required', 'string'],
            'findings' => ['required', 'string'],
            'procedure_description' => ['required', 'string'],
            'complications' => ['nullable', 'string'],
            'blood_loss' => ['nullable', 'string'],
            'specimens_taken' => ['nullable', 'string'],
            'postoperative_instructions' => ['nullable', 'string'],
            'postoperative_vitals' => ['nullable', 'array'],
            'patient_condition' => ['required', 'in:stable,fair,critical'],
        ]);

        $surgery->update([
            'status' => 'completed',
            'actual_end_time' => $validated['actual_end_time'] ?? now()->format('H:i'),
            'completed_at' => now(),
            'completed_by' => $request->user()->id,
            'actual_procedure' => $validated['actual_procedure'],
            'postoperative_diagnosis' => $validated['postoperative_diagnosis'],
            'findings' => $validated['findings'],
            'procedure_description' => $validated['procedure_description'],
            'complications' => $validated['complications'] ?? null,
            'blood_loss' => $validated['blood_loss'] ?? null,
            'specimens_taken' => $validated['specimens_taken'] ?? null,
            'postoperative_instructions' => $validated['postoperative_instructions'] ?? null,
            'postoperative_vitals' => $validated['postoperative_vitals'] ?? null,
            'patient_condition' => $validated['patient_condition'],
        ]);

        return $this->successResponse(
            new SurgeryResource($surgery->fresh()->load(['patient', 'surgeon', 'room'])),
            'Surgery completed successfully'
        );
    }

    /**
     * Cancel surgery.
     *
     * @param Request $request
     * @param Surgery $surgery
     * @return JsonResponse
     */
    public function cancel(Request $request, Surgery $surgery): JsonResponse
    {
        if (in_array($surgery->status, ['completed', 'cancelled'])) {
            return $this->errorResponse('Cannot cancel this surgery', 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string'],
            'cancelled_at_time' => ['nullable', 'date_format:H:i'],
        ]);

        $surgery->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return $this->successResponse(
            new SurgeryResource($surgery->fresh()),
            'Surgery cancelled'
        );
    }

    /**
     * Update surgery schedule.
     *
     * @param Request $request
     * @param Surgery $surgery
     * @return JsonResponse
     */
    public function reschedule(Request $request, Surgery $surgery): JsonResponse
    {
        if (!in_array($surgery->status, ['scheduled', 'confirmed'])) {
            return $this->errorResponse('Cannot reschedule this surgery', 422);
        }

        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'scheduled_start_time' => ['required', 'date_format:H:i'],
            'surgery_room_id' => ['nullable', 'exists:surgery_rooms,id'],
            'reschedule_reason' => ['required', 'string'],
        ]);

        $surgery->update([
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_start_time' => $validated['scheduled_start_time'],
            'surgery_room_id' => $validated['surgery_room_id'] ?? $surgery->surgery_room_id,
            'rescheduled_at' => now(),
            'rescheduled_by' => $request->user()->id,
            'reschedule_reason' => $validated['reschedule_reason'],
        ]);

        return $this->successResponse(
            new SurgeryResource($surgery->fresh()->load(['patient', 'surgeon', 'room'])),
            'Surgery rescheduled successfully'
        );
    }

    /**
     * Get today's surgery schedule.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $query = Surgery::query()
            ->with(['patient', 'surgeon', 'room', 'surgeryType'])
            ->whereDate('scheduled_date', today())
            ->when($request->room_id, fn($q, $r) => $q->where('surgery_room_id', $r))
            ->when($request->status, fn($q, $s) => $q->where('status', $s));

        $surgeries = $query->orderBy('scheduled_start_time')->get();

        return $this->successResponse([
            'date' => today()->toDateString(),
            'total' => $surgeries->count(),
            'scheduled' => $surgeries->where('status', 'scheduled')->count(),
            'in_progress' => $surgeries->where('status', 'in_progress')->count(),
            'completed' => $surgeries->where('status', 'completed')->count(),
            'cancelled' => $surgeries->where('status', 'cancelled')->count(),
            'surgeries' => SurgeryResource::collection($surgeries),
        ]);
    }

    /**
     * Generate unique surgery number.
     *
     * @return string
     */
    private function generateSurgeryNumber(): string
    {
        $prefix = 'OK';
        $date = date('Ymd');
        $lastSurgery = Surgery::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastSurgery ? ((int) substr($lastSurgery->surgery_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
}
