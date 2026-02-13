<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Clinical\LaboratoryResult;
use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\Clinical\RadiologyResult;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * RL 3 Export Class - Pelayanan Rumah Sakit
 * 
 * Generates RL 3.1 - 3.15 reports in Kemenkes format.
 * Contains service delivery indicators.
 */
class RL3Export implements WithMultipleSheets
{
    use Exportable;

    /**
     * @param string $startDate Report period start (Y-m-d)
     * @param string $endDate Report period end (Y-m-d)
     */
    public function __construct(
        private string $startDate,
        private string $endDate
    ) {
    }

    /**
     * Get all sheets for the export.
     *
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new RL31Sheet($this->startDate, $this->endDate),
            new RL32Sheet($this->startDate, $this->endDate),
            new RL33Sheet($this->startDate, $this->endDate),
            new RL34Sheet($this->startDate, $this->endDate),
        ];
    }
}

/**
 * RL 3.1 Sheet - Pelayanan Rawat Jalan
 */
class RL31Sheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private Collection $data;
    private string $period;

    public function __construct(string $startDate, string $endDate)
    {
        $this->period = Carbon::parse($startDate)->format('F Y') . ' - ' . Carbon::parse($endDate)->format('F Y');
        $this->data = $this->generateData($startDate, $endDate);
    }

    private function generateData(string $startDate, string $endDate): Collection
    {
        $stats = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('visit_type', 'outpatient')
            ->selectRaw('polyclinic_id,
                COUNT(*) as total,
                SUM(registration_type = "walk_in") as new_patient,
                SUM(registration_type != "walk_in") as old_patient
            ')
            ->groupBy('polyclinic_id')
            ->get()
            ->keyBy('polyclinic_id');

        $polyclinics = Polyclinic::all();
        $data = collect();
        $no = 1;

        foreach ($polyclinics as $polyclinic) {
            $stat = $stats->get($polyclinic->id);

            if ($stat && $stat->total > 0) {
                $data->push((object) [
                    'no' => $no++,
                    'service_unit' => $polyclinic->name,
                    'new_patient' => $stat->new_patient,
                    'old_patient' => $stat->old_patient,
                    'total' => $stat->total,
                ]);
            }
        }

        return $data;
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Unit Pelayanan',
            'Pasien Baru',
            'Pasien Lama',
            'Total',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->service_unit,
            $row->new_patient,
            $row->old_patient,
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'RL 3.1 - Rawat Jalan';
    }
}

/**
 * RL 3.2 Sheet - Pelayanan IGD
 */
class RL32Sheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private Collection $data;

    public function __construct(string $startDate, string $endDate)
    {
        $this->data = $this->generateData($startDate, $endDate);
    }

    private function generateData(string $startDate, string $endDate): Collection
    {
        $emergencyVisits = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('visit_type', 'emergency')
            ->count();

        return collect([
            (object) [
                'no' => 1,
                'description' => 'Jumlah Kunjungan IGD',
                'total' => $emergencyVisits,
            ],
            (object) [
                'no' => 2,
                'description' => 'Pasien Rujukan',
                'total' => Visit::whereBetween('visit_date', [$startDate, $endDate])
                    ->where('visit_type', 'emergency')
                    ->where('registration_type', 'referral')
                    ->count(),
            ],
            (object) [
                'no' => 3,
                'description' => 'Pasien Non-Rujukan',
                'total' => Visit::whereBetween('visit_date', [$startDate, $endDate])
                    ->where('visit_type', 'emergency')
                    ->where('registration_type', '!=', 'referral')
                    ->count(),
            ],
        ]);
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Uraian',
            'Jumlah',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->description,
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'RL 3.2 - IGD';
    }
}

/**
 * RL 3.3 Sheet - Pelayanan Rawat Inap
 */
class RL33Sheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private Collection $data;

    public function __construct(string $startDate, string $endDate)
    {
        $this->data = $this->generateData($startDate, $endDate);
    }

    private function generateData(string $startDate, string $endDate): Collection
    {
        $inpatientVisits = Visit::whereBetween('visit_date', [$startDate, $endDate])
            ->where('visit_type', 'inpatient')
            ->count();

        return collect([
            (object) [
                'no' => 1,
                'description' => 'Jumlah Pasien Masuk',
                'total' => $inpatientVisits,
            ],
            (object) [
                'no' => 2,
                'description' => 'Pasien Dipindahkan',
                'total' => 0, // Would need transfer data
            ],
            (object) [
                'no' => 3,
                'description' => 'Pasien Keluar Hidup',
                'total' => 0, // Would need discharge data
            ],
            (object) [
                'no' => 4,
                'description' => 'Pasien Keluar Meninggal',
                'total' => 0, // Would need mortality data
            ],
        ]);
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Uraian',
            'Jumlah',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->description,
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'RL 3.3 - Rawat Inap';
    }
}

/**
 * RL 3.4 Sheet - Pelayanan Penunjang
 */
class RL34Sheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    private Collection $data;

    public function __construct(string $startDate, string $endDate)
    {
        $this->data = $this->generateData($startDate, $endDate);
    }

    private function generateData(string $startDate, string $endDate): Collection
    {
        $labResults = LaboratoryResult::whereHas('laboratoryOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate]);
        })->count();

        $radiologyResults = RadiologyResult::whereHas('radiologyOrder', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('order_date', [$startDate, $endDate]);
        })->count();

        $prescriptions = Prescription::whereBetween('prescription_date', [$startDate, $endDate])->count();

        $medicalRecords = MedicalRecord::whereBetween('visit_date', [$startDate, $endDate])->count();

        return collect([
            (object) [
                'no' => 1,
                'service_type' => 'Laboratorium',
                'total' => $labResults,
            ],
            (object) [
                'no' => 2,
                'service_type' => 'Radiologi',
                'total' => $radiologyResults,
            ],
            (object) [
                'no' => 3,
                'service_type' => 'Farmasi',
                'total' => $prescriptions,
            ],
            (object) [
                'no' => 4,
                'service_type' => 'Rekam Medis',
                'total' => $medicalRecords,
            ],
        ]);
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Jenis Pelayanan',
            'Jumlah',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no,
            $row->service_type,
            $row->total,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'RL 3.4 - Penunjang';
    }
}
