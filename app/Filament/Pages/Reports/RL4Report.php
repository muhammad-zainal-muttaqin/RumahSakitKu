<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use App\Models\Clinical\Assessment;
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

class RL4Report extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'RL 4 - Morbiditas';

    protected static ?string $title = 'Laporan RL 4 - Morbiditas (10 Besar Penyakit)';

    protected static ?string $slug = 'reports/rl-4';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 104;

    protected string $view = 'filament.pages.reports.rl4-report';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $reportType = 'all';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->reportType = 'all';
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
                                'all' => 'Semua Penyakit',
                                'rawat_inap' => 'Rawat Inap',
                                'rawat_jalan' => 'Rawat Jalan',
                                'igd' => 'IGD',
                            ])
                            ->required()
                            ->default('all'),
                        
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

                return Assessment::query()
                    ->whereBetween('assessed_at', [$start, $end])
                    ->whereNotNull('primary_diagnosis_code')
                    ->when($this->reportType !== 'all', function ($query) {
                        return $query->whereHas('visit', function ($q) {
                            $q->where('visit_type', $this->reportType);
                        });
                    })
                    ->select(
                        'primary_diagnosis_code',
                        'primary_diagnosis_name',
                        DB::raw('COUNT(*) as total_cases'),
                        DB::raw('SUM(CASE WHEN patients.gender = "L" THEN 1 ELSE 0 END) as male_count'),
                        DB::raw('SUM(CASE WHEN patients.gender = "P" THEN 1 ELSE 0 END) as female_count')
                    )
                    ->join('patients', 'assessments.patient_id', '=', 'patients.id')
                    ->groupBy('primary_diagnosis_code', 'primary_diagnosis_name')
                    ->orderByDesc('total_cases')
                    ->limit(50);
            })
            ->columns([
                TextColumn::make('rank')
                    ->label('No.')
                    ->state(function ($record, $rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->alignCenter()
                    ->width('50px'),

                TextColumn::make('primary_diagnosis_code')
                    ->label('Kode ICD-10')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-semibold'),

                TextColumn::make('primary_diagnosis_name')
                    ->label('Nama Penyakit')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('total_cases')
                    ->label('Jumlah Kasus')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->weight('font-bold'),

                TextColumn::make('male_count')
                    ->label('Laki-laki')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color('primary'),

                TextColumn::make('female_count')
                    ->label('Perempuan')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color('danger'),

                TextColumn::make('percentage')
                    ->label('%')
                    ->state(function ($record) {
                        $total = Assessment::whereBetween('assessed_at', [
                            Carbon::parse($this->startDate),
                            Carbon::parse($this->endDate)
                        ])->whereNotNull('primary_diagnosis_code')->count();
                        
                        return $total > 0 ? round(($record->total_cases / $total) * 100, 2) . '%' : '0%';
                    })
                    ->alignEnd(),
            ])
            ->defaultSort('total_cases', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Tidak ada data penyakit')
            ->emptyStateDescription('Tidak ditemukan data diagnosis untuk periode yang dipilih.');
    }

    public function getSummaryData(): array
    {
        $cacheKey = "rl4_summary_{$this->startDate}_{$this->endDate}_{$this->reportType}";
        
        return Cache::remember($cacheKey, 300, function () {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            $query = Assessment::whereBetween('assessed_at', [$start, $end])
                ->whereNotNull('primary_diagnosis_code');

            if ($this->reportType !== 'all') {
                $query->whereHas('visit', function ($q) {
                    $q->where('visit_type', $this->reportType);
                });
            }

            $totalCases = $query->count();
            $totalDiseases = $query->distinct('primary_diagnosis_code')->count();

            // Top 10 diseases
            $top10 = (clone $query)
                ->select('primary_diagnosis_code', 'primary_diagnosis_name', DB::raw('COUNT(*) as count'))
                ->groupBy('primary_diagnosis_code', 'primary_diagnosis_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            return [
                'total_cases' => $totalCases,
                'total_diseases' => $totalDiseases,
                'top_10' => $top10,
                'average_cases_per_disease' => $totalDiseases > 0 ? round($totalCases / $totalDiseases, 2) : 0,
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
