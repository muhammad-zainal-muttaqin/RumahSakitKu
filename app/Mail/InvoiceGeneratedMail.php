<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Financial\Invoice;
use App\Settings\HospitalSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent when invoice is generated for patient.
 */
class InvoiceGeneratedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $paymentLink = null
    ) {
    }

    public function envelope(): Envelope
    {
        $settings = app(HospitalSettings::class);

        return new Envelope(
            subject: 'Tagihan Pembayaran - ' . $this->invoice->invoice_number . ' - ' . $settings->hospital_name,
        );
    }

    public function content(): Content
    {
        $settings = app(HospitalSettings::class);
        $patient = $this->invoice->patient;
        $visit = $this->invoice->visit;

        return new Content(
            markdown: 'emails.patient.invoice',
            with: [
                'patientName' => $patient?->name,
                'invoiceNumber' => $this->invoice->invoice_number,
                'invoiceDate' => $this->invoice->invoice_date?->format('d/m/Y'),
                'dueDate' => $this->invoice->due_date?->format('d/m/Y'),
                'visitNumber' => $visit?->visit_number,
                'subtotal' => $this->invoice->formatted_total,
                'discountAmount' => 'Rp ' . number_format($this->invoice->discount_amount ?? 0, 0, ',', '.'),
                'taxAmount' => 'Rp ' . number_format($this->invoice->tax_amount ?? 0, 0, ',', '.'),
                'totalAmount' => 'Rp ' . number_format($this->invoice->total_amount, 0, ',', '.'),
                'paidAmount' => 'Rp ' . number_format($this->invoice->paid_amount, 0, ',', '.'),
                'balanceDue' => 'Rp ' . number_format($this->invoice->balance_due, 0, ',', '.'),
                'paymentStatus' => $this->invoice->payment_status,
                'isPaid' => $this->invoice->is_paid,
                'paymentLink' => $this->paymentLink,
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

        // Optionally attach invoice PDF if available
        // $pdfPath = storage_path('app/invoices/' . $this->invoice->invoice_number . '.pdf');
        // if (file_exists($pdfPath)) {
        //     $attachments[] = Attachment::fromPath($pdfPath);
        // }

        return $attachments;
    }
}
