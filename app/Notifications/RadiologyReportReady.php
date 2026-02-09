<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Clinical\RadiologyOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RadiologyReportReady extends Notification
{
    use Queueable;

    public function __construct(private readonly RadiologyOrder $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'patient_id' => $this->order->patient_id,
            'message' => 'Radiology report is ready.',
        ];
    }
}
