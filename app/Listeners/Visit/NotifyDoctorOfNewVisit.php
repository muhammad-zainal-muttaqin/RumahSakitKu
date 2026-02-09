<?php

declare(strict_types=1);

namespace App\Listeners\Visit;

use App\Events\Visit\VisitCreated;
use App\Notifications\NewVisitNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDoctorOfNewVisit implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(VisitCreated $event): void
    {
        $doctor = $event->visit->doctor;
        
        if ($doctor) {
            $doctor->notify(new NewVisitNotification($event->visit));
        }
    }
}
