<?php

namespace App\Providers;

use App\Models\Patient\Patient;
use App\Policies\PatientPolicy;
use App\Models\Patient\Visit;
use App\Policies\VisitPolicy;
use App\Models\Clinical\MedicalRecord;
use App\Policies\MedicalRecordPolicy;
use App\Models\Clinical\Prescription;
use App\Policies\PrescriptionPolicy;
use App\Models\Clinical\LaboratoryResult;
use App\Policies\LaboratoryResultPolicy;
use App\Models\Financial\Invoice;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Patient::class => PatientPolicy::class,
        Visit::class => VisitPolicy::class,
        MedicalRecord::class => MedicalRecordPolicy::class,
        Prescription::class => PrescriptionPolicy::class,
        LaboratoryResult::class => LaboratoryResultPolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('patient-data-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'pendaftaran',
                'dokter_umum',
                'dokter_spesialis',
                'perawat',
                'kasir',
                'farmasi',
                'laboratorium',
                'manajemen',
            ]);
        });

        Gate::define('bpjs-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'pendaftaran',
                'kasir',
                'manajemen',
            ]);
        });

        Gate::define('medical-record-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'dokter_umum',
                'dokter_spesialis',
                'perawat',
                'manajemen',
            ]);
        });

        Gate::define('pharmacy-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'farmasi',
                'manajemen',
            ]);
        });

        Gate::define('laboratory-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'laboratorium',
                'manajemen',
            ]);
        });

        Gate::define('billing-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'kasir',
                'manajemen',
            ]);
        });

        Gate::define('admin-access', function ($user) {
            return $user->hasAnyRole([
                'super_admin',
                'manajemen',
            ]);
        });
    }
}
