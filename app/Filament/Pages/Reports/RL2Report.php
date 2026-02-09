<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use BackedEnum;
use UnitEnum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use App\Models\MasterData\Employee;
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
use Illuminate\Support\Facades\DB;

class RL2Report extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'RL 2 - Tenaga Medis';

    protected static ?string $title = 'Laporan RL 2 - Tenaga Medis';

    protected static ?string $slug = 'reports/rl-2';

    protected static string | UnitEnum | null $navigationGroup = 'Laporan & Analitik';

    protected static ?int $navigationSort = 102;

    protected string $view = 'filament.pages.reports.rl2-report';

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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('status', 'aktif')
                    ->with('specialistPolyclinic')
            )
            ->columns([
                TextColumn::make('employee_code')
                    ->label('Kode Pegawai')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('profession_category')
                    ->label('Kategori')
                    ->state(function (Employee $record): string {
                        if ($record->is_doctor) return 'Dokter';
                        if ($record->is_nurse) return 'Perawat';
                        if ($record->profession && str_contains(strtolower($record->profession), 'bidan')) return 'Bidan';
                        if ($record->profession && str_contains(strtolower($record->profession), 'farmasi')) return 'Farmasi';
                        return 'Lainnya';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dokter' => 'primary',
                        'Perawat' => 'success',
                        'Bidan' => 'warning',
                        'Farmasi' => 'info',
                        default => 'gray',
                    }),
                
                TextColumn::make('specialistPolyclinic.name')
                    ->label('Spesialisasi')
                    ->default('-'),
                
                TextColumn::make('sip_number')
                    ->label('No. SIP')
                    ->default('-'),
                
                TextColumn::make('sip_expiry_date')
                    ->label('SIP Berlaku Sampai')
                    ->date('d M Y')
                    ->default('-')
                    ->badge()
                    ->color(function (Employee $record): string {
                        if (!$record->sip_expiry_date) return 'gray';
                        if ($record->sip_expiry_date->isPast()) return 'danger';
                        if ($record->sip_expiry_date->diffInDays(now()) <= 30) return 'warning';
                        return 'success';
                    }),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'cuti' => 'warning',
                        'nonaktif' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'doctor' => 'Dokter',
                        'nurse' => 'Perawat',
                        'midwife' => 'Bidan',
                        'pharmacist' => 'Farmasi',
                    ])
                    ->query(function ($query, $data) {
                        if (empty($data['value'])) return $query;
                        
                        return match ($data['value']) {
                            'doctor' => $query->where('is_doctor', true),
                            'nurse' => $query->where('is_nurse', true),
                            'midwife' => $query->where('profession', 'like', '%bidan%'),
                            'pharmacist' => $query->where('profession', 'like', '%farmasi%'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Employee $record): string => route('filament.admin.resources.employees.view', ['record' => $record])),
            ])
            ->defaultSort('name');
    }

    public function getStatistics(): array
    {
        $cacheKey = "rl2_statistics_{$this->startDate}_{$this->endDate}";
        
        return Cache::remember($cacheKey, 300, function () {
            $activeEmployees = Employee::where('status', 'aktif');

            return [
                'total_doctors' => (clone $activeEmployees)->where('is_doctor', true)->count(),
                'specialists' => (clone $activeEmployees)
                    ->where('is_doctor', true)
                    ->whereNotNull('specialist_polyclinic_id')
                    ->count(),
                'general_practitioners' => (clone $activeEmployees)
                    ->where('is_doctor', true)
                    ->whereNull('specialist_polyclinic_id')
                    ->count(),
                'total_nurses' => (clone $activeEmployees)->where('is_nurse', true)->count(),
                'total_midwives' => (clone $activeEmployees)
                    ->where('profession', 'like', '%bidan%')
                    ->count(),
                'total_pharmacists' => (clone $activeEmployees)
                    ->where('profession', 'like', '%farmasi%')
                    ->count(),
                'total_employees' => (clone $activeEmployees)->count(),
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
