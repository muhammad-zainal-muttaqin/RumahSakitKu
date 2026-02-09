<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Patient\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when visit is completed.
 * Channels: database
 * Stores patient history notification.
 */
class VisitCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Visit $visit,
        public ?string $summary = null,
        public ?array $nextActions = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $patient = $this->visit->patient;
        $polyclinic = $this->visit->polyclinic;
        $doctor = $this->visit->doctor;

        return [
            'type' => 'visit_completed',
            'visit_id' => $this->visit->id,
            'visit_number' => $this->visit->visit_number,
            'patient_id' => $this->visit->patient_id,
            'patient_name' => $patient?->name,
            'polyclinic_name' => $polyclinic?->name,
            'doctor_name' => $doctor?->full_name_with_title,
            'visit_date' => $this->visit->visit_date?->toDateString(),
            'check_in_at' => $this->visit->check_in_at?->toDateTimeString(),
            'check_out_at' => $this->visit->check_out_at?->toDateTimeString(),
            'duration' => $this->visit->duration,
            'summary' => $this->summary,
            'next_actions' => $this->nextActions,
            'message' => "Kunjungan {$this->visit->visit_number} selesai",
            'action_url' => '/admin/visits/' . $this->visit->id,
        ];
    }
}
