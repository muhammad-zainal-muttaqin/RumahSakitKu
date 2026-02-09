<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

/**
 * Telescope Service Provider
 * 
 * Registers Telescope for local development monitoring.
 * Configures access gates and data pruning.
 */
class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local', 'development', 'testing');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            // In local, record everything
            if ($isLocal) {
                return true;
            }

            // In production, only record errors and specific events
            return $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $this->isCriticalOperation($entry);
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
            'authorization',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // Allow local and development environments
            if (in_array($this->app->environment(), ['local', 'development', 'testing'])) {
                return true;
            }

            // Only admins can access in production
            return $user->hasRole('admin') || $user->hasRole('super_admin');
        });
    }

    /**
     * Check if entry is a critical operation.
     */
    private function isCriticalOperation(IncomingEntry $entry): bool
    {
        $criticalPatterns = config('telescope.simrs.critical_operations', []);
        
        if ($entry->type === 'event') {
            foreach ($criticalPatterns as $pattern) {
                if (fnmatch($pattern, $entry->content['name'] ?? '')) {
                    return true;
                }
            }
        }

        if ($entry->type === 'job') {
            foreach ($criticalPatterns as $pattern) {
                if (fnmatch($pattern, $entry->content['name'] ?? '')) {
                    return true;
                }
            }
        }

        return false;
    }
}
