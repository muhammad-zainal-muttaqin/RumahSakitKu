<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Clinical\MedicalRecord;
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
 * Medical Records Export Class
 * 
 * Exports EMR data to Excel with optional SOAP notes inclusion.
 */
class MedicalRecordsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /**
     * @param string|null $startDate Filter by visit date range start
     * @param string|null $endDate Filter by visit date range end
     * @param int|null $doctorId Filter by doctor
     * @param bool $includeSoap Include SOAP notes in export
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?int $doctorId = null,
        private bool $includeSoap = false
    ) {
    }

    /**
     * Query for medical records export.
     */
    public function query(): Builder
    {
        return MedicalRecord::query()
            ->with(['patient', 'visit.doctor'])
            ->when($this->startDate, fn($q) => $q->whereDate('visit_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('visit_date', '<=', $this->endDate))
            ->when($this->doctorId, function ($q) {
                $q->whereHas('visit', fn($vq) => $vq->where('doctor_id', $this->doctorId));
            })
            ->orderBy('visit_date', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        $headings = [
            'No. RM',
            'Nama Pasien',
            'Diagnosis Utama',
            'Diagnosis Sekunder',
            'Kode ICD10',
            'Deskripsi ICD10',
            'Tanggal Kunjungan',
            'Dokter',
            'Status',
        ];

        if ($this->includeSoap) {
            $headings[] = 'SOAP Notes';
        }

        return $headings;
    }

    /**
     * Map medical record data to row.
     *
     * @param MedicalRecord $record
     */
    public function map($record): array
    {
        $row = [
            $record->patient?->medical_record_number ?? '-',
            $record->patient?->name ?? '-',
            $record->diagnosis_primary ?? '-',
            $record->diagnosis_secondary ?? '-',
            $record->icd10_code ?? '-',
            $record->icd10_description ?? '-',
            $record->visit_date?->format('d/m/Y') ?? '-',
            $record->visit?->doctor?->name ?? '-',
            $record->is_finalized ? 'Final' : 'Draft',
        ];

        if ($this->includeSoap) {
            $row[] = $record->soap_note;
        }

        return $row;
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
