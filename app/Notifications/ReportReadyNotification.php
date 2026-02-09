<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when report is generated.
 * Channels: database, mail
 */
class ReportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $reportName,
        public string $reportType,
        public string $filePath,
        public User $generatedBy,
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
        public ?array $summary = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Laporan ' . $this->reportName . ' Telah Selesai Dibuat')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Laporan yang Anda minta telah selesai dibuat:')
            ->line('')
            ->line('**Nama Laporan:** ' . $this->reportName)
            ->line('**Tipe:** ' . $this->reportType)
            ->line('**Dibuat Oleh:** ' . $this->generatedBy->name)
            ->line('**Waktu:** ' . now()->format('d/m/Y H:i'));

        if ($this->periodStart && $this->periodEnd) {
            $message->line('**Periode:** ' . $this->periodStart . ' s/d ' . $this->periodEnd);
        }

        if ($this->summary && !empty($this->summary)) {
            $message->line('')
                ->line('**Ringkasan:**');
            foreach ($this->summary as $key => $value) {
                $message->line('- ' . ucfirst($key) . ': ' . $value);
            }
        }

        return $message
            ->line('')
            ->action('Unduh Laporan', url('/admin/reports/download/' . basename($this->filePath)))
            ->line('Laporan ini juga tersedia di menu laporan sistem.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'report_ready',
            'report_name' => $this->reportName,
            'report_type' => $this->reportType,
            'file_path' => $this->filePath,
            'file_name' => basename($this->filePath),
            'generated_by' => $this->generatedBy->id,
            'generated_by_name' => $this->generatedBy->name,
            'generated_at' => now()->toDateTimeString(),
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'summary' => $this->summary,
            'message' => "Laporan {$this->reportName} telah selesai dibuat",
            'action_url' => '/admin/reports/download/' . basename($this->filePath),
        ];
    }
}
