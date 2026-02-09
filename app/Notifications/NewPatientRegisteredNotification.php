<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Patient\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Real-time notification sent to registration staff when new patient is registered.
 * Channels: database, broadcast
 */
class NewPatientRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Patient $patient,
        public ?int $registeredBy = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_patient_registered',
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'medical_record_number' => $this->patient->medical_record_number,
            'nik' => $this->patient->nik,
            'insurance_type' => $this->patient->insurance_type,
            'registered_at' => $this->patient->registered_at?->toDateTimeString(),
            'registered_by' => $this->registeredBy,
            'message' => "Pasien baru terdaftar: {$this->patient->name} ({$this->patient->medical_record_number})",
            'action_url' => '/admin/patients/' . $this->patient->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'new_patient_registered',
            'notification_id' => $this->id,
            'patient_id' => $this->patient->id,
            'patient_name' => $this->patient->name,
            'medical_record_number' => $this->patient->medical_record_number,
            'insurance_type' => $this->patient->insurance_type,
            'registered_at' => $this->patient->registered_at?->toDateTimeString(),
            'message' => "Pasien baru terdaftar: {$this->patient->name}",
            'sound' => 'notification',
        ]);
    }
}
