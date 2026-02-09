<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;
use UnitEnum;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Clinical\RadiologyOrder;
use App\Models\Clinical\LaboratoryOrder;
use App\Models\Clinical\Prescription;
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
use Illuminate\Support\Facades\Cache;

class RL3Report extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'RL 3 - Pelayanan';

    protected static ?string $title = 'Laporan RL 3 - Pelayanan';

    protected static ?string $slug = 'reports/rl-3';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 103;

    protected string $view = 'filament.pages.reports.rl3-report';

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $reportType = 'rl3_1';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->reportType = 'rl3_1';
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
                                'rl3_1' => 'RL 3.1 - Rawat Inap',
                                'rl3_2' => 'RL 3.2 - Rawat Jalan',
                                'rl3_3' => 'RL 3.3 - Gawat Darurat',
                                'rl3_4' => 'RL 3.4 - Kebidanan',
                                'rl3_5' => 'RL 3.5 - Perinatologi',
                                'rl3_6' => 'RL 3.6 - Bedah',
                                'rl3_7' => 'RL 3.7 - Radiologi',
                                'rl3_8' => 'RL 3.8 - Laboratorium',
                                'rl3_9' => 'RL 3.9 - Rehabilitasi Medik',
                                'rl3_10' => 'RL 3.10 - Kamar Operasi',
                                'rl3_11' => 'RL 3.11 - Intensif',
                                'rl3_12' => 'RL 3.12 - Gigi dan Mulut',
                                'rl3_13' => 'RL 3.13 - Farmasi',
                                'rl3_14' => 'RL 3.14 - Ambulance',
                                'rl3_15' => 'RL 3.15 - Pemulasaran Jenazah',
                            ])
                            ->required()
                            ->live()
                            ->default('rl3_1'),
                        
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

    public function getReportData(): array
    {
        $cacheKey = "rl3_{$this->reportType}_{$this->startDate}_{$this->endDate}";
        
        return Cache::remember($cacheKey, 300, function () {
            $reportService = app(ReportService::class);
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            return match ($this->reportType) {
                'rl3_1' => $this->getRL31Data($start, $end, $reportService),
                'rl3_2' => $this->getRL32Data($start, $end),
                'rl3_3' => $this->getRL33Data($start, $end),
                'rl3_4' => $this->getRL34Data($start, $end),
                'rl3_5' => $this->getRL35Data($start, $end),
                'rl3_6' => $this->getRL36Data($start, $end),
                'rl3_7' => $this->getRL37Data($start, $end),
                'rl3_8' => $this->getRL38Data($start, $end),
                'rl3_9' => $this->getRL39Data($start, $end),
                'rl3_10' => $this->getRL310Data($start, $end),
                'rl3_11' => $this->getRL311Data($start, $end),
                'rl3_12' => $this->getRL312Data($start, $end),
                'rl3_13' => $this->getRL313Data($start, $end),
                'rl3_14' => $this->getRL314Data($start, $end),
                'rl3_15' => $this->getRL315Data($start, $end),
                default => [],
            };
        });
    }

    private function getRL31Data(Carbon $start, Carbon $end, ReportService $service): array
    {
        $visits = Visit::where('visit_type', 'rawat_inap')
            ->whereBetween('admission_date', [$start, $end])
            ->get();

        return [
            'title' => 'RL 3.1 - Pelayanan Rawat Inap',
            'data' => [
                'pasien_masuk' => $visits->count(),
                'pasien_keluar' => $visits->whereNotNull('discharge_date')->count(),
                'lama_dirawat' => $visits->whereNotNull('discharge_date')
                    ->sum(fn ($v) => Carbon::parse($v->admission_date)->diffInDays($v->discharge_date)),
                'bor' => $service->calculateBOR($start, $end),
                'los' => $service->calculateLOS($start, $end),
                'toi' => $service->calculateTOI($start, $end),
                'bto' => $service->calculateBTO($start, $end),
                'jumlah_bed' => Bed::active()->count(),
                'kelas_breakdown' => Room::active()
                    ->selectRaw('room_class, COUNT(*) as room_count, SUM(total_beds) as bed_count')
                    ->groupBy('room_class')
                    ->get(),
            ],
        ];
    }

    private function getRL32Data(Carbon $start, Carbon $end): array
    {
        $visits = Visit::where('visit_type', 'rawat_jalan')
            ->whereBetween('visit_date', [$start, $end])
            ->with('polyclinic')
            ->get();

        return [
            'title' => 'RL 3.2 - Pelayanan Rawat Jalan',
            'data' => [
                'total_kunjungan' => $visits->count(),
                'pasien_baru' => Patient::whereBetween('first_visit_at', [$start, $end])->count(),
                'pasien_lama' => $visits->whereNotNull('patient_id')
                    ->filter(fn ($v) => $v->patient && $v->patient->first_visit_at < $start)
                    ->count(),
                'by_polyclinic' => $visits->groupBy('polyclinic.name')
                    ->map(fn ($group) => $group->count())
                    ->sortDesc(),
                'by_gender' => [
                    'L' => $visits->where('patient.gender', 'L')->count(),
                    'P' => $visits->where('patient.gender', 'P')->count(),
                ],
            ],
        ];
    }

    private function getRL33Data(Carbon $start, Carbon $end): array
    {
        $visits = Visit::where('visit_type', 'igd')
            ->whereBetween('visit_date', [$start, $end])
            ->get();

        return [
            'title' => 'RL 3.3 - Pelayanan Gawat Darurat',
            'data' => [
                'total_kunjungan' => $visits->count(),
                'pasien_rujukan' => $visits->whereNotNull('referral_from')->count(),
                'pasien_non_rujukan' => $visits->whereNull('referral_from')->count(),
                'doa' => $visits->where('discharge_status', 'meninggal')->count(),
                'dirawat' => $visits->where('discharge_status', 'rawat_inap')->count(),
                'pulang' => $visits->where('discharge_status', 'pulang')->count(),
                'dirujuk' => $visits->where('discharge_status', 'dirujuk')->count(),
            ],
        ];
    }

    private function getRL34Data(Carbon $start, Carbon $end): array
    {
        // Obstetrics and Gynecology data
        return [
            'title' => 'RL 3.4 - Pelayanan Kebidanan',
            'data' => [
                'k1' => 0, // First pregnancy visit
                'k4' => 0, // Fourth pregnancy visit
                'persalinan_normal' => 0,
                'persalinan_cesar' => 0,
                'komplikasi_kebidanan' => 0,
            ],
        ];
    }

    private function getRL35Data(Carbon $start, Carbon $end): array
    {
        // Perinatology data
        return [
            'title' => 'RL 3.5 - Pelayanan Perinatologi',
            'data' => [
                'bayi_lahir' => 0,
                'bblr' => 0, // Low birth weight
                'asfiksia' => 0,
                'neonatus_meninggal' => 0,
            ],
        ];
    }

    private function getRL36Data(Carbon $start, Carbon $end): array
    {
        // Surgery data
        return [
            'title' => 'RL 3.6 - Pelayanan Bedah',
            'data' => [
                'operasi_major' => 0,
                'operasi_medium' => 0,
                'operasi_minor' => 0,
                'operasi_emergency' => 0,
                'operasi_elektif' => 0,
            ],
        ];
    }

    private function getRL37Data(Carbon $start, Carbon $end): array
    {
        $orders = RadiologyOrder::whereBetween('order_date', [$start, $end])->get();

        return [
            'title' => 'RL 3.7 - Pelayanan Radiologi',
            'data' => [
                'total_pemeriksaan' => $orders->count(),
                'cito' => $orders->where('priority', 'cito')->count(),
                'rutin' => $orders->where('priority', 'normal')->count(),
            ],
        ];
    }

    private function getRL38Data(Carbon $start, Carbon $end): array
    {
        $orders = LaboratoryOrder::whereBetween('order_date', [$start, $end])->get();

        return [
            'title' => 'RL 3.8 - Pelayanan Laboratorium',
            'data' => [
                'total_pemeriksaan' => $orders->count(),
                'cito' => $orders->where('is_cito', true)->count(),
                'rutin' => $orders->where('is_cito', false)->count(),
                'hasil_validated' => $orders->where('status', 'validated')->count(),
            ],
        ];
    }

    private function getRL39Data(Carbon $start, Carbon $end): array
    {
        return [
            'title' => 'RL 3.9 - Pelayanan Rehabilitasi Medik',
            'data' => [
                'fisioterapi' => 0,
                'okupasi' => 0,
                'terapi_wicara' => 0,
            ],
        ];
    }

    private function getRL310Data(Carbon $start, Carbon $end): array
    {
        return [
            'title' => 'RL 3.10 - Pelayanan Kamar Operasi',
            'data' => [
                'operasi_bersih' => 0,
                'operasi_bersih_tercemar' => 0,
                'operasi_tercemar' => 0,
                'operasi_kotor' => 0,
            ],
        ];
    }

    private function getRL311Data(Carbon $start, Carbon $end): array
    {
        return [
            'title' => 'RL 3.11 - Pelayanan Intensif',
            'data' => [
                'icu' => Visit::where('visit_type', 'rawat_inap')
                    ->whereHas('room', fn ($q) => $q->where('room_class', 'ICU'))
                    ->whereBetween('admission_date', [$start, $end])
                    ->count(),
                'nicu' => Visit::where('visit_type', 'rawat_inap')
                    ->whereHas('room', fn ($q) => $q->where('room_class', 'NICU'))
                    ->whereBetween('admission_date', [$start, $end])
                    ->count(),
                'picu' => Visit::where('visit_type', 'rawat_inap')
                    ->whereHas('room', fn ($q) => $q->where('room_class', 'PICU'))
                    ->whereBetween('admission_date', [$start, $end])
                    ->count(),
            ],
        ];
    }

    private function getRL312Data(Carbon $start, Carbon $end): array
    {
        $visits = Visit::where('visit_type', 'rawat_jalan')
            ->whereHas('polyclinic', fn ($q) => $q->where('category', 'gigi'))
            ->whereBetween('visit_date', [$start, $end])
            ->get();

        return [
            'title' => 'RL 3.12 - Pelayanan Gigi dan Mulut',
            'data' => [
                'total_kunjungan' => $visits->count(),
            ],
        ];
    }

    private function getRL313Data(Carbon $start, Carbon $end): array
    {
        $prescriptions = Prescription::whereBetween('prescription_date', [$start, $end])->get();

        return [
            'title' => 'RL 3.13 - Pelayanan Farmasi',
            'data' => [
                'total_resep' => $prescriptions->count(),
                'resep_lengkap' => $prescriptions->where('status', 'completed')->count(),
                'resep_racikan' => $prescriptions->where('prescription_type', 'racikan')->count(),
                'resep_non_racikan' => $prescriptions->where('prescription_type', 'non_racikan')->count(),
            ],
        ];
    }

    private function getRL314Data(Carbon $start, Carbon $end): array
    {
        return [
            'title' => 'RL 3.14 - Pelayanan Ambulance',
            'data' => [
                'pelayanan_darurat' => 0,
                'pelayanan_non_darurat' => 0,
                'rujukan' => 0,
            ],
        ];
    }

    private function getRL315Data(Carbon $start, Carbon $end): array
    {
        return [
            'title' => 'RL 3.15 - Pelayanan Pemulasaran Jenazah',
            'data' => [
                'jenazah_dirawat' => 0,
                'jenazah_dibawa_pulang' => 0,
            ],
        ];
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
