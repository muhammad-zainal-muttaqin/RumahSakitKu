<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * RL 1 Export Class - Data Dasar Rumah Sakit
 * 
 * Generates RL 1.1 report in Kemenkes format.
 * Contains basic hospital indicators and statistics.
 */
class RL1Export implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    /** @var Collection<int, object> Report data */
    private Collection $data;

    /** @var string Report period */
    private string $period;

    /**
     * @param string $startDate Report period start (Y-m-d)
     * @param string $endDate Report period end (Y-m-d)
     */
    public function __construct(
        private string $startDate,
        private string $endDate
    ) {
        $this->period = Carbon::parse($startDate)->format('F Y') . ' - ' . Carbon::parse($endDate)->format('F Y');
        $this->data = $this->generateReportData();
    }

    /**
     * Generate RL 1.1 report data with calculated indicators.
     */
    private function generateReportData(): Collection
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Patient statistics
        $totalPatients = Patient::count();
        $newPatients = Patient::whereBetween('registered_at', [$start, $end])->count();
        $activePatients = Patient::where('is_active', true)->count();

        // Visit statistics
        $totalVisits = Visit::whereBetween('visit_date', [$start, $end])->count();
        $outpatientVisits = Visit::whereBetween('visit_date', [$start, $end])
            ->where('visit_type', 'outpatient')
            ->count();
        $inpatientVisits = Visit::whereBetween('visit_date', [$start, $end])
            ->where('visit_type', 'inpatient')
            ->count();
        $emergencyVisits = Visit::whereBetween('visit_date', [$start, $end])
            ->where('visit_type', 'emergency')
            ->count();

        // Financial statistics
        $totalRevenue = Invoice::whereBetween('invoice_date', [$start, $end])
            ->where('status', 'paid')
            ->sum('total_amount');
        $totalPayments = Payment::whereBetween('payment_date', [$start, $end])
            ->where('is_refunded', false)
            ->sum('amount');

        // Employee statistics
        $totalEmployees = Employee::where('status', 'aktif')->count();
        $totalDoctors = Employee::where('status', 'aktif')->where('is_doctor', true)->count();
        $totalNurses = Employee::where('status', 'aktif')->where('is_nurse', true)->count();

        // Calculate BOR (Bed Occupancy Rate) - Example calculation
        $bor = 0; // Would need actual bed count and patient days data

        // Calculate ALOS (Average Length of Stay)
        $alos = 0; // Would need actual admission and discharge data

        // Calculate TOI (Turn Over Interval)
        $toi = 0; // Would need actual bed data

        return collect([
            (object) [
                'no' => 1,
                'indicator' => 'Jumlah Pasien Terdaftar',
                'value' => $totalPatients,
                'unit' => 'orang',
                'notes' => 'Total pasien dalam sistem',
            ],
            (object) [
                'no' => 2,
                'indicator' => 'Pasien Baru',
                'value' => $newPatients,
                'unit' => 'orang',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 3,
                'indicator' => 'Pasien Aktif',
                'value' => $activePatients,
                'unit' => 'orang',
                'notes' => 'Status aktif',
            ],
            (object) [
                'no' => 4,
                'indicator' => 'Total Kunjungan',
                'value' => $totalVisits,
                'unit' => 'kunjungan',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 5,
                'indicator' => 'Kunjungan Rawat Jalan',
                'value' => $outpatientVisits,
                'unit' => 'kunjungan',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 6,
                'indicator' => 'Kunjungan Rawat Inap',
                'value' => $inpatientVisits,
                'unit' => 'kunjungan',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 7,
                'indicator' => 'Kunjungan IGD',
                'value' => $emergencyVisits,
                'unit' => 'kunjungan',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 8,
                'indicator' => 'Total Pendapatan',
                'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'unit' => 'rupiah',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 9,
                'indicator' => 'Total Pembayaran Diterima',
                'value' => 'Rp ' . number_format($totalPayments, 0, ',', '.'),
                'unit' => 'rupiah',
                'notes' => 'Periode ' . $this->period,
            ],
            (object) [
                'no' => 10,
                'indicator' => 'Jumlah Karyawan Aktif',
                'value' => $totalEmployees,
                'unit' => 'orang',
                'notes' => 'Status aktif',
            ],
            (object) [
                'no' => 11,
                'indicator' => 'Jumlah Dokter',
                'value' => $totalDoctors,
                'unit' => 'orang',
                'notes' => 'Status aktif',
            ],
            (object) [
                'no' => 12,
                'indicator' => 'Jumlah Perawat',
                'value' => $totalNurses,
                'unit' => 'orang',
                'notes' => 'Status aktif',
            ],
            (object) [
                'no' => 13,
                'indicator' => 'BOR (Bed Occupancy Rate)',
                'value' => $bor . '%',
                'unit' => 'persen',
                'notes' => '(Hari Perawatan / (Jumlah TT x Hari)) x 100%',
            ],
            (object) [
                'no' => 14,
                'indicator' => 'ALOS (Average Length of Stay)',
                'value' => $alos,
                'unit' => 'hari',
                'notes' => 'Lama Dirawat / Pasien Keluar',
            ],
            (object) [
                'no' => 15,
                'indicator' => 'TOI (Turn Over Interval)',
                'value' => $toi,
                'unit' => 'hari',
                'notes' => '(Jumlah TT x Periode - Hari Perawatan) / Pasien Keluar',
            ],
        ]);
    }

    /**
     * Get collection for export.
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * Excel column headings.
     */
    public function headings(): array
    {
        return [
            'No.',
            'Indikator',
            'Nilai',
            'Satuan',
            'Keterangan',
        ];
    }

    /**
     * Map data to row.
     *
     * @param object $row
     */
    public function map($row): array
    {
        return [
            $row->no,
            $row->indicator,
            $row->value,
            $row->unit,
            $row->notes,
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
                    'startColor' => ['rgb' => '1E40AF'],
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    /**
     * Sheet title.
     */
    public function title(): string
    {
        return 'RL 1.1 - Data Dasar RS';
    }
}
