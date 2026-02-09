<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 * Log User Activity Middleware
 * 
 * Middleware for logging user activities.
 * Tracks user actions for audit trails.
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

class LogUserActivity
{
    /**
     * Routes to exclude from logging.
     *
     * @var array<string>
     */
    protected array $excludedRoutes = [
        'livewire/*',
        'admin/livewire/*',
        '*.hot-update.js',
        '*.hot-update.json',
        'horizon/*',
        'telescope/*',
        'sanctum/*',
        '_debugbar/*',
    ];

    /**
     * HTTP methods to log.
     *
     * @var array<string>
     */
    protected array $loggedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

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
            $this->logActivity($request, $response);
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
        // Check if method should be logged
        if (!in_array($request->method(), $this->loggedMethods, true)) {
            return false;
        }

        // Check excluded routes
        foreach ($this->excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        // Skip if user is not authenticated (optional - can be removed if you want to log guest activities)
        if (!Auth::check()) {
            return false;
        }

        return true;
    }

    /**
     * Log user activity.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logActivity(Request $request, Response $response): void
    {
        $user = Auth::user();
        $action = $this->determineAction($request);

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles?->pluck('name')->toArray() ?? [],
            'action' => $action,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $this->sanitizePayload($request->all()),
            'response_status' => $response->getStatusCode(),
        ];

        // Store to database if AuditLog model exists
        $this->storeToDatabase($logData);

        // Log to file
        Log::channel('audit')->info('User activity', $logData);
    }

    /**
     * Determine action from request.
     *
     * @param Request $request
     * @return string
     */
    protected function determineAction(Request $request): string
    {
        $path = $request->path();
        $method = $request->method();

        // Check route name first
        $routeName = $request->route()?->getName() ?? '';

        if (str_contains($routeName, 'create') || str_contains($routeName, 'store')) {
            return 'CREATE';
        }

        if (str_contains($routeName, 'edit') || str_contains($routeName, 'update')) {
            return 'UPDATE';
        }

        if (str_contains($routeName, 'destroy') || str_contains($routeName, 'delete')) {
            return 'DELETE';
        }

        // Check URL patterns
        if ($method === 'POST') {
            if (str_contains($path, 'create') || str_contains($path, 'store')) {
                return 'CREATE';
            }
            return 'STORE';
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            return 'UPDATE';
        }

        if ($method === 'DELETE') {
            return 'DELETE';
        }

        return 'ACTION';
    }

    /**
     * Sanitize payload by removing sensitive data.
     *
     * @param array $payload
     * @return array
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'secret_key',
            'private_key',
            'credit_card',
            'card_number',
            'cvv',
            'nik',
            'bpjs_number',
            'bpjs_kartu',
            'no_bpjs',
        ];

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, $sensitiveFields, true)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Store activity to database.
     *
     * @param array $data
     * @return void
     */
    protected function storeToDatabase(array $data): void
    {
        try {
            if (!class_exists('App\Models\AuditLog')) {
                return;
            }

            // Determine entity type from path
            $path = $data['path'];
            $parts = explode('/', $path);
            $entityType = $parts[1] ?? 'system';

            // Extract entity ID if available
            $entityId = null;
            foreach ($parts as $part) {
                if (is_numeric($part)) {
                    $entityId = (int) $part;
                    break;
                }
            }

            AuditLog::create([
                'user_id' => $data['user_id'],
                'user_type' => 'web',
                'event' => $data['action'],
                'auditable_type' => $entityType,
                'auditable_id' => $entityId ?? 0,
                'old_values' => null,
                'new_values' => $data['payload'],
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'url' => $data['url'],
                'created_at' => now(),
            ]);
        } catch (Exception $e) {
            // Silently fail - don't disrupt the request
            Log::channel('audit')->error('Failed to store activity log', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
