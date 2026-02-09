<?php

declare(strict_types=1);

namespace App\Listeners\Prescription;

use App\Models\User;
use App\Events\Prescription\PrescriptionCreated;
use App\Notifications\NewPrescriptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyPharmacy implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(PrescriptionCreated $event): void
    {
        // Get pharmacy staff
        $pharmacyStaff = User::role('pharmacy')->get();
        
        // Notify pharmacy staff
        Notification::send($pharmacyStaff, new NewPrescriptionNotification($event->prescription));
        
        // Update prescription status to notified
        $event->prescription->updateQuietly([
            'pharmacy_notified_at' => now(),
        ]);
    }
}
