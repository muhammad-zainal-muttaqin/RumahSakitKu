<?php

declare(strict_types=1);

namespace App\Listeners\Inpatient;

use App\Events\Inpatient\PatientAdmitted;
use App\Events\Inpatient\PatientDischarged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateBedOccupancy implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(PatientAdmitted|PatientDischarged $event): void
    {
        if ($event instanceof PatientAdmitted) {
            $this->handleAdmission($event);
        } else {
            $this->handleDischarge($event);
        }
    }
    
    private function handleAdmission(PatientAdmitted $event): void
    {
        $bed = $event->bed;
        $room = $event->room;
        
        // Mark bed as occupied
        $bed->update(['status' => 'occupied']);
        
        // Update room occupancy count
        $room->increment('occupied_beds');
        
        // Clear room statistics cache
        cache()->forget("room.stats.{$room->id}");
        
        // Log occupancy change
        activity()
            ->performedOn($bed)
            ->withProperties([
                'room_id' => $room->id,
                'patient_id' => $event->visit->patient_id,
            ])
            ->log('bed_occupied');
    }
    
    private function handleDischarge(PatientDischarged $event): void
    {
        $visit = $event->visit;
        $inpatient = $visit->inpatient;
        
        if ($inpatient) {
            $bed = $inpatient->bed;
            $room = $inpatient->room;
            
            // Mark bed as available
            $bed?->update(['status' => 'available']);
            
            // Update room occupancy count
            $room?->decrement('occupied_beds');
            
            // Clear caches
            cache()->forget("room.stats.{$room?->id}");
            
            // Log occupancy change
            activity()
                ->performedOn($bed)
                ->withProperties([
                    'room_id' => $room?->id,
                    'patient_id' => $visit->patient_id,
                ])
                ->log('bed_vacated');
        }
    }
}
