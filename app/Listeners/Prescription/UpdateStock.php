<?php

declare(strict_types=1);

namespace App\Listeners\Prescription;

use App\Events\Pharmacy\LowStockAlert;
use App\Events\Prescription\PrescriptionDispensed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStock implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(PrescriptionDispensed $event): void
    {
        $prescription = $event->prescription;
        
        // Update stock for each prescription item
        foreach ($prescription->items as $item) {
            $medicine = $item->medicine;
            
            if ($medicine) {
                $medicine->decrement('stock', $item->quantity);
                
                // Check for low stock
                if ($medicine->stock <= $medicine->min_stock) {
                    // Trigger low stock alert
                    event(new LowStockAlert($medicine));
                }
            }
        }
        
        // Log stock update
        activity()
            ->performedOn($prescription)
            ->log('prescription_stock_deducted');
    }
}
