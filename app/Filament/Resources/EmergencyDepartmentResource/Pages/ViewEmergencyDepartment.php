<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\EmergencyDepartmentResource;
use App\Models\Clinical\Assessment;
use App\Services\TriageService;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewEmergencyDepartment extends ViewRecord
{
    protected static string $resource = EmergencyDepartmentResource::class;

    protected static ?string $title = 'Detail Pasien IGD';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Pasien')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('patient.name')
                            ->label('Nama Pasien'),
                        TextEntry::make('patient.medical_record_number')
                            ->label('Nomor RM'),
                        TextEntry::make('patient.gender')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn (string $state): string => $state === 'male' ? 'Laki-laki' : 'Perempuan'),
                        TextEntry::make('patient.age')
                            ->label('Usia')
                            ->formatStateUsing(fn ($state): string => $state . ' tahun'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Informasi Kunjungan')
                    ->icon('heroicon-o-clipboard-document')
                    ->schema([
                        TextEntry::make('visit_number')
                            ->label('Nomor Kunjungan'),
                        TextEntry::make('visit_date')
                            ->label('Tanggal Kunjungan')
                            ->date('d M Y'),
                        TextEntry::make('status')
                            ->label('Status')
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
                        TextEntry::make('check_in_at')
                            ->label('Waktu Check-in')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('check_out_at')
                            ->label('Waktu Check-out')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Data Triase')
                    ->icon('heroicon-o-heart')
                    ->visible(fn (Model $record): bool => $record->medicalRecord?->assessments->isNotEmpty() ?? false)
                    ->schema([
                        TextEntry::make('triage_category')
                            ->label('Kategori Triase')
                            ->getStateUsing(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                return $assessment?->triage_category ?? 'unknown';
                            })
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => TriageService::getCategoryShortLabel($state))
                            ->color(fn (string $state): string => TriageService::getCategoryColor($state)),

                        TextEntry::make('chief_complaint')
                            ->label('Keluhan Utama')
                            ->getStateUsing(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                return $assessment?->chief_complaint ?? '-';
                            }),

                        TextEntry::make('vital_signs_summary')
                            ->label('Ringkasan TTV')
                            ->getStateUsing(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                $vitalSigns = $assessment?->vital_signs ?? [];

                                $bp = ($vitalSigns['systolic_bp'] ?? '-') . '/' . ($vitalSigns['diastolic_bp'] ?? '-');
                                $hr = $vitalSigns['heart_rate'] ?? '-';
                                $rr = $vitalSigns['respiratory_rate'] ?? '-';
                                $temp = $vitalSigns['body_temperature'] ?? '-';
                                $spo2 = $vitalSigns['oxygen_saturation'] ?? '-';

                                return "TD: {$bp} mmHg | HR: {$hr} bpm | RR: {$rr} x/menit | Suhu: {$temp}°C | SpO2: {$spo2}%";
                            })
                            ->columnSpanFull(),

                        TextEntry::make('gcs_total')
                            ->label('GCS Total')
                            ->getStateUsing(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                $vitalSigns = $assessment?->vital_signs ?? [];
                                $eye = $vitalSigns['gcs_eye'] ?? 0;
                                $verbal = $vitalSigns['gcs_verbal'] ?? 0;
                                $motor = $vitalSigns['gcs_motor'] ?? 0;
                                $total = (int) $eye + (int) $verbal + (int) $motor;

                                return $total > 0 ? (string) $total : '-';
                            })
                            ->badge()
                            ->color(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                $vitalSigns = $assessment?->vital_signs ?? [];
                                $eye = $vitalSigns['gcs_eye'] ?? 0;
                                $verbal = $vitalSigns['gcs_verbal'] ?? 0;
                                $motor = $vitalSigns['gcs_motor'] ?? 0;
                                $total = (int) $eye + (int) $verbal + (int) $motor;

                                return match (true) {
                                    $total === 0 => 'gray',
                                    $total >= 13 => 'success',
                                    $total >= 9 => 'warning',
                                    default => 'danger',
                                };
                            }),

                        TextEntry::make('assessed_by')
                            ->label('Petugas Triase')
                            ->getStateUsing(function (Model $record): string {
                                $assessment = $record->medicalRecord?->assessments->first();
                                return $assessment?->assessedBy?->name ?? '-';
                            }),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Dokter Penanggung Jawab')
                    ->icon('heroicon-o-user-md')
                    ->schema([
                        TextEntry::make('doctor.name')
                            ->label('Nama Dokter')
                            ->placeholder('Belum ditugaskan'),
                    ])
                    ->visible(fn (Model $record): bool => $record->doctor !== null)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                    ])
                    ->visible(fn (Model $record): bool => !empty($record->notes))
                    ->collapsible(),
            ]);
    }
}
