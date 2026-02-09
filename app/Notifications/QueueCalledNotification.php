<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Patient\VisitQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when patient's queue is called.
 * Channels: database, broadcast, sms (optional)
 */
class QueueCalledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public VisitQueue $queue,
        public ?string $counterLocation = null
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Add SMS channel if phone number exists and SMS is enabled
        if ($notifiable->phone && config('services.sms.enabled')) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $polyclinicName = $this->queue->polyclinic?->name ?? 'Poliklinik';
        $queueNumber = $this->queue->display_number;
        $counterNumber = $this->queue->counter_number ?? '-';

        return (new MailMessage)
            ->subject('Antrian Anda Dipanggil')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line("Nomor antrian **{$queueNumber}** silakan menuju {$polyclinicName}")
            ->line("Loket: **{$counterNumber}**")
            ->line('Mohon segera datang ke loket yang dituju.')
            ->action('Lihat Detail Antrian', url('/queue/status/' . $this->queue->id))
            ->line('Terima kasih telah menggunakan layanan kami.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'queue_called',
            'queue_id' => $this->queue->id,
            'visit_id' => $this->queue->visit_id,
            'queue_number' => $this->queue->display_number,
            'polyclinic_id' => $this->queue->polyclinic_id,
            'polyclinic_name' => $this->queue->polyclinic?->name,
            'counter_number' => $this->queue->counter_number,
            'called_at' => $this->queue->called_at?->toDateTimeString(),
            'waiting_time' => $this->queue->waiting_time,
            'message' => "Nomor antrian {$this->queue->display_number} dipanggil ke loket {$this->queue->counter_number}",
            'action_url' => '/queue/status/' . $this->queue->id,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'queue_called',
            'notification_id' => $this->id,
            'queue_id' => $this->queue->id,
            'queue_number' => $this->queue->display_number,
            'polyclinic_name' => $this->queue->polyclinic?->name,
            'counter_number' => $this->queue->counter_number,
            'called_at' => $this->queue->called_at?->toDateTimeString(),
            'message' => "Nomor antrian {$this->queue->display_number} dipanggil",
            'sound' => 'notification',
        ]);
    }

    /**
     * Send SMS notification.
     */
    public function toSms(object $notifiable): string
    {
        $polyclinicName = $this->queue->polyclinic?->name ?? 'Poliklinik';
        $queueNumber = $this->queue->display_number;
        $counterNumber = $this->queue->counter_number ?? '-';

        return "RumahSakitKu: Nomor antrian {$queueNumber} silakan menuju {$polyclinicName}, Loket {$counterNumber}. Terima kasih.";
    }
}
