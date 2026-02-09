<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for API Key authentication.
 * 
 * Validates API key from request header and checks rate limits.
 */
class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'API Key is required',
            ], 401);
        }

        // Validate API key
        $apiKeyData = $this->validateApiKey($apiKey);

        if (!$apiKeyData) {
            return response()->json([
                'success' => false,
                'code' => 401,
                'message' => 'Invalid API Key',
            ], 401);
        }

        // Check if API key is active
        if (!$apiKeyData['is_active']) {
            return response()->json([
                'success' => false,
                'code' => 403,
                'message' => 'API Key is deactivated',
            ], 403);
        }

        // Check rate limit
        if (!$this->checkRateLimit($apiKey, $apiKeyData['rate_limit'] ?? 100)) {
            return response()->json([
                'success' => false,
                'code' => 429,
                'message' => 'Rate limit exceeded. Please try again later.',
            ], 429);
        }

        // Set API key data to request for use in controllers
        $request->attributes->add(['api_key_data' => $apiKeyData]);

        // Log API usage
        $this->logApiUsage($apiKey, $request);

        return $next($request);
    }

    /**
     * Validate API key.
     *
     * @param string $apiKey
     * @return array|null
     */
    private function validateApiKey(string $apiKey): ?array
    {
        // Check in database or cache
        // This is a simplified version - implement according to your API key storage
        $cacheKey = "api_key:{$apiKey}";

        $apiKeyData = Cache::remember($cacheKey, 300, function () use ($apiKey) {
            // Query database for API key
            // Example: return ApiKey::where('key', $apiKey)->first()?->toArray();
            
            // For demonstration, using a simple check
            // In production, use proper database lookup
            if ($apiKey === config('app.api_key')) {
                return [
                    'key' => $apiKey,
                    'name' => 'Default API Key',
                    'is_active' => true,
                    'rate_limit' => config('app.api_rate_limit', 100),
                    'permissions' => ['*'],
                ];
            }

            return null;
        });

        return $apiKeyData;
    }

    /**
     * Check rate limit for API key.
     *
     * @param string $apiKey
     * @param int $limit
     * @return bool
     */
    private function checkRateLimit(string $apiKey, int $limit): bool
    {
        $rateLimitKey = "api_rate_limit:{$apiKey}:" . now()->format('Y-m-d-H');
        
        $current = Cache::get($rateLimitKey, 0);
        
        if ($current >= $limit) {
            return false;
        }

        Cache::increment($rateLimitKey);
        Cache::put($rateLimitKey, $current + 1, 3600); // 1 hour

        return true;
    }

    /**
     * Log API usage.
     *
     * @param string $apiKey
     * @param Request $request
     * @return void
     */
    private function logApiUsage(string $apiKey, Request $request): void
    {
        // Log to database or external service
        // Example:
        // ApiLog::create([
        //     'api_key' => $apiKey,
        //     'method' => $request->method(),
        //     'endpoint' => $request->path(),
        //     'ip_address' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        //     'requested_at' => now(),
        // ]);
    }
}
