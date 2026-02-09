<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Patient\Patient;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent when a new patient is registered.
 */
class PatientRegisteredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Patient $patient,
        public ?string $temporaryPassword = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Selamat Datang di ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);

        return new Content(
            markdown: 'emails.patient.registered',
            with: [
                'patientName' => $this->patient->name,
                'mrn' => $this->patient->medical_record_number,
                'birthDate' => $this->patient->birth_date?->format('d/m/Y'),
                'phone' => $this->patient->phone,
                'email' => $this->patient->email,
                'address' => $this->patient->address,
                'hospitalName' => $settings->hospital_name,
                'hospitalPhone' => $settings->hospital_phone,
                'hospitalAddress' => $settings->hospital_address,
                'temporaryPassword' => $this->temporaryPassword,
                'registrationDate' => $this->patient->registered_at?->format('d/m/Y H:i'),
            ],
        );
    }
}
