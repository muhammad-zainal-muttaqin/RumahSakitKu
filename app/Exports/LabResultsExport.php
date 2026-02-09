<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Clinical\LaboratoryResult;
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
 * Lab Results Export Class
 * 
 * Exports laboratory test results with flag indicators.
 */
class LabResultsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /**
     * @param string|null $startDate Filter by result date range start
     * @param string|null $endDate Filter by result date range end
     * @param int|null $patientId Filter by patient
     * @param string|null $testType Filter by test type/category
     * @param string|null $flag Filter by result flag (normal, abnormal, critical)
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?int $patientId = null,
        private ?string $testType = null,
        private ?string $flag = null
    ) {
    }

    /**
     * Query for lab results export.
     */
    public function query(): Builder
    {
        return LaboratoryResult::query()
            ->with(['laboratoryOrder.patient', 'labTest', 'validatedBy'])
            ->when($this->startDate, function ($q) {
                $q->whereHas('laboratoryOrder', fn($oq) => $oq->whereDate('order_date', '>=', $this->startDate));
            })
            ->when($this->endDate, function ($q) {
                $q->whereHas('laboratoryOrder', fn($oq) => $oq->whereDate('order_date', '<=', $this->endDate));
            })
            ->when($this->patientId, function ($q) {
                $q->whereHas('laboratoryOrder', fn($oq) => $oq->where('patient_id', $this->patientId));
            })
            ->when($this->testType, fn($q) => $q->whereHas('labTest', fn($tq) => $tq->where('category', $this->testType)))
            ->when($this->flag, fn($q) => $q->where('flag', $this->flag))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'No. Order',
            'Nama Pasien',
            'No. RM',
            'Pemeriksaan',
            'Kategori',
            'Hasil',
            'Flag',
            'Referensi',
            'Unit',
            'Metode',
            'Mesin Analyzer',
            'Divalidasi Oleh',
            'Tanggal Validasi',
            'Catatan',
        ];
    }

    /**
     * Map lab result data to row.
     *
     * @param LaboratoryResult $result
     */
    public function map($result): array
    {
        return [
            $result->laboratoryOrder?->order_number ?? '-',
            $result->laboratoryOrder?->patient?->name ?? '-',
            $result->laboratoryOrder?->patient?->medical_record_number ?? '-',
            $result->labTest?->name ?? '-',
            $result->labTest?->category_label ?? '-',
            $result->display_value,
            $result->flag_label,
            $result->reference_range ?? '-',
            $result->unit ?? '-',
            $result->test_method ?? '-',
            $result->analyzer_machine ?? '-',
            $result->validatedBy?->name ?? '-',
            $result->validated_at?->format('d/m/Y H:i') ?? '-',
            $result->notes ?? '-',
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
     * Chunk size for large datasets.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
