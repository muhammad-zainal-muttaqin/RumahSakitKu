<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Clinical\Surgery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to surgery team when surgery is scheduled.
 * Channels: database, mail
 */
class SurgeryScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Surgery $surgery,
        public ?string $notes = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $patient = $this->surgery->patient;
        $surgeon = $this->surgery->surgeon;
        $room = $this->surgery->operating_room;

        $scheduledDate = $this->surgery->scheduled_date?->format('d/m/Y');
        $startTime = $this->surgery->start_time?->format('H:i');

        $message = (new MailMessage)
            ->subject('Jadwal Operasi Baru - ' . $this->surgery->surgery_number)
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Jadwal operasi baru telah ditambahkan:')
            ->line('')
            ->line('**Nomor Operasi:** ' . $this->surgery->surgery_number)
            ->line('**Pasien:** ' . ($patient?->name ?? '-'))
            ->line('**Tanggal:** ' . ($scheduledDate ?? '-'))
            ->line('**Waktu:** ' . ($startTime ?? '-'))
            ->line('**Ruang Operasi:** ' . ($room ?? '-'))
            ->line('**Dokter:** ' . ($surgeon?->full_name_with_title ?? '-'))
            ->line('**Tindakan:** ' . ($this->surgery->procedure_name ?? '-'))
            ->line('**Tipe:** ' . ($this->surgery->surgery_type_label ?? '-'));

        if ($this->notes) {
            $message->line('')
                ->line('**Catatan:** ' . $this->notes);
        }

        return $message
            ->action('Lihat Detail Operasi', url('/admin/surgeries/' . $this->surgery->id))
            ->line('Mohon pastikan kesiapan tim dan peralatan operasi.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'surgery_scheduled',
            'surgery_id' => $this->surgery->id,
            'surgery_number' => $this->surgery->surgery_number,
            'patient_id' => $this->surgery->patient_id,
            'patient_name' => $this->surgery->patient?->name,
            'surgeon_id' => $this->surgery->surgeon_id,
            'surgeon_name' => $this->surgery->surgeon?->full_name_with_title,
            'scheduled_date' => $this->surgery->scheduled_date?->toDateString(),
            'start_time' => $this->surgery->start_time?->format('H:i'),
            'operating_room' => $this->surgery->operating_room,
            'procedure_name' => $this->surgery->procedure_name,
            'surgery_type' => $this->surgery->surgery_type,
            'surgery_type_label' => $this->surgery->surgery_type_label,
            'priority' => $this->surgery->surgery_type === 'emergency' || $this->surgery->surgery_type === 'cito' ? 'high' : 'normal',
            'message' => "Jadwal operasi {$this->surgery->surgery_number} - {$this->surgery->patient?->name}",
            'action_url' => '/admin/surgeries/' . $this->surgery->id,
        ];
    }
}
