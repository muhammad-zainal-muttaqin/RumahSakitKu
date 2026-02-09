<?php

declare(strict_types=1);

namespace App\Listeners\Audit;

use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogModelChanges implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /**
     * Handle the event.
     *
     * @param object $event
     */
    public function handle(object $event): void
    {
        $eventClass = class_basename($event);
        
        // Extract model from event
        $model = $this->extractModelFromEvent($event);
        
        if ($model) {
            // Log to audit trail using spatie/laravel-activitylog
            activity()
                ->performedOn($model)
                ->withProperties([
                    'event' => $eventClass,
                    'model_type' => get_class($model),
                    'model_id' => $model->id,
                ])
                ->log($this->getLogName($eventClass));
        }
    }
    
    private function extractModelFromEvent(object $event): ?object
    {
        $modelProperties = ['patient', 'visit', 'medicalRecord', 'prescription', 
                          'invoice', 'payment', 'surgery', 'laboratoryOrder', 
                          'laboratoryResult'];
        
        foreach ($modelProperties as $property) {
            if (property_exists($event, $property)) {
                return $event->$property;
            }
        }
        
        return null;
    }
    
    private function getLogName(string $eventClass): string
    {
        return 'event_' . Str::snake($eventClass);
    }
}
