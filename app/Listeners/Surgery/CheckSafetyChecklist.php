<?php

declare(strict_types=1);

namespace App\Listeners\Surgery;

use RuntimeException;
use App\Events\Surgery\SurgeryStarted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckSafetyChecklist implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(SurgeryStarted $event): void
    {
        $surgery = $event->surgery;
        $checklist = $surgery->safetyChecklist;
        
        if (!$checklist) {
            throw new RuntimeException('Safety checklist not found for surgery: ' . $surgery->id);
        }
        
        // Verify all required checklist items
        $requiredItems = [
            'patient_identity_verified',
            'surgical_site_marked',
            'anesthesia_safety_check',
            'allergies_checked',
            'equipment_available',
        ];
        
        foreach ($requiredItems as $item) {
            if (!$checklist->$item) {
                throw new RuntimeException(
                    "Safety checklist incomplete: {$item} not verified for surgery: {$surgery->id}"
                );
            }
        }
        
        // Mark checklist as verified
        $checklist->update([
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
        
        // Log verification
        activity()
            ->performedOn($surgery)
            ->log('surgery_safety_checklist_verified');
    }
}
