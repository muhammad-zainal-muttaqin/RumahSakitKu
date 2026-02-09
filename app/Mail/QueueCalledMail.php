<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Patient\VisitQueue;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent when patient's queue is called.
 */
class QueueCalledMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public VisitQueue $queue,
        public ?int $estimatedWaitMinutes = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Antrian Anda Dipanggil - ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $patient = $this->queue->patient;
        $polyclinic = $this->queue->polyclinic;

        return new Content(
            markdown: 'emails.patient.queue-called',
            with: [
                'patientName' => $patient?->name,
                'queueNumber' => $this->queue->display_number,
                'polyclinicName' => $polyclinic?->name,
                'counterNumber' => $this->queue->counter_number,
                'calledAt' => $this->queue->called_at?->format('H:i'),
                'estimatedWaitMinutes' => $this->estimatedWaitMinutes,
                'hospitalName' => $settings->hospital_name,
                'hospitalPhone' => $settings->hospital_phone,
                'waitingTime' => $this->queue->waiting_time,
            ],
        );
    }
}
