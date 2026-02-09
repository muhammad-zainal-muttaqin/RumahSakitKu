<?php

declare(strict_types=1);

namespace App\Listeners\Laboratory;

use App\Services\CriticalValueAlertService;
use App\Events\Laboratory\LabResultsEntered;
use App\Notifications\LabResultsAvailableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyDoctorOfResults implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(LabResultsEntered $event): void
    {
        $result = $event->laboratoryResult;
        $order = $result->laboratoryOrder;
        
        if ($order) {
            // Notify ordering doctor
            $doctor = $order->doctor;
            if ($doctor) {
                $doctor->notify(new LabResultsAvailableNotification($result));
            }
            
            // Update order status
            $order->update(['status' => 'completed']);
            
            // Check for critical values
            if ($this->hasCriticalValues($result)) {
                // Send urgent notification
                $this->sendCriticalValueAlert($result, $doctor);
            }
            
            // Log notification
            activity()
                ->performedOn($result)
                ->log('lab_results_notified');
        }
    }
    
    private function hasCriticalValues($result): bool
    {
        foreach ($result->details as $detail) {
            if ($detail->is_critical) {
                return true;
            }
        }
        
        return false;
    }
    
    private function sendCriticalValueAlert($result, $doctor): void
    {
        // Send immediate notification for critical values
        // This could be SMS, push notification, or direct call
        CriticalValueAlertService::send($result, $doctor);
    }
}
