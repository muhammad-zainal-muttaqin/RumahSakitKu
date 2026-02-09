<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Financial\Payment;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent as payment confirmation to patient.
 */
class PaymentReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Payment $payment,
        public ?string $receiptUrl = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Konfirmasi Pembayaran - ' . $this->payment->payment_number . ' - ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $invoice = $this->payment->invoice;
        $patient = $invoice?->patient;

        return new Content(
            markdown: 'emails.patient.payment-received',
            with: [
                'patientName' => $patient?->name,
                'paymentNumber' => $this->payment->payment_number,
                'paymentDate' => $this->payment->payment_date?->format('d/m/Y'),
                'paymentTime' => $this->payment->payment_time?->format('H:i'),
                'amount' => $this->payment->formatted_amount,
                'paymentMethod' => $this->payment->payment_method_label,
                'paymentType' => $this->payment->payment_type,
                'referenceNumber' => $this->payment->reference_number,
                'bankName' => $this->payment->bank_name,
                'receivedBy' => $this->payment->received_by,
                'invoiceNumber' => $invoice?->invoice_number,
                'remainingBalance' => $invoice ? 'Rp ' . number_format($invoice->balance_due, 0, ',', '.') : null,
                'isFullyPaid' => $invoice?->is_paid ?? false,
                'receiptUrl' => $this->receiptUrl,
                'hospitalName' => $settings->hospital_name,
                'hospitalPhone' => $settings->hospital_phone,
                'hospitalAddress' => $settings->hospital_address,
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

        // Optionally attach receipt PDF if available
        // $receiptPath = storage_path('app/receipts/' . $this->payment->payment_number . '.pdf');
        // if (file_exists($receiptPath)) {
        //     $attachments[] = Attachment::fromPath($receiptPath);
        // }

        return $attachments;
    }
}
