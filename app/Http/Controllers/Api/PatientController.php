<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Patient Management API Controller.
 * 
 * Handles patient CRUD operations and related data retrieval.
 */
class PatientController extends BaseController
{
    /**
     * Display a listing of patients.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query()
            ->with(['region', 'insurance'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('medical_record_number', 'like', "%{$search}%")
                        ->orWhere('bpjs_number', 'like', "%{$search}%");
                });
            })
            ->when($request->gender, fn($q, $g) => $q->where('gender', $g))
            ->when($request->blood_type, fn($q, $b) => $q->where('blood_type', $b))
            ->when($request->is_active !== null, fn($q, $a) => $q->where('is_active', $a));

        $patients = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginateResponse($patients);
    }

    /**
     * Store a newly created patient.
     *
     * @param StorePatientRequest $request
     * @return JsonResponse
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Generate medical record number if not provided
        if (empty($data['medical_record_number'])) {
            $data['medical_record_number'] = $this->generateMedicalRecordNumber();
        }

        $patient = Patient::create($data);

        return $this->createdResponse(
            new PatientResource($patient->load(['region', 'insurance'])),
            'Patient created successfully'
        );
    }

    /**
     * Display the specified patient.
     *
     * @param Patient $patient
     * @return JsonResponse
     */
    public function show(Patient $patient): JsonResponse
    {
        return $this->successResponse(
            new PatientResource($patient->load([
                'region',
                'insurance',
                'visits' => fn($q) => $q->latest()->limit(10),
            ]))
        );
    }

    /**
     * Update the specified patient.
     *
     * @param StorePatientRequest $request
     * @param Patient $patient
     * @return JsonResponse
     */
    public function update(StorePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return $this->successResponse(
            new PatientResource($patient->fresh()->load(['region', 'insurance'])),
            'Patient updated successfully'
        );
    }

    /**
     * Search patients by NIK, medical record number, or name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:3'],
            'type' => ['nullable', 'in:all,nik,mrn,name,bpjs'],
        ]);

        $query = $request->query;
        $type = $request->type ?? 'all';

        $patients = Patient::query()
            ->with(['region', 'insurance'])
            ->where(function ($q) use ($query, $type) {
                switch ($type) {
                    case 'nik':
                        $q->where('nik', 'like', "%{$query}%");
                        break;
                    case 'mrn':
                        $q->where('medical_record_number', 'like', "%{$query}%");
                        break;
                    case 'name':
                        $q->where('name', 'like', "%{$query}%");
                        break;
                    case 'bpjs':
                        $q->where('bpjs_number', 'like', "%{$query}%");
                        break;
                    default:
                        $q->where('name', 'like', "%{$query}%")
                            ->orWhere('nik', 'like', "%{$query}%")
                            ->orWhere('medical_record_number', 'like', "%{$query}%")
                            ->orWhere('bpjs_number', 'like', "%{$query}%");
                }
            })
            ->limit(20)
            ->get();

        return $this->successResponse([
            'results' => PatientResource::collection($patients),
            'count' => $patients->count(),
        ]);
    }

    /**
     * Get patient visits history.
     *
     * @param Request $request
     * @param Patient $patient
     * @return JsonResponse
     */
    public function visits(Request $request, Patient $patient): JsonResponse
    {
        $visits = $patient->visits()
            ->with(['doctor', 'clinic', 'visitType'])
            ->when($request->from_date, fn($q, $d) => $q->whereDate('visit_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('visit_date', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginateResponse($visits);
    }

    /**
     * Get patient medical records.
     *
     * @param Request $request
     * @param Patient $patient
     * @return JsonResponse
     */
    public function medicalRecords(Request $request, Patient $patient): JsonResponse
    {
        $records = $patient->medicalRecords()
            ->with(['visit', 'doctor', 'icd10'])
            ->when($request->from_date, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginateResponse($records);
    }

    /**
     * Soft delete a patient.
     *
     * @param Patient $patient
     * @return JsonResponse
     */
    public function destroy(Patient $patient): JsonResponse
    {
        // Check if patient has active visits
        if ($patient->visits()->whereIn('status', ['registered', 'in_progress'])->exists()) {
            return $this->errorResponse(
                'Cannot delete patient with active visits',
                422
            );
        }

        $patient->delete();

        return $this->successResponse(null, 'Patient deleted successfully');
    }

    /**
     * Generate unique medical record number.
     *
     * @return string
     */
    private function generateMedicalRecordNumber(): string
    {
        $prefix = 'RM';
        $year = date('Y');
        $lastPatient = Patient::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPatient ? ((int) substr($lastPatient->medical_record_number, -6) + 1) : 1;

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }
}
