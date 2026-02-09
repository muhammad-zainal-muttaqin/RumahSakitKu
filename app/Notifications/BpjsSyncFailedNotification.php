<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BpjsLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alert notification sent to admin when BPJS sync fails.
 * Channels: database, mail
 */
class BpjsSyncFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public BpjsLog $bpjsLog,
        public ?string $additionalContext = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $serviceType = $this->bpjsLog->service_type;
        $endpoint = $this->bpjsLog->endpoint;
        $httpStatus = $this->bpjsLog->http_status;
        $errorMessage = $this->bpjsLog->error_message;
        $executedAt = $this->bpjsLog->executed_at?->format('d/m/Y H:i:s');

        return (new MailMessage)
            ->error()
            ->subject('Peringatan: Sinkronisasi BPJS Gagal')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Sinkronisasi dengan BPJS mengalami kegagalan:')
            ->line('')
            ->line('**Service:** ' . $serviceType)
            ->line('**Endpoint:** ' . $endpoint)
            ->line('**HTTP Status:** ' . ($httpStatus ?? 'N/A'))
            ->line('**Waktu:** ' . ($executedAt ?? '-'))
            ->line('')
            ->line('**Error Message:**')
            ->line($errorMessage ?? 'Tidak ada detail error')
            ->line('')
            ->when($this->additionalContext, function ($msg) {
                return $msg->line('**Context:** ' . $this->additionalContext);
            })
            ->action('Lihat Log BPJS', url('/admin/bpjs-logs/' . $this->bpjsLog->id))
            ->line('Segera periksa dan perbaiki masalah koneksi ke BPJS.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'bpjs_sync_failed',
            'bpjs_log_id' => $this->bpjsLog->id,
            'service_type' => $this->bpjsLog->service_type,
            'endpoint' => $this->bpjsLog->endpoint,
            'method' => $this->bpjsLog->method,
            'http_status' => $this->bpjsLog->http_status,
            'error_message' => $this->bpjsLog->error_message,
            'execution_time_ms' => $this->bpjsLog->execution_time_ms,
            'executed_at' => $this->bpjsLog->executed_at?->toDateTimeString(),
            'user_id' => $this->bpjsLog->user_id,
            'additional_context' => $this->additionalContext,
            'priority' => 'high',
            'message' => "BPJS Sync Failed: {$this->bpjsLog->service_type} - {$this->bpjsLog->endpoint}",
            'action_url' => '/admin/bpjs-logs/' . $this->bpjsLog->id,
        ];
    }
}
