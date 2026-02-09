<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Financial\Payment;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Financial Report Export Class
 * 
 * Exports payment/financial data with summary by payment method.
 */
class FinancialReportExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading, WithEvents
{
    use Exportable;

    /** @var array<string, float> Summary totals by payment method */
    private array $paymentSummary = [];

    /** @var float Grand total of all payments */
    private float $grandTotal = 0;

    /**
     * @param string|null $startDate Filter by payment date range start
     * @param string|null $endDate Filter by payment date range end
     * @param string|null $paymentMethod Filter by payment method
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?string $paymentMethod = null
    ) {
    }

    /**
     * Query for financial report export.
     */
    public function query(): Builder
    {
        return Payment::query()
            ->with(['invoice.patient'])
            ->when($this->startDate, fn($q) => $q->whereDate('payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('payment_date', '<=', $this->endDate))
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->where('is_refunded', false)
            ->orderBy('payment_date', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'Tanggal',
            'No. Pembayaran',
            'Nama Pasien',
            'No. Invoice',
            'Tipe',
            'Jumlah',
            'Metode Pembayaran',
            'No. Referensi',
            'Bank',
            'Diterima Oleh',
            'Catatan',
        ];
    }

    /**
     * Map payment data to row and track summary.
     *
     * @param Payment $payment
     */
    public function map($payment): array
    {
        // Track summary totals
        $method = $payment->payment_method;
        if (!isset($this->paymentSummary[$method])) {
            $this->paymentSummary[$method] = 0;
        }
        $this->paymentSummary[$method] += $payment->amount;
        $this->grandTotal += $payment->amount;

        return [
            $payment->payment_date?->format('d/m/Y'),
            $payment->payment_number,
            $payment->invoice?->patient?->name ?? '-',
            $payment->invoice?->invoice_number ?? '-',
            $this->getPaymentTypeLabel($payment->payment_type),
            $payment->amount,
            $payment->payment_method_label,
            $payment->reference_number ?? '-',
            $payment->bank_name ?? '-',
            $payment->received_by ?? '-',
            $payment->notes ?? '-',
        ];
    }

    /**
     * Get payment type label.
     */
    private function getPaymentTypeLabel(?string $type): string
    {
        return match ($type) {
            'registration' => 'Pendaftaran',
            'deposit' => 'Deposit',
            'settlement' => 'Pelunasan',
            'refund' => 'Refund',
            default => ucfirst($type ?? '-'),
        };
    }

    /**
     * Apply styles to worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    /**
     * Register events for adding summary.
     *
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow() + 2;

                // Add summary section
                $sheet->setCellValue("A{$lastRow}", 'RINGKASAN PER METODE PEMBAYARAN');
                $sheet->mergeCells("A{$lastRow}:K{$lastRow}");
                $sheet->getStyle("A{$lastRow}")->getFont()->setBold(true);
                $lastRow++;

                // Add headers for summary
                $sheet->setCellValue("A{$lastRow}", 'Metode Pembayaran');
                $sheet->setCellValue("B{$lastRow}", 'Total');
                $sheet->getStyle("A{$lastRow}:B{$lastRow}")->getFont()->setBold(true);
                $lastRow++;

                // Add summary data
                foreach ($this->paymentSummary as $method => $total) {
                    $sheet->setCellValue("A{$lastRow}", ucfirst(str_replace('_', ' ', $method)));
                    $sheet->setCellValue("B{$lastRow}", $total);
                    $sheet->getStyle("B{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $lastRow++;
                }

                // Add grand total
                $sheet->setCellValue("A{$lastRow}", 'GRAND TOTAL');
                $sheet->setCellValue("B{$lastRow}", $this->grandTotal);
                $sheet->getStyle("A{$lastRow}:B{$lastRow}")->getFont()->setBold(true);
                $sheet->getStyle("B{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }

    /**
     * Chunk size for large datasets.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
