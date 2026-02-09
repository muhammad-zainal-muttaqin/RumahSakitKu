<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssessmentResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use App\Filament\Resources\AssessmentResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewAssessment extends ViewRecord
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit'),
            DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Asesmen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('medicalRecord.record_number')
                            ->label('Nomor Rekam Medis'),
                        TextEntry::make('patient.name')
                            ->label('Nama Pasien'),
                        BadgeEntry::make('assessment_type')
                            ->label('Tipe Asesmen')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'triage' => 'Triase (IGD)',
                                'awal_perawat' => 'Asesmen Awal Perawat',
                                'awal_dokter' => 'Asesmen Awal Dokter',
                                'lanjutan' => 'Asesmen Lanjutan',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'triage' => 'danger',
                                'awal_perawat' => 'info',
                                'awal_dokter' => 'primary',
                                'lanjutan' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('assessedBy.name')
                            ->label('Petugas/Pelaksana'),
                        TextEntry::make('assessment_date')
                            ->label('Tanggal Asesmen')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Tanda-Tanda Vital (TTV)')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('vital_signs.systolic_bp')
                                    ->label('Tekanan Darah Sistolik')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' mmHg' : '-'),
                                TextEntry::make('vital_signs.diastolic_bp')
                                    ->label('Tekanan Darah Diastolik')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' mmHg' : '-'),
                                TextEntry::make('bp_status')
                                    ->label('Status Tekanan Darah')
                                    ->getStateUsing(function ($record): string {
                                        $vs = $record->vital_signs ?? [];
                                        $sys = $vs['systolic_bp'] ?? 0;
                                        $dia = $vs['diastolic_bp'] ?? 0;
                                        
                                        if (!$sys || !$dia) return '-';
                                        
                                        if ($sys < 120 && $dia < 80) return 'Normal';
                                        if ($sys < 130 && $dia < 80) return 'Pre-hipertensi';
                                        if ($sys < 140 || $dia < 90) return 'Hipertensi Stage 1';
                                        return 'Hipertensi Stage 2';
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Normal' => 'success',
                                        'Pre-hipertensi' => 'warning',
                                        'Hipertensi Stage 1' => 'orange',
                                        'Hipertensi Stage 2' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('vital_signs.heart_rate')
                                    ->label('Denyut Jantung')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' bpm' : '-'),
                                TextEntry::make('vital_signs.respiratory_rate')
                                    ->label('Pernapasan')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' x/menit' : '-'),
                                TextEntry::make('vital_signs.body_temperature')
                                    ->label('Suhu Tubuh')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' °C' : '-'),
                                TextEntry::make('vital_signs.oxygen_saturation')
                                    ->label('Saturasi Oksigen (SpO2)')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' %' : '-'),
                                TextEntry::make('vital_signs.pain_scale')
                                    ->label('Skala Nyeri')
                                    ->formatStateUsing(function (?string $state): string {
                                        if ($state === null || $state === '') return '-';
                                        $pain = (int) $state;
                                        $label = match (true) {
                                            $pain === 0 => 'Tidak Nyeri',
                                            $pain <= 3 => 'Nyeri Ringan',
                                            $pain <= 6 => 'Nyeri Sedang',
                                            default => 'Nyeri Berat',
                                        };
                                        return "{$pain} ({$label})";
                                    })
                                    ->badge()
                                    ->color(function (?string $state): string {
                                        if ($state === null || $state === '') return 'gray';
                                        $pain = (int) $state;
                                        return match (true) {
                                            $pain === 0 => 'success',
                                            $pain <= 3 => 'info',
                                            $pain <= 6 => 'warning',
                                            default => 'danger',
                                        };
                                    }),
                            ]),
                    ]),

                Section::make('Status Kesadaran')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        TextEntry::make('physical_examination.consciousness_level')
                            ->label('Level Kesadaran')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'compos_mentis' => 'Compos Mentis (CM)',
                                'somnolence' => 'Somnolence',
                                'stupor' => 'Stupor',
                                'coma' => 'Coma',
                                default => $state ?? '-',
                            }),
                        Fieldset::make('Glasgow Coma Scale (GCS)')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('physical_examination.gcs_eye')
                                            ->label('Buka Mata (E)')
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                4 => '4 - Spontan',
                                                3 => '3 - Terhadap Suara',
                                                2 => '2 - Terhadap Nyeri',
                                                1 => '1 - Tidak Ada',
                                                default => '-',
                                            }),
                                        TextEntry::make('physical_examination.gcs_verbal')
                                            ->label('Respon Verbal (V)')
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                5 => '5 - Orientasi Baik',
                                                4 => '4 - Bingung',
                                                3 => '3 - Kata-kata Tak Jelas',
                                                2 => '2 - Suara Tak Jelas',
                                                1 => '1 - Tidak Ada',
                                                default => '-',
                                            }),
                                        TextEntry::make('physical_examination.gcs_motor')
                                            ->label('Respon Motorik (M)')
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                6 => '6 - Turuti Perintah',
                                                5 => '5 - Melokalisasi Nyeri',
                                                4 => '4 - Fleksi Withdrawal',
                                                3 => '3 - Fleksi Abnormal',
                                                2 => '2 - Ekstensi',
                                                1 => '1 - Tidak Ada',
                                                default => '-',
                                            }),
                                        TextEntry::make('gcs_total')
                                            ->label('Total GCS')
                                            ->getStateUsing(function ($record): string {
                                                $pe = $record->physical_examination ?? [];
                                                $total = (int) ($pe['gcs_eye'] ?? 0) + (int) ($pe['gcs_verbal'] ?? 0) + (int) ($pe['gcs_motor'] ?? 0);
                                                
                                                if ($total === 0) return '-';
                                                
                                                $status = match (true) {
                                                    $total >= 13 => 'Ringan',
                                                    $total >= 9 => 'Sedang',
                                                    $total >= 3 => 'Berat',
                                                    default => '-',
                                                };
                                                
                                                return "{$total} ({$status})";
                                            })
                                            ->badge()
                                            ->color(function ($record): string {
                                                $pe = $record->physical_examination ?? [];
                                                $total = (int) ($pe['gcs_eye'] ?? 0) + (int) ($pe['gcs_verbal'] ?? 0) + (int) ($pe['gcs_motor'] ?? 0);
                                                
                                                return match (true) {
                                                    $total === 0 => 'gray',
                                                    $total >= 13 => 'success',
                                                    $total >= 9 => 'warning',
                                                    default => 'danger',
                                                };
                                            }),
                                    ]),
                            ]),
                    ]),

                Section::make('Antropometri')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('vital_signs.weight_kg')
                                    ->label('Berat Badan')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' kg' : '-'),
                                TextEntry::make('vital_signs.height_cm')
                                    ->label('Tinggi Badan')
                                    ->formatStateUsing(fn (?string $state): string => $state ? $state . ' cm' : '-'),
                                TextEntry::make('bmi')
                                    ->label('BMI')
                                    ->getStateUsing(function ($record): string {
                                        $vs = $record->vital_signs ?? [];
                                        $weight = $vs['weight_kg'] ?? null;
                                        $height = $vs['height_cm'] ?? null;
                                        
                                        if (!$weight || !$height) return '-';
                                        
                                        $heightM = (float) $height / 100;
                                        if ($heightM <= 0) return '-';
                                        
                                        $bmi = round((float) $weight / ($heightM * $heightM), 2);
                                        return (string) $bmi;
                                    }),
                                TextEntry::make('bmi_category')
                                    ->label('Kategori BMI')
                                    ->getStateUsing(function ($record): string {
                                        $vs = $record->vital_signs ?? [];
                                        $weight = $vs['weight_kg'] ?? null;
                                        $height = $vs['height_cm'] ?? null;
                                        
                                        if (!$weight || !$height) return '-';
                                        
                                        $heightM = (float) $height / 100;
                                        if ($heightM <= 0) return '-';
                                        
                                        $bmi = (float) $weight / ($heightM * $heightM);
                                        
                                        return match (true) {
                                            $bmi < 18.5 => 'Kurus',
                                            $bmi < 25 => 'Normal',
                                            $bmi < 30 => 'Kelebihan Berat',
                                            default => 'Obesitas',
                                        };
                                    })
                                    ->badge()
                                    ->color(function ($record): string {
                                        $vs = $record->vital_signs ?? [];
                                        $weight = $vs['weight_kg'] ?? null;
                                        $height = $vs['height_cm'] ?? null;
                                        
                                        if (!$weight || !$height) return 'gray';
                                        
                                        $heightM = (float) $height / 100;
                                        if ($heightM <= 0) return 'gray';
                                        
                                        $bmi = (float) $weight / ($heightM * $heightM);
                                        
                                        return match (true) {
                                            $bmi < 18.5 => 'warning',
                                            $bmi < 25 => 'success',
                                            $bmi < 30 => 'warning',
                                            default => 'danger',
                                        };
                                    }),
                            ]),
                    ]),

                Section::make('Kategori Triase')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn ($record) => $record->assessment_type === 'triage' && $record->triage_category)
                    ->schema([
                        BadgeEntry::make('triage_category')
                            ->label('Kategori')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'red' => 'MERAH - Emergency',
                                'yellow' => 'KUNING - Urgent',
                                'green' => 'HIJAU - Non-Urgent',
                                'black' => 'HITAM - Deceased',
                                default => $state ?? '-',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'red' => 'danger',
                                'yellow' => 'warning',
                                'green' => 'success',
                                'black' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn ($record) => !empty($record->notes))
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan Tambahan')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
