<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Clinical\LaboratoryOrder;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent when lab results are ready.
 */
class LabResultsReadyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public LaboratoryOrder $labOrder,
        public ?string $accessCode = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Hasil Laboratorium Tersedia - ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $patient = $this->labOrder->patient;
        $doctor = $this->labOrder->doctor;

        // Count results
        $totalResults = $this->labOrder->results?->count() ?? 0;
        $abnormalResults = $this->labOrder->results?->where('is_abnormal', true)->count() ?? 0;
        $criticalResults = $this->labOrder->results?->where('is_critical', true)->count() ?? 0;

        return new Content(
            markdown: 'emails.patient.lab-results',
            with: [
                'patientName' => $patient?->name,
                'orderNumber' => $this->labOrder->order_number,
                'orderDate' => $this->labOrder->order_date?->format('d/m/Y'),
                'doctorName' => $doctor?->full_name_with_title,
                'totalResults' => $totalResults,
                'abnormalResults' => $abnormalResults,
                'criticalResults' => $criticalResults,
                'hasAbnormalResults' => $abnormalResults > 0,
                'hasCriticalResults' => $criticalResults > 0,
                'priority' => $this->labOrder->priority,
                'diagnosis' => $this->labOrder->diagnosis,
                'clinicalNotes' => $this->labOrder->clinical_notes,
                'accessCode' => $this->accessCode,
                'resultsUrl' => url('/patient-portal/lab-results/' . $this->labOrder->order_number),
                'hospitalName' => $settings->hospital_name,
                'hospitalPhone' => $settings->hospital_phone,
                'hospitalAddress' => $settings->hospital_address,
            ],
        );
    }
}
