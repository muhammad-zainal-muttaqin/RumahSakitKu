<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 * Patient Data Access Middleware
 * 
 * Middleware for controlling access to patient data.
 * Enforces role-based access control for patient information.
 * 
 * @package App\Http\Middleware
 */
use Exception;
use App\Models\Patient\PatientVisit;
use App\Models\Clinical\MedicalVisit;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class PatientDataAccessMiddleware
{
    /**
     * Roles that have full patient access.
     *
     * @var array<string>
     */
    protected array $fullAccessRoles = [
        'pendaftaran',
        'admin',
        'super_admin',
        'direktur',
        'manajer',
    ];

    /**
     * Roles with limited field access.
     *
     * @var array<string>
     */
    protected array $limitedFieldRoles = [
        'kasir',
        'billing',
        'keuangan',
    ];

    /**
     * Fields visible to limited access roles.
     *
     * @var array<string>
     */
    protected array $allowedFieldsForLimited = [
        'id',
        'medical_record_number',
        'name',
        'patient_type',
        'bpjs_number',
        'bpjs_class',
        'total_bill',
        'payment_status',
        'registration_date',
        'visit_date',
        'discharge_date',
    ];

    /**
     * Fields that should be hidden from limited roles.
     *
     * @var array<string>
     */
    protected array $restrictedFields = [
        'nik',
        'address',
        'phone',
        'email',
        'birth_place',
        'birth_date',
        'gender',
        'blood_type',
        'religion',
        'marital_status',
        'occupation',
        'emergency_contact',
        'emergency_phone',
        'medical_history',
        'allergies',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized. Authentication required.',
            ], 401);
        }

        // Check if user has any role
        $userRoles = $user->roles?->pluck('name')->toArray() ?? [];

        // Full access roles can see all patients
        if ($this->hasFullAccess($userRoles)) {
            $this->trackAccess($request, $user, 'full');
            return $next($request);
        }

        // Check doctor access - can only see patients in their polyclinic
        if ($this->isDoctor($userRoles)) {
            if (!$this->canDoctorAccessPatient($request, $user)) {
                return response()->json([
                    'message' => 'Access denied. You can only access patients assigned to your polyclinic.',
                ], 403);
            }
            $this->trackAccess($request, $user, 'polyclinic_limited');
            return $next($request);
        }

        // Limited field access roles
        if ($this->hasLimitedFieldAccess($userRoles)) {
            $this->trackAccess($request, $user, 'limited_fields');

            $response = $next($request);

            // Filter response data for limited roles
            return $this->filterResponseFields($response);
        }

        // Default: deny access
        return response()->json([
            'message' => 'Access denied. Insufficient permissions to access patient data.',
        ], 403);
    }

    /**
     * Check if user has full access.
     *
     * @param array $roles
     * @return bool
     */
    protected function hasFullAccess(array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, $this->fullAccessRoles, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is a doctor.
     *
     * @param array $roles
     * @return bool
     */
    protected function isDoctor(array $roles): bool
    {
        return in_array('dokter', $roles, true)
            || in_array('doctor', $roles, true)
            || in_array('dr', $roles, true);
    }

    /**
     * Check if user has limited field access.
     *
     * @param array $roles
     * @return bool
     */
    protected function hasLimitedFieldAccess(array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, $this->limitedFieldRoles, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if doctor can access the specific patient.
     *
     * @param Request $request
     * @param mixed $user
     * @return bool
     */
    protected function canDoctorAccessPatient(Request $request, $user): bool
    {
        $patientId = $this->extractPatientId($request);

        if (!$patientId) {
            // If no specific patient, allow listing but will be filtered by polyclinic
            return true;
        }

        // Get doctor's polyclinic
        $doctorPolyclinicId = $user->polyclinic_id ?? $user->employee?->polyclinic_id ?? null;

        if (!$doctorPolyclinicId) {
            return false;
        }

        // Check if patient has active visit in doctor's polyclinic
        // This is a placeholder - actual implementation depends on your models
        try {
            $hasAccess = $this->checkPatientPolyclinicAccess($patientId, $doctorPolyclinicId);

            // Store in session for this request
            Session::put('current_patient_access', [
                'patient_id' => $patientId,
                'polyclinic_id' => $doctorPolyclinicId,
                'access_granted' => $hasAccess,
                'timestamp' => now()->toIso8601String(),
            ]);

            return $hasAccess;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if patient has access in specific polyclinic.
     *
     * @param int|string $patientId
     * @param int|string $polyclinicId
     * @return bool
     */
    protected function checkPatientPolyclinicAccess(int|string $patientId, int|string $polyclinicId): bool
    {
        // This should be implemented based on your actual model structure
        // Example: Check if patient has active visit in the polyclinic

        if (class_exists('App\Models\Patient\PatientVisit')) {
            return PatientVisit::where('patient_id', $patientId)
                ->where('polyclinic_id', $polyclinicId)
                ->whereIn('status', ['waiting', 'in_progress', 'active'])
                ->exists();
        }

        if (class_exists('App\Models\Clinical\MedicalVisit')) {
            return MedicalVisit::where('patient_id', $patientId)
                ->where('polyclinic_id', $polyclinicId)
                ->whereIn('status', ['registered', 'in_progress'])
                ->exists();
        }

        // Default deny if models don't exist
        return false;
    }

    /**
     * Extract patient ID from request.
     *
     * @param Request $request
     * @return int|string|null
     */
    protected function extractPatientId(Request $request): int|string|null
    {
        // Check route parameters
        if ($request->route('patient')) {
            return $request->route('patient');
        }

        if ($request->route('patientId')) {
            return $request->route('patientId');
        }

        // Check query parameters
        if ($request->has('patient_id')) {
            return $request->input('patient_id');
        }

        // Check request body
        if ($request->has('patient_id')) {
            return $request->input('patient_id');
        }

        // Try to extract from URL path
        $path = $request->path();
        if (preg_match('/patients?\/(\d+)/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Track access in session.
     *
     * @param Request $request
     * @param mixed $user
     * @param string $accessType
     * @return void
     */
    protected function trackAccess(Request $request, $user, string $accessType): void
    {
        $patientId = $this->extractPatientId($request);

        $accessLog = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'access_type' => $accessType,
            'patient_id' => $patientId,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Store current access in session
        Session::put('patient_data_access', $accessLog);

        // Add to access history
        $accessHistory = Session::get('patient_access_history', []);
        $accessHistory[] = $accessLog;

        // Keep only last 50 entries
        if (count($accessHistory) > 50) {
            $accessHistory = array_slice($accessHistory, -50);
        }

        Session::put('patient_access_history', $accessHistory);

        // Log the access
        Log::channel('audit')->info('Patient data access', $accessLog);
    }

    /**
     * Filter response fields for limited access roles.
     *
     * @param Response $response
     * @return Response
     */
    protected function filterResponseFields(Response $response): Response
    {
        // Only filter JSON responses
        if (!$response->headers->get('Content-Type') ||
            !str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        $content = json_decode($response->getContent(), true);

        if (!$content) {
            return $response;
        }

        // Filter data
        if (isset($content['data'])) {
            $content['data'] = $this->filterData($content['data']);
        } else {
            $content = $this->filterData($content);
        }

        // Add access restriction notice
        $content['access_restricted'] = true;
        $content['visible_fields'] = $this->allowedFieldsForLimited;

        $response->setContent(json_encode($content));

        return $response;
    }

    /**
     * Filter data to only allowed fields.
     *
     * @param array $data
     * @return array
     */
    protected function filterData(array $data): array
    {
        $filtered = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->allowedFieldsForLimited, true)) {
                $filtered[$key] = $value;
            } elseif (is_array($value)) {
                // Recursively filter nested arrays
                $filtered[$key] = $this->filterData($value);
            }
        }

        return $filtered;
    }

    /**
     * Get the allowed fields for current user.
     *
     * @return array
     */
    public static function getAllowedFields(): array
    {
        $instance = new self();
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $userRoles = $user->roles?->pluck('name')->toArray() ?? [];

        if ($instance->hasFullAccess($userRoles) || $instance->isDoctor($userRoles)) {
            return ['*']; // All fields
        }

        if ($instance->hasLimitedFieldAccess($userRoles)) {
            return $instance->allowedFieldsForLimited;
        }

        return [];
    }

    /**
     * Check if current user can access specific field.
     *
     * @param string $field
     * @return bool
     */
    public static function canAccessField(string $field): bool
    {
        $allowedFields = self::getAllowedFields();

        if (in_array('*', $allowedFields, true)) {
            return true;
        }

        return in_array($field, $allowedFields, true);
    }
}
