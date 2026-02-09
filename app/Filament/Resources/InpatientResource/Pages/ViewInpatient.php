<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\InpatientResource;
use App\Services\InpatientService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewInpatient extends ViewRecord
{
    protected static string $resource = InpatientResource::class;

    protected static ?string $title = 'Detail Pasien Rawat Inap';

    public function infolist(Schema $schema): Schema
    {
        $inpatientService = app(InpatientService::class);
        $lengthOfStay = $inpatientService->calculateLengthOfStay($this->record->visit_date);

        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Kunjungan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        TextEntry::make('visit_number')
                            ->label('Nomor Kunjungan')
                            ->icon('heroicon-m-hashtag')
                            ->weight('font-bold'),

                        TextEntry::make('visit_date')
                            ->label('Tanggal Masuk')
                            ->icon('heroicon-m-calendar')
                            ->date('d M Y'),

                        TextEntry::make('length_of_stay')
                            ->label('Lama Rawat Inap (LOS)')
                            ->icon('heroicon-m-clock')
                            ->state(fn () => "{$lengthOfStay} hari")
                            ->badge()
                            ->color(fn () => $lengthOfStay > 10 ? 'danger' : ($lengthOfStay > 5 ? 'warning' : 'success')),

                        TextEntry::make('inpatient_status')
                            ->label('Status Rawat Inap')
                            ->icon('heroicon-m-signal')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'registered' => 'Terdaftar',
                                'admitted' => 'Dirawat',
                                'discharge_planned' => 'Rencana Pulang',
                                'discharged' => 'Sudah Pulang',
                                'transferred' => 'Pindah',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'registered' => 'info',
                                'admitted' => 'primary',
                                'discharge_planned' => 'warning',
                                'discharged' => 'success',
                                'transferred' => 'purple',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Data Pasien')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('patient.name')
                            ->label('Nama Pasien')
                            ->icon('heroicon-m-user')
                            ->weight('font-medium'),

                        TextEntry::make('patient.medical_record_number')
                            ->label('Nomor RM')
                            ->icon('heroicon-m-identification'),

                        TextEntry::make('patient.nik')
                            ->label('NIK')
                            ->icon('heroicon-m-credit-card')
                            ->placeholder('-'),

                        TextEntry::make('patient.gender')
                            ->label('Jenis Kelamin')
                            ->icon('heroicon-m-user')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                                default => '-',
                            }),

                        TextEntry::make('patient.birth_date')
                            ->label('Tanggal Lahir')
                            ->icon('heroicon-m-cake')
                            ->date('d M Y'),

                        TextEntry::make('patient.age')
                            ->label('Usia')
                            ->icon('heroicon-m-calendar')
                            ->state(fn () => $this->record->patient?->age . ' tahun'),

                        TextEntry::make('patient.phone')
                            ->label('No. Telepon')
                            ->icon('heroicon-m-phone')
                            ->placeholder('-'),

                        TextEntry::make('patient.address')
                            ->label('Alamat')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Informasi Kamar')
                    ->icon('heroicon-o-home')
                    ->schema([
                        TextEntry::make('bed.room.name')
                            ->label('Nama Kamar')
                            ->icon('heroicon-m-home')
                            ->placeholder('Belum ditugaskan'),

                        TextEntry::make('bed.room.room_class')
                            ->label('Kelas Kamar')
                            ->icon('heroicon-m-star')
                            ->badge()
                            ->placeholder('-')
                            ->color(fn (?string $state): string => match ($state) {
                                'VVIP' => 'danger',
                                'VIP' => 'warning',
                                'Kelas I' => 'primary',
                                'Kelas II' => 'info',
                                'Kelas III' => 'success',
                                'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
                                default => 'gray',
                            }),

                        TextEntry::make('bed.bed_number')
                            ->label('Nomor Bed')
                            ->icon('heroicon-m-bed')
                            ->placeholder('-'),

                        TextEntry::make('bed.bed_name')
                            ->label('Nama Bed')
                            ->icon('heroicon-m-tag')
                            ->placeholder('-'),

                        TextEntry::make('bed.room.floor')
                            ->label('Lantai')
                            ->icon('heroicon-m-building-office'),

                        TextEntry::make('bed.room.building')
                            ->label('Gedung')
                            ->icon('heroicon-m-building-library')
                            ->placeholder('-'),

                        TextEntry::make('room_price')
                            ->label('Tarif Kamar')
                            ->icon('heroicon-m-currency-dollar')
                            ->state(function () {
                                $room = $this->record->bed?->room;
                                if (!$room) {
                                    return '-';
                                }
                                $paymentType = $this->record->payment_type ?? 'umum';
                                $price = $paymentType === 'bpjs' ? $room->bpjs_price : $room->base_price;
                                return 'Rp ' . number_format($price, 0, ',', '.') . '/hari';
                            }),

                        TextEntry::make('estimated_room_cost')
                            ->label('Estimasi Biaya Kamar')
                            ->icon('heroicon-m-calculator')
                            ->state(function () use ($lengthOfStay) {
                                $room = $this->record->bed?->room;
                                if (!$room) {
                                    return '-';
                                }
                                $paymentType = $this->record->payment_type ?? 'umum';
                                $price = $paymentType === 'bpjs' ? $room->bpjs_price : $room->base_price;
                                $total = $price * $lengthOfStay;
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Dokter Penanggung Jawab')
                    ->icon('heroicon-o-user-md')
                    ->schema([
                        TextEntry::make('doctor.name')
                            ->label('Dokter PJ')
                            ->icon('heroicon-m-user-md')
                            ->placeholder('Belum ditugaskan'),

                        TextEntry::make('admission_diagnosis')
                            ->label('Diagnosa Masuk')
                            ->icon('heroicon-m-clipboard-document')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Cara Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextEntry::make('payment_type')
                            ->label('Cara Bayar')
                            ->icon('heroicon-m-credit-card')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'umum' => 'Umum',
                                'bpjs' => 'BPJS',
                                'asuransi' => 'Asuransi',
                                'perusahaan' => 'Perusahaan',
                                'karyawan' => 'Karyawan',
                                default => ucfirst($state ?? '-'),
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'umum' => 'gray',
                                'bpjs' => 'success',
                                'asuransi' => 'primary',
                                'perusahaan' => 'info',
                                'karyawan' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('insurance_name')
                            ->label('Nama Asuransi/Perusahaan')
                            ->icon('heroicon-m-building-office')
                            ->placeholder('-'),

                        TextEntry::make('insurance_number')
                            ->label('Nomor Asuransi')
                            ->icon('heroicon-m-identification')
                            ->placeholder('-'),

                        TextEntry::make('deposit_amount')
                            ->label('Deposit')
                            ->icon('heroicon-m-banknotes')
                            ->state(fn () => $this->record->deposit_amount 
                                ? 'Rp ' . number_format($this->record->deposit_amount, 0, ',', '.') 
                                : '-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Rujukan')
                    ->icon('heroicon-o-share')
                    ->schema([
                        TextEntry::make('referral_from')
                            ->label('Rujukan Dari')
                            ->icon('heroicon-m-building-library')
                            ->placeholder('-'),

                        TextEntry::make('referral_number')
                            ->label('Nomor Rujukan')
                            ->icon('heroicon-m-document')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Keluhan dan Keterangan')
                    ->icon('heroicon-o-clipboard')
                    ->schema([
                        TextEntry::make('complaint')
                            ->label('Keluhan Utama')
                            ->icon('heroicon-m-clipboard')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->icon('heroicon-m-pencil')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Informasi Pulang')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (): bool => $this->record->inpatient_status === 'discharged')
                    ->schema([
                        TextEntry::make('discharge_date')
                            ->label('Tanggal Pulang')
                            ->icon('heroicon-m-calendar')
                            ->date('d M Y'),

                        TextEntry::make('discharge_status')
                            ->label('Status Pulang')
                            ->icon('heroicon-m-flag')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'sembuh' => 'Sembuh',
                                'membaik' => 'Membaik',
                                'belum_sembuh' => 'Belum Sembuh',
                                'meninggal' => 'Meninggal',
                                'dirujuk' => 'Dirujuk',
                                'kabur' => 'Kabur',
                                'atas_permintaan' => 'Atas Permintaan Sendiri',
                                default => ucfirst($state ?? '-'),
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'sembuh' => 'success',
                                'membaik' => 'primary',
                                'belum_sembuh' => 'warning',
                                'meninggal' => 'danger',
                                'dirujuk' => 'info',
                                'kabur' => 'danger',
                                'atas_permintaan' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('discharge_diagnosis')
                            ->label('Diagnosa Akhir')
                            ->icon('heroicon-m-document-text')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('discharge_notes')
                            ->label('Catatan Pulang')
                            ->icon('heroicon-m-pencil')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Rencana Pulang')
                    ->icon('heroicon-o-clock')
                    ->visible(fn (): bool => $this->record->inpatient_status === 'discharge_planned')
                    ->schema([
                        TextEntry::make('planned_discharge_date')
                            ->label('Tanggal Rencana Pulang')
                            ->icon('heroicon-m-calendar')
                            ->date('d M Y'),

                        TextEntry::make('discharge_plan_notes')
                            ->label('Catatan Rencana Pulang')
                            ->icon('heroicon-m-pencil')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Status Kunjungan')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        TextEntry::make('is_completed')
                            ->label('Selesai')
                            ->icon('heroicon-m-check-badge')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                        TextEntry::make('check_in_at')
                            ->label('Waktu Check-in')
                            ->icon('heroicon-m-play')
                            ->dateTime('d M Y H:i')
                            ->placeholder('Belum check-in'),

                        TextEntry::make('check_out_at')
                            ->label('Waktu Check-out')
                            ->icon('heroicon-m-stop')
                            ->dateTime('d M Y H:i')
                            ->placeholder('Belum check-out'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            
            Action::make('printSummary')
                ->label('Cetak Ringkasan')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->url(fn (): string => route('inpatient.summary', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
