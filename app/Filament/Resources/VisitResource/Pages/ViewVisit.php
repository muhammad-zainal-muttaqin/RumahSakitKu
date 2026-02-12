<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\VisitResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewVisit extends ViewRecord
{
    protected static string $resource = VisitResource::class;

    public function infolist(Schema $schema): Schema
    {
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
                            ->label('Tanggal Kunjungan')
                            ->icon('heroicon-m-calendar')
                            ->date('d M Y'),

                        TextEntry::make('visit_type')
                            ->label('Jenis Kunjungan')
                            ->icon('heroicon-m-building-office')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'rawat_jalan' => 'Rawat Jalan',
                                'rawat_inap' => 'Rawat Inap',
                                'igd' => 'IGD',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'rawat_jalan' => 'primary',
                                'rawat_inap' => 'warning',
                                'igd' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('registration_type')
                            ->label('Jenis Pendaftaran')
                            ->icon('heroicon-m-document-text')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'baru' => 'Baru',
                                'lama' => 'Lama',
                                'rujukan' => 'Rujukan',
                                'kontrol' => 'Kontrol',
                                default => ucfirst($state),
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

                \Filament\Schemas\Components\Section::make('Lokasi dan Dokter')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        TextEntry::make('polyclinic.name')
                            ->label('Poliklinik')
                            ->icon('heroicon-m-building-office-2'),

                        TextEntry::make('doctor.name')
                            ->label('Dokter')
                            ->icon('heroicon-m-user-circle')
                            ->placeholder('Belum ditugaskan'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Keluhan dan Prioritas')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        TextEntry::make('priority')
                            ->label('Prioritas')
                            ->icon('heroicon-m-flag')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'normal' => 'Normal',
                                'urgent' => 'Urgent',
                                'emergency' => 'Emergency',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'normal' => 'gray',
                                'urgent' => 'warning',
                                'emergency' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('complaint')
                            ->label('Keluhan')
                            ->icon('heroicon-m-clipboard')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                \Filament\Schemas\Components\Section::make('Informasi Rujukan')
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

                \Filament\Schemas\Components\Section::make('Informasi BPJS')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextEntry::make('bpjs_sep_number')
                            ->label('Nomor SEP BPJS')
                            ->icon('heroicon-m-identification')
                            ->placeholder('-'),
                    ])
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Status Kunjungan')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->icon('heroicon-m-signal')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'registered' => 'Terdaftar',
                                'waiting' => 'Menunggu',
                                'in_progress' => 'Sedang Dilayani',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                default => ucfirst($state),
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'registered' => 'info',
                                'waiting' => 'warning',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

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

                        TextEntry::make('duration')
                            ->label('Durasi (menit)')
                            ->icon('heroicon-m-clock')
                            ->state(function ($record): ?int {
                                return $record->duration;
                            })
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Rekam Medis')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record): bool => $record->medicalRecord !== null)
                    ->schema([
                        TextEntry::make('medicalRecord.medical_record_number')
                            ->label('Nomor Rekam Medis')
                            ->icon('heroicon-m-document-text'),

                        TextEntry::make('medicalRecord.created_at')
                            ->label('Dibuat Pada')
                            ->icon('heroicon-m-calendar')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Invoice')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn ($record): bool => $record->invoice !== null)
                    ->schema([
                        TextEntry::make('invoice.invoice_number')
                            ->label('Nomor Invoice')
                            ->icon('heroicon-m-document-currency-dollar'),

                        TextEntry::make('invoice.total_amount')
                            ->label('Total')
                            ->icon('heroicon-m-currency-dollar')
                            ->money('IDR'),

                        TextEntry::make('invoice.status')
                            ->label('Status Invoice')
                            ->icon('heroicon-m-signal')
                            ->badge(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Catatan')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

