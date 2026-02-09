<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class RL1Report extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'RL 1 - Data Dasar RS';

    protected static ?string $title = 'Laporan RL 1 - Data Dasar Rumah Sakit';

    protected static ?string $slug = 'reports/rl-1';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 101;

    protected string $view = 'filament.pages.reports.rl1-report';

    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Filter Periode')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(2),
            ]);
    }

    public function getHospitalData(): array
    {
        $cacheKey = "rl1_hospital_{$this->startDate}_{$this->endDate}";
        
        return Cache::remember($cacheKey, 300, function () {
            $reportService = app(ReportService::class);
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            $bedStats = $reportService->getHospitalBedStatistics();
            $employeeStats = $reportService->getEmployeeStatistics();
            $serviceStats = $reportService->getServiceStatistics($start, $end);

            return [
                'rl1_1' => [
                    'title' => 'RL 1.1 - Data Dasar Rumah Sakit',
                    'data' => [
                        'nama_rs' => config('app.hospital_name', 'Rumah Sakit'),
                        'kode_rs' => config('app.hospital_code', '-'),
                        'jenis_rs' => config('app.hospital_type', 'RS Umum'),
                        'kelas_rs' => config('app.hospital_class', 'B'),
                        'total_beds' => $bedStats['total_beds'],
                        'director_name' => config('app.hospital_director', '-'),
                    ],
                ],
                'rl1_2' => [
                    'title' => 'RL 1.2 - Indikator Pelayanan',
                    'data' => [
                        'bor' => $reportService->calculateBOR($start, $end),
                        'los' => $reportService->calculateLOS($start, $end),
                        'toi' => $reportService->calculateTOI($start, $end),
                        'bto' => $reportService->calculateBTO($start, $end),
                        'gdr' => $reportService->calculateGDR($start, $end, 'rawat_inap'),
                        'ndr' => $reportService->calculateNDR($start, $end),
                    ],
                ],
            ];
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success'),
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger'),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray'),
        ];
    }
}
