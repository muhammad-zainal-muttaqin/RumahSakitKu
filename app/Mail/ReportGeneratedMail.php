<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent when a report is generated for admin.
 */
class ReportGeneratedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

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

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan ' . $this->reportName . ' Telah Selesai Dibuat',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.report-generated',
            with: [
                'reportName' => $this->reportName,
                'reportType' => $this->reportType,
                'generatedBy' => $this->generatedBy->name,
                'generatedAt' => now()->format('d/m/Y H:i'),
                'periodStart' => $this->periodStart,
                'periodEnd' => $this->periodEnd,
                'summary' => $this->summary,
                'downloadUrl' => url('/admin/reports/download/' . basename($this->filePath)),
                'fileSize' => $this->getFileSize(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if (file_exists($this->filePath)) {
            $attachments[] = Attachment::fromPath($this->filePath);
        }

        return $attachments;
    }

    /**
     * Get formatted file size.
     */
    private function getFileSize(): string
    {
        if (!file_exists($this->filePath)) {
            return '-';
        }

        $bytes = filesize($this->filePath);
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
