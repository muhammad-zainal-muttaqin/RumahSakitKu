<?php

declare(strict_types=1);

namespace App\Listeners\Surgery;

use App\Events\Surgery\SurgeryScheduled;
use App\Notifications\SurgeryScheduledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySurgeryTeam implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(SurgeryScheduled $event): void
    {
        $surgery = $event->surgery;
        
        // Notify lead surgeon
        if ($surgery->surgeon) {
            $surgery->surgeon->notify(new SurgeryScheduledNotification($surgery));
        }
        
        // Notify anesthesiologist
        if ($surgery->anesthesiologist) {
            $surgery->anesthesiologist->notify(new SurgeryScheduledNotification($surgery));
        }
        
        // Notify surgical nurses
        foreach ($surgery->nurses as $nurse) {
            $nurse->notify(new SurgeryScheduledNotification($surgery));
        }
        
        // Log notification
        activity()
            ->performedOn($surgery)
            ->log('surgery_team_notified');
    }
}
