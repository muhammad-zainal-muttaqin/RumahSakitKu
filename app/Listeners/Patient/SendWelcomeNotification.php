<?php

declare(strict_types=1);

namespace App\Listeners\Patient;

use App\Events\Patient\PatientRegistered;
use App\Mail\PatientRegisteredMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(PatientRegistered $event): void
    {
        if ($event->patient->email) {
            Mail::to($event->patient->email)
                ->send(new PatientRegisteredMail($event->patient));
        }
    }
}
