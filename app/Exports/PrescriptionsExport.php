<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Clinical\Prescription;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Prescriptions Export Class
 * 
 * Exports prescription data with optional item details.
 */
class PrescriptionsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /**
     * @param string|null $startDate Filter by prescription date range start
     * @param string|null $endDate Filter by prescription date range end
     * @param string|null $status Filter by prescription status
     * @param int|null $doctorId Filter by prescribing doctor
     * @param bool $includeItems Include prescription items detail
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?string $status = null,
        private ?int $doctorId = null,
        private bool $includeItems = false
    ) {
    }

    /**
     * Query for prescriptions export.
     */
    public function query(): Builder
    {
        return Prescription::query()
            ->with(['patient', 'prescribedBy', 'items.medicine'])
            ->when($this->startDate, fn($q) => $q->whereDate('prescription_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('prescription_date', '<=', $this->endDate))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->doctorId, fn($q) => $q->where('prescribed_by', $this->doctorId))
            ->orderBy('prescription_date', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'No. Resep',
            'Tanggal',
            'Nama Pasien',
            'No. RM',
            'Dokter',
            'Total Item',
            'Total Estimasi',
            'Status',
            'Prioritas',
            'Tipe Resep',
            'Items Detail',
        ];
    }

    /**
     * Map prescription data to row.
     *
     * @param Prescription $prescription
     */
    public function map($prescription): array
    {
        $itemsDetail = $this->includeItems ? $this->formatItemsDetail($prescription) : '-';

        return [
            $prescription->prescription_number,
            $prescription->prescription_date?->format('d/m/Y'),
            $prescription->patient?->name ?? '-',
            $prescription->patient?->medical_record_number ?? '-',
            $prescription->prescribedBy?->name ?? '-',
            $prescription->total_items,
            'Rp ' . number_format($prescription->total_estimated_cost, 0, ',', '.'),
            $this->getStatusLabel($prescription->status),
            $this->getPriorityLabel($prescription->priority),
            $this->getTypeLabel($prescription->prescription_type),
            $itemsDetail,
        ];
    }

    /**
     * Format prescription items detail.
     */
    private function formatItemsDetail(Prescription $prescription): string
    {
        $items = [];
        foreach ($prescription->items as $item) {
            $medicineName = $item->medicine?->name ?? 'Unknown';
            $items[] = "- {$medicineName}: {$item->quantity} {$item->unit}";
        }
        return implode("\n", $items);
    }

    /**
     * Get status label.
     */
    private function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'processing' => 'Diproses',
            'ready' => 'Siap',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    /**
     * Get priority label.
     */
    private function getPriorityLabel(?string $priority): string
    {
        return match ($priority) {
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'emergency' => 'Emergency',
            default => '-',
        };
    }

    /**
     * Get prescription type label.
     */
    private function getTypeLabel(?string $type): string
    {
        return match ($type) {
            'regular' => 'Reguler',
            'compound' => 'Racikan',
            'external' => 'Luar',
            default => '-',
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
     * Chunk size for large datasets.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
