<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Patient\VisitQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitQueuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No. Antrian',
            'No. Kunjungan',
            'No. RM',
            'Nama Pasien',
            'Poliklinik',
            'Status',
            'Loket',
            'Waktu Daftar',
            'Waktu Dipanggil',
            'Waktu Selesai',
            'Waktu Tunggu (menit)',
            'Waktu Pelayanan (menit)',
            'Tanggal',
        ];
    }

    public function map($queue): array
    {
        return [
            $queue->display_number,
            $queue->visit?->visit_number,
            $queue->patient?->medical_record_number,
            $queue->patient?->name,
            $queue->polyclinic?->name,
            $this->getStatusLabel($queue->status),
            $queue->counter_number,
            $queue->created_at?->format('H:i:s'),
            $queue->called_at?->format('H:i:s'),
            $queue->completed_at?->format('H:i:s'),
            $queue->waiting_time,
            $queue->service_time,
            $queue->created_at?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'skipped' => 'Dilewati',
            default => $status,
        };
    }
}
