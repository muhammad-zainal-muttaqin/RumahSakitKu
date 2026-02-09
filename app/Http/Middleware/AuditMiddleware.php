<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 * Audit Middleware
 * 
 * Middleware for auditing HTTP requests.
 * Logs patient-related mutations for compliance.
 * 
 * @package App\Http\Middleware
 */
use App\Models\AuditLog;
use Exception;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Routes to skip logging for GET requests.
     *
     * @var array<string>
     */
    protected array $skipGetRoutes = [
        'admin/patients',
        'admin/patients/index',
        'admin/registrations',
        'admin/visits',
        'admin/appointments',
        'admin/queue',
        'api/patients',
        'api/visits',
    ];

    /**
     * URL patterns that indicate patient-related mutations.
     *
     * @var array<string>
     */
    protected array $patientRelatedPatterns = [
        'patient',
        'registration',
        'visit',
        'appointment',
        'medical-record',
        'rekam-medis',
        'rawat',
        'pemeriksaan',
        'diagnosis',
        'treatment',
        'obat',
        'medication',
        'lab',
        'radiology',
        'billing',
        'payment',
        'bpjs',
        'satusehat',
    ];

    /**
     * Sensitive fields that should be masked in logs.
     *
     * @var array<string>
     */
    protected array $sensitiveFields = [
        'nik',
        'bpjs_number',
        'bpjs_kartu',
        'no_bpjs',
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
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
        $response = $next($request);

        if ($this->shouldLogRequest($request)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldLogRequest(Request $request): bool
    {
        // Skip GET requests on listing pages
        if ($request->isMethod('GET') && $this->isListingPage($request)) {
            return false;
        }

        // Only log patient-related mutations
        if (!$this->isPatientRelated($request)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the request is for a listing page.
     *
     * @param Request $request
     * @return bool
     */
    protected function isListingPage(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->skipGetRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        // Skip if URL ends with index or has no specific resource ID
        if (preg_match('/\/(index|list|all)$/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the request is related to patient data.
     *
     * @param Request $request
     * @return bool
     */
    protected function isPatientRelated(Request $request): bool
    {
        $path = strtolower($request->path());

        foreach ($this->patientRelatedPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request details.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logRequest(Request $request, Response $response): void
    {
        $user = Auth::user();
        $payload = $this->sanitizePayload($request->all());

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => $user?->roles?->pluck('name')->toArray() ?? [],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'action' => $this->determineAction($request),
            'payload' => $payload,
            'response_status' => $response->getStatusCode(),
            'referer' => $request->headers->get('referer'),
        ];

        // Store in audit log database if available
        $this->storeAuditLog($logData);

        // Also log to file for backup
        Log::channel('audit')->info('Audit log entry', $logData);
    }

    /**
     * Sanitize payload by masking sensitive fields.
     *
     * @param array $payload
     * @return array
     */
    protected function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (in_array($key, $this->sensitiveFields, true)) {
                $payload[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }

        return $payload;
    }

    /**
     * Mask a sensitive value.
     *
     * @param mixed $value
     * @return string
     */
    protected function maskValue(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $str = (string) $value;
        $length = strlen($str);

        if ($length <= 4) {
            return '****';
        }

        // Show first 2 and last 2 characters
        return substr($str, 0, 2) . str_repeat('*', $length - 4) . substr($str, -2);
    }

    /**
     * Determine the action type from the request.
     *
     * @param Request $request
     * @return string
     */
    protected function determineAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // Determine action based on HTTP method and route
        if ($method === 'POST') {
            if (str_contains($path, 'create') || preg_match('/\/(store|save)$/', $path)) {
                return 'CREATE';
            }
            return 'STORE';
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            if (preg_match('/\/(update|edit)\//', $path)) {
                return 'UPDATE';
            }
            return 'MODIFY';
        }

        if ($method === 'DELETE') {
            return 'DELETE';
        }

        if ($method === 'GET') {
            if (preg_match('/\/(show|view|detail)\//', $path)) {
                return 'VIEW';
            }
            return 'READ';
        }

        return strtoupper($method);
    }

    /**
     * Store audit log to database.
     *
     * @param array $data
     * @return void
     */
    protected function storeAuditLog(array $data): void
    {
        try {
            // Check if AuditLog model exists
            if (class_exists('App\Models\AuditLog')) {
                AuditLog::create([
                    'user_id' => $data['user_id'],
                    'user_name' => $data['user_name'],
                    'action' => $data['action'],
                    'entity_type' => $this->getEntityType($data['path']),
                    'entity_id' => $this->getEntityId($data['path']),
                    'ip_address' => $data['ip_address'],
                    'user_agent' => $data['user_agent'],
                    'url' => $data['url'],
                    'payload' => $data['payload'],
                    'response_status' => $data['response_status'],
                    'created_at' => $data['timestamp'],
                ]);
            }
        } catch (Exception $e) {
            // Silently fail - don't disrupt the request
            Log::channel('audit')->error('Failed to store audit log', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Get entity type from path.
     *
     * @param string $path
     * @return string|null
     */
    protected function getEntityType(string $path): ?string
    {
        $parts = explode('/', $path);
        return $parts[1] ?? null;
    }

    /**
     * Get entity ID from path.
     *
     * @param string $path
     * @return string|null
     */
    protected function getEntityId(string $path): ?string
    {
        $parts = explode('/', $path);
        // Look for numeric ID in the path
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                return $part;
            }
        }
        return null;
    }
}
