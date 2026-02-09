<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use App\Models\Patient\Visit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;

class RecentVisitsTable extends BaseWidget
{
    protected static ?string $heading = 'Kunjungan Terbaru';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Visit::query()
                    ->with(['patient', 'polyclinic', 'doctor'])
                    ->orderByDesc('visit_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-semibold'),

                TextColumn::make('patient.name')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Visit $record): string => $record->patient?->medical_record_number ?? '-'),

                BadgeColumn::make('visit_type')
                    ->label('Jenis')
                    ->colors([
                        'primary' => 'rawat_jalan',
                        'danger' => 'igd',
                        'success' => 'rawat_inap',
                        'warning' => 'mcu',
                    ])
                    ->icons([
                        'heroicon-o-building-office' => 'rawat_jalan',
                        'heroicon-o-heart' => 'igd',
                        'heroicon-o-home' => 'rawat_inap',
                        'heroicon-o-clipboard-document-check' => 'mcu',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rawat_jalan' => 'Rawat Jalan',
                        'igd' => 'IGD',
                        'rawat_inap' => 'Rawat Inap',
                        'mcu' => 'MCU',
                        default => $state,
                    }),

                TextColumn::make('polyclinic.name')
                    ->label('Poliklinik')
                    ->default('-'),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->default('-'),

                TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pendaftaran',
                        'warning' => 'menunggu',
                        'info' => 'proses',
                        'success' => 'selesai',
                        'danger' => 'batal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendaftaran' => 'Pendaftaran',
                        'menunggu' => 'Menunggu',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => $state,
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Visit $record): string => route('filament.admin.resources.visits.view', ['record' => $record])),
            ])
            ->striped()
            ->paginated(false);
    }
}
