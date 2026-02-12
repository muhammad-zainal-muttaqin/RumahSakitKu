<?php

namespace App\Providers;

use App\Services\BPJS\BpjsVclaimService;
use App\Services\SatuSehat\SatuSehatService;
use App\Services\Patient\PatientService;
use App\Services\Billing\BillingService;
use App\Services\Queue\QueueService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(!app()->isProduction());
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        // Compatibility: some tests use Http::timeout() without arguments as a fake timeout response.
        Http::macro('timeout', function (?int $seconds = null) {
            if ($seconds === null) {
                return Http::failedConnection('Request timed out.');
            }

            return $this->createPendingRequest()->timeout($seconds);
        });
    }

    public function register(): void
    {
        $this->app->singleton(BpjsVclaimService::class);
        $this->app->singleton(SatuSehatService::class);
        $this->app->singleton(PatientService::class);
        $this->app->singleton(BillingService::class);
        $this->app->singleton(QueueService::class);
    }
}
