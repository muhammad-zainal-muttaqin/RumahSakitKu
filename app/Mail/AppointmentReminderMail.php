<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Patient\Visit;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent as appointment reminder to patient.
 */
class AppointmentReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Visit $visit,
        public ?string $additionalNotes = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Pengingat Janji Temu - ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $patient = $this->visit->patient;
        $polyclinic = $this->visit->polyclinic;
        $doctor = $this->visit->doctor;

        return new Content(
            markdown: 'emails.patient.appointment-reminder',
            with: [
                'patientName' => $patient?->name,
                'visitNumber' => $this->visit->visit_number,
                'visitDate' => $this->visit->visit_date?->format('d/m/Y'),
                'visitTime' => $this->visit->visit_date?->format('H:i'),
                'polyclinicName' => $polyclinic?->name ?? 'Umum',
                'doctorName' => $doctor?->full_name_with_title ?? 'Dokter Jaga',
                'complaint' => $this->visit->complaint,
                'hospitalName' => $settings->hospital_name,
                'hospitalAddress' => $settings->hospital_address,
                'hospitalPhone' => $settings->hospital_phone,
                'additionalNotes' => $this->additionalNotes,
                'checkInUrl' => url('/check-in/' . $this->visit->visit_number),
            ],
        );
    }
}
