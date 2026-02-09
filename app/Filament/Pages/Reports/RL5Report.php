<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use App\Models\Patient\Visit;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RL5Report extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationLabel = 'RL 5 - Kematian';

    protected static ?string $title = 'Laporan RL 5 - Kematian';

    protected static ?string $slug = 'reports/rl-5';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 105;

    protected string $view = 'filament.pages.reports.rl5-report';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $reportType = 'rl5_1';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->reportType = 'rl5_1';
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Filter Laporan')
                    ->schema([
                        Select::make('reportType')
                            ->label('Jenis Laporan')
                            ->options([
                                'rl5_1' => 'RL 5.1 - Kematian di Rawat Inap',
                                'rl5_2' => 'RL 5.2 - Kematian di Gawat Darurat',
                                'rl5_3' => 'RL 5.3 - Kematian di Rawat Jalan',
                            ])
                            ->required()
                            ->live()
                            ->default('rl5_1'),
                        
                        DatePicker::make('startDate')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->startOfMonth()),
                        
                        DatePicker::make('endDate')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $start = Carbon::parse($this->startDate);
                $end = Carbon::parse($this->endDate);

                $visitType = match ($this->reportType) {
                    'rl5_1' => 'rawat_inap',
                    'rl5_2' => 'igd',
                    'rl5_3' => 'rawat_jalan',
                    default => 'rawat_inap',
                };

                $dateField = $visitType === 'igd' ? 'visit_date' : 'discharge_date';

                return Visit::query()
                    ->where('visit_type', $visitType)
                    ->where('discharge_status', 'meninggal')
                    ->whereBetween($dateField, [$start, $end])
                    ->with(['patient', 'medicalRecord.assessments']);
            })
            ->columns([
                TextColumn::make('visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('patient.name')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('patient.medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('patient.gender')
                    ->label('JK')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'primary',
                        'P' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'L',
                        'P' => 'P',
                        default => '-',
                    }),

                TextColumn::make('patient.age')
                    ->label('Umur')
                    ->state(function (Visit $record): string {
                        if (!$record->patient || !$record->patient->birth_date) return '-';
                        $age = $record->patient->birth_date->age;
                        return $age . ' th';
                    }),

                TextColumn::make('diagnosis')
                    ->label('Diagnosis Utama')
                    ->state(function (Visit $record): string {
                        $assessment = $record->medicalRecord?->assessments?->first();
                        return $assessment?->primary_diagnosis_name ?? '-';
                    })
                    ->wrap()
                    ->limit(50),

                TextColumn::make('diagnosis_code')
                    ->label('ICD-10')
                    ->state(function (Visit $record): string {
                        $assessment = $record->medicalRecord?->assessments?->first();
                        return $assessment?->primary_diagnosis_code ?? '-';
                    })
                    ->searchable()
                    ->copyable(),

                TextColumn::make('admission_date')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->visible(fn () => $this->reportType === 'rl5_1'),

                TextColumn::make('discharge_date')
                    ->label('Tgl Meninggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('los_hours')
                    ->label('LOS')
                    ->state(function (Visit $record): string {
                        if (!$record->admission_date || !$record->discharge_date) return '-';
                        $hours = Carbon::parse($record->admission_date)->diffInHours($record->discharge_date);
                        if ($hours < 24) {
                            return $hours . ' jam';
                        }
                        $days = floor($hours / 24);
                        $remainingHours = $hours % 24;
                        return $days . ' h ' . $remainingHours . ' j';
                    })
                    ->visible(fn () => $this->reportType === 'rl5_1'),
            ])
            ->defaultSort('discharge_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function getSummaryData(): array
    {
        $cacheKey = "rl5_summary_{$this->reportType}_{$this->startDate}_{$this->endDate}";
        
        return Cache::remember($cacheKey, 300, function () {
            $reportService = app(ReportService::class);
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            $mortality = $reportService->getMortalityStatistics($start, $end);

            return match ($this->reportType) {
                'rl5_1' => [
                    'title' => 'RL 5.1 - Kematian di Rawat Inap',
                    'total_deaths' => $mortality['rawat_inap']['total_deaths'],
                    'under_48h' => $mortality['rawat_inap']['under_48h'],
                    'over_48h' => $mortality['rawat_inap']['over_48h'],
                    'gdr' => $reportService->calculateGDR($start, $end, 'rawat_inap'),
                    'ndr' => $reportService->calculateNDR($start, $end),
                ],
                'rl5_2' => [
                    'title' => 'RL 5.2 - Kematian di Gawat Darurat',
                    'total_deaths' => $mortality['igd']['total_deaths'],
                ],
                'rl5_3' => [
                    'title' => 'RL 5.3 - Kematian di Rawat Jalan',
                    'total_deaths' => $mortality['rawat_jalan']['total_deaths'],
                ],
                default => [],
            };
        });
    }

    public function getAgeDistribution(): array
    {
        $cacheKey = "rl5_age_dist_{$this->reportType}_{$this->startDate}_{$this->endDate}";
        
        return Cache::remember($cacheKey, 300, function () {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            $visitType = match ($this->reportType) {
                'rl5_1' => 'rawat_inap',
                'rl5_2' => 'igd',
                'rl5_3' => 'rawat_jalan',
                default => 'rawat_inap',
            };

            $dateField = $visitType === 'igd' ? 'visit_date' : 'discharge_date';

            return Visit::where('visit_type', $visitType)
                ->where('discharge_status', 'meninggal')
                ->whereBetween($dateField, [$start, $end])
                ->join('patients', 'visits.patient_id', '=', 'patients.id')
                ->select(
                    DB::raw('CASE 
                        WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) < 1 THEN "< 1 th"
                        WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) BETWEEN 1 AND 4 THEN "1-4 th"
                        WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) BETWEEN 5 AND 14 THEN "5-14 th"
                        WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) BETWEEN 15 AND 44 THEN "15-44 th"
                        WHEN TIMESTAMPDIFF(YEAR, patients.birth_date, CURDATE()) BETWEEN 45 AND 64 THEN "45-64 th"
                        ELSE "> 64 th"
                    END as age_group'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN patients.gender = "L" THEN 1 ELSE 0 END) as male'),
                    DB::raw('SUM(CASE WHEN patients.gender = "P" THEN 1 ELSE 0 END) as female')
                )
                ->groupBy('age_group')
                ->orderByRaw('FIELD(age_group, "< 1 th", "1-4 th", "5-14 th", "15-44 th", "45-64 th", "> 64 th")')
                ->get()
                ->toArray();
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
