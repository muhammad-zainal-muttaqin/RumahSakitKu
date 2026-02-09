<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Patient\Patient;
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
 * Patients Export Class
 * 
 * Exports patient data to Excel with filtering capabilities.
 */
class PatientsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    /**
     * @param string|null $startDate Filter by registration date range start
     * @param string|null $endDate Filter by registration date range end
     * @param string|null $insuranceType Filter by insurance type
     * @param bool $activeOnly Only include active patients
     */
    public function __construct(
        private ?string $startDate = null,
        private ?string $endDate = null,
        private ?string $insuranceType = null,
        private bool $activeOnly = true
    ) {
    }

    /**
     * Query for patients export.
     */
    public function query(): Builder
    {
        return Patient::query()
            ->when($this->startDate, fn($q) => $q->whereDate('registered_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('registered_at', '<=', $this->endDate))
            ->when($this->insuranceType, fn($q) => $q->where('insurance_type', $this->insuranceType))
            ->when($this->activeOnly, fn($q) => $q->where('is_active', true))
            ->orderBy('registered_at', 'desc');
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'No. RM',
            'Nama',
            'NIK',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Alamat',
            'Telepon',
            'Asuransi',
        ];
    }

    /**
     * Map patient data to row.
     *
     * @param Patient $patient
     */
    public function map($patient): array
    {
        return [
            $patient->medical_record_number,
            $patient->name,
            $patient->nik,
            $this->getGenderLabel($patient->gender),
            $patient->birth_date?->format('d/m/Y'),
            $patient->address,
            $patient->phone,
            $this->getInsuranceTypeLabel($patient->insurance_type),
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
     * Get gender label.
     */
    private function getGenderLabel(?string $gender): string
    {
        return match ($gender) {
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
            default => '-',
        };
    }

    /**
     * Get insurance type label.
     */
    private function getInsuranceTypeLabel(?string $type): string
    {
        return match ($type) {
            'bpjs' => 'BPJS Kesehatan',
            'private' => 'Asuransi Swasta',
            'corporate' => 'Asuransi Perusahaan',
            'government' => 'Asuransi Pemerintah',
            'self_pay' => 'Umum',
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
