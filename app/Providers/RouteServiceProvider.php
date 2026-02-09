<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * Route Service Provider.
 *
 * This service provider handles the registration and configuration
 * of application routes, including API route loading and rate limiting.
 *
 * @package App\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function (): void {
            // API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web Routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        // Configure route patterns for better security
        $this->configureRoutePatterns();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($this->getRateLimitKey($request));
        });

        // Authentication rate limit (more restrictive)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($this->getRateLimitKey($request));
        });

        // BPJS integration rate limit
        RateLimiter::for('bpjs', function (Request $request) {
            return Limit::perMinute(30)->by($this->getRateLimitKey($request));
        });

        // Export operations rate limit
        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(10)->by($this->getRateLimitKey($request));
        });

        // Import operations rate limit
        RateLimiter::for('imports', function (Request $request) {
            return Limit::perMinute(5)->by($this->getRateLimitKey($request));
        });

        // Reports rate limit
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(20)->by($this->getRateLimitKey($request));
        });
    }

    /**
     * Get the rate limit key for the request.
     *
     * Uses user ID if authenticated, otherwise uses IP address.
     *
     * @param Request $request
     * @return string
     */
    protected function getRateLimitKey(Request $request): string
    {
        if ($request->user()) {
            return (string) $request->user()->id;
        }

        return $request->ip() ?? 'unknown';
    }

    /**
     * Configure route patterns for parameter validation.
     *
     * @return void
     */
    protected function configureRoutePatterns(): void
    {
        // Numeric IDs only
        Route::pattern('id', '[0-9]+');
        Route::pattern('patient', '[0-9]+');
        Route::pattern('visit', '[0-9]+');
        Route::pattern('record', '[0-9]+');
        Route::pattern('prescription', '[0-9]+');
        Route::pattern('invoice', '[0-9]+');
        Route::pattern('queue', '[0-9]+');
        Route::pattern('room', '[0-9]+');
        Route::pattern('inpatient', '[0-9]+');
        Route::pattern('order', '[0-9]+');
        Route::pattern('surgery', '[0-9]+');

        // Date patterns
        Route::pattern('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

        // BPJS patterns
        Route::pattern('nik', '[0-9]{16}');
        Route::pattern('sepNumber', '[A-Z0-9-]+');
        Route::pattern('noRujukan', '[0-9]+');
    }
}
