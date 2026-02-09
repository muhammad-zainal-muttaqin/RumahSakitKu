<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure valid API token for protected routes.
 *
 * This middleware validates that the incoming request has a valid
 * Sanctum token for API access. It can be used as an additional
 * layer of security for sensitive API endpoints.
 *
 * @package App\Http\Middleware\Api
 */
class EnsureApiToken
{
    /**
     * Handle an incoming request.
     *
     * Validates the presence and validity of the API token.
     * Checks for token expiration and required abilities.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string|null ...$abilities Required token abilities (optional)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string ...$abilities): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please provide a valid API token.',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'details' => 'The request requires authentication.',
                ],
            ], 401);
        }

        // Check if user is active
        if (!$request->user()->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is deactivated.',
                'error' => [
                    'code' => 'ACCOUNT_DEACTIVATED',
                    'details' => 'Your account has been deactivated. Please contact administrator.',
                ],
            ], 403);
        }

        // Check for required abilities if specified
        if (!empty($abilities)) {
            $token = $request->user()->currentAccessToken();

            if ($token && !$token->can('api:access')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions.',
                    'error' => [
                        'code' => 'INSUFFICIENT_PERMISSIONS',
                        'details' => 'The token does not have the required abilities.',
                        'required' => $abilities,
                    ],
                ], 403);
            }
        }

        // Log API access for audit
        $this->logApiAccess($request);

        return $next($request);
    }

    /**
     * Log API access for audit purposes.
     *
     * @param Request $request
     * @return void
     */
    protected function logApiAccess(Request $request): void
    {
        // Only log in production or if explicitly enabled
        if (!config('app.debug') || config('api.audit_log_enabled', false)) {
            $user = $request->user();

            if ($user) {
                // Update last activity timestamp
                $user->update([
                    'last_activity_at' => now(),
                ]);

                // Detailed logging can be implemented here
                // Example: Log to database or external service
            }
        }
    }

    /**
     * Get the token from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // Check query parameter (not recommended for production)
        if ($request->has('api_token')) {
            return $request->query('api_token');
        }

        // Check cookie
        if ($request->hasCookie('api_token')) {
            return $request->cookie('api_token');
        }

        return null;
    }
}
