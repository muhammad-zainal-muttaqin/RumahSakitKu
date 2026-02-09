<?php

declare(strict_types=1);

namespace App\Listeners\Bpjs;

use App\Events\Visit\VisitCreated;
use App\Jobs\Bpjs\SyncVisitToBpjsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncToBpjsOnVisit implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(VisitCreated $event): void
    {
        $visit = $event->visit;
        $patient = $visit->patient;
        
        // Only sync if patient is BPJS participant
        if ($patient && $patient->insurance_type === 'bpjs' && $patient->bpjs_number) {
            // Dispatch job to sync with BPJS
            SyncVisitToBpjsJob::dispatch($visit);
            
            // Log sync initiation
            activity()
                ->performedOn($visit)
                ->withProperties([
                    'bpjs_number' => $patient->bpjs_number,
                    'sync_type' => 'visit_creation',
                ])
                ->log('bpjs_sync_initiated');
        }
    }
}
