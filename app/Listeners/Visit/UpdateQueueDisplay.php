<?php

declare(strict_types=1);

namespace App\Listeners\Visit;

use App\Events\Visit\VisitStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Broadcast;

class UpdateQueueDisplay implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(VisitStatusChanged $event): void
    {
        // Broadcast queue update to all connected displays
        Broadcast::event($event)->toOthers();
        
        // Update queue statistics in cache
        $departmentId = $event->visit->department_id;
        
        // Invalidate queue cache for real-time updates
        cache()->forget("queue.department.{$departmentId}");
    }
}
