<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Patient\Visit;
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
 * Visits Export Class
 * 
 * Exports visit data to Excel with filtering capabilities.
 */
class VisitsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /**
     * @param string|null $startDate Filter by visit date range start
     * @param string|null $endDate Filter by visit date range end
     * @param int|null $polyclinicId Filter by polyclinic
     * @param int|null $doctorId Filter by doctor
     * @param string|null $status Filter by visit status
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?int $polyclinicId = null,
        private ?int $doctorId = null,
        private ?string $status = null
    ) {
    }

    /**
     * Query for visits export.
     */
    public function query(): Builder
    {
        return Visit::query()
            ->with(['patient', 'polyclinic', 'doctor'])
            ->when($this->startDate, fn($q) => $q->whereDate('visit_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('visit_date', '<=', $this->endDate))
            ->when($this->polyclinicId, fn($q) => $q->where('polyclinic_id', $this->polyclinicId))
            ->when($this->doctorId, fn($q) => $q->where('doctor_id', $this->doctorId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy('visit_date', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'No. Kunjungan',
            'Pasien',
            'Poli',
            'Dokter',
            'Tanggal',
            'Status',
            'Tipe',
        ];
    }

    /**
     * Map visit data to row.
     *
     * @param Visit $visit
     */
    public function map($visit): array
    {
        return [
            $visit->visit_number,
            $visit->patient?->name ?? '-',
            $visit->polyclinic?->name ?? '-',
            $visit->doctor?->name ?? '-',
            $visit->visit_date?->format('d/m/Y'),
            $this->getStatusLabel($visit->status),
            $this->getVisitTypeLabel($visit->visit_type),
        ];
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
     * Get status label.
     */
    private function getStatusLabel(?string $status): string
    {
        return match ($status) {
            'registered' => 'Terdaftar',
            'waiting' => 'Menunggu',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    /**
     * Get visit type label.
     */
    private function getVisitTypeLabel(?string $type): string
    {
        return match ($type) {
            'outpatient' => 'Rawat Jalan',
            'inpatient' => 'Rawat Inap',
            'emergency' => 'IGD',
            default => '-',
        };
    }

    /**
     * Chunk size for large datasets.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
