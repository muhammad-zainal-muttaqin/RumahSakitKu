<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Patient\PatientRegistered;
use App\Listeners\Patient\SendWelcomeNotification;
use App\Listeners\Audit\LogModelChanges;
use App\Events\Patient\PatientUpdated;
use App\Events\Visit\VisitCreated;
use App\Listeners\Visit\NotifyDoctorOfNewVisit;
use App\Events\Visit\VisitStatusChanged;
use App\Listeners\Visit\UpdateQueueDisplay;
use App\Events\Visit\VisitCompleted;
use App\Events\MedicalRecord\MedicalRecordFinalized;
use App\Listeners\MedicalRecord\ArchiveMedicalRecord;
use App\Events\Prescription\PrescriptionCreated;
use App\Listeners\Prescription\NotifyPharmacy;
use App\Events\Prescription\PrescriptionVerified;
use App\Events\Prescription\PrescriptionDispensed;
use App\Listeners\Prescription\UpdateStock;
use App\Events\Invoice\InvoiceCreated;
use App\Listeners\Invoice\SendInvoiceNotification;
use App\Events\Invoice\PaymentReceived;
use App\Listeners\Invoice\ProcessPaymentReconciliation;
use App\Events\Inpatient\PatientAdmitted;
use App\Listeners\Inpatient\UpdateBedOccupancy;
use App\Events\Inpatient\PatientDischarged;
use App\Events\Surgery\SurgeryScheduled;
use App\Listeners\Surgery\NotifySurgeryTeam;
use App\Events\Surgery\SurgeryStarted;
use App\Listeners\Surgery\CheckSafetyChecklist;
use App\Events\Surgery\SurgeryCompleted;
use App\Events\Laboratory\LabOrderCreated;
use App\Events\Laboratory\LabResultsEntered;
use App\Listeners\Laboratory\NotifyDoctorOfResults;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Laravel Auth Events
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Patient Events
        PatientRegistered::class => [
            SendWelcomeNotification::class,
            LogModelChanges::class,
        ],
        PatientUpdated::class => [
            LogModelChanges::class,
        ],

        // Visit Events
        VisitCreated::class => [
            NotifyDoctorOfNewVisit::class,
            LogModelChanges::class,
        ],
        VisitStatusChanged::class => [
            UpdateQueueDisplay::class,
            LogModelChanges::class,
        ],
        VisitCompleted::class => [
            LogModelChanges::class,
        ],

        // Medical Record Events
        MedicalRecordFinalized::class => [
            ArchiveMedicalRecord::class,
            LogModelChanges::class,
        ],

        // Prescription Events
        PrescriptionCreated::class => [
            NotifyPharmacy::class,
            LogModelChanges::class,
        ],
        PrescriptionVerified::class => [
            LogModelChanges::class,
        ],
        PrescriptionDispensed::class => [
            UpdateStock::class,
            LogModelChanges::class,
        ],

        // Invoice Events
        InvoiceCreated::class => [
            SendInvoiceNotification::class,
            LogModelChanges::class,
        ],
        PaymentReceived::class => [
            ProcessPaymentReconciliation::class,
            LogModelChanges::class,
        ],

        // Inpatient Events
        PatientAdmitted::class => [
            UpdateBedOccupancy::class,
            LogModelChanges::class,
        ],
        PatientDischarged::class => [
            UpdateBedOccupancy::class,
            LogModelChanges::class,
        ],

        // Surgery Events
        SurgeryScheduled::class => [
            NotifySurgeryTeam::class,
            LogModelChanges::class,
        ],
        SurgeryStarted::class => [
            CheckSafetyChecklist::class,
            LogModelChanges::class,
        ],
        SurgeryCompleted::class => [
            LogModelChanges::class,
        ],

        // Laboratory Events
        LabOrderCreated::class => [
            LogModelChanges::class,
        ],
        LabResultsEntered::class => [
            NotifyDoctorOfResults::class,
            LogModelChanges::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
