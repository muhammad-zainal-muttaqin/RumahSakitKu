<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AssessmentResource\Pages\ListAssessments;
use App\Filament\Resources\AssessmentResource\Pages\CreateAssessment;
use App\Filament\Resources\AssessmentResource\Pages\ViewAssessment;
use App\Filament\Resources\AssessmentResource\Pages\EditAssessment;
use App\Filament\Resources\AssessmentResource\Pages;

/**
 * Assessment Resource
 * 
 * Filament resource for managing patient assessments.
 * 
 * @package App\Filament\Resources
 */

use App\Models\Clinical\Assessment;
use BackedEnum;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Asesmen & TTV';

    protected static ?string $modelLabel = 'Asesmen';

    protected static ?string $pluralModelLabel = 'Asesmen & TTV';

    protected static ?int $navigationSort = 21;

    protected static UnitEnum|string|null $navigationGroup = 'Rekam Medis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section 1: Info Asesmen
                Section::make('Informasi Asesmen')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('medical_record_id')
                            ->label('Rekam Medis')
                            ->relationship('medicalRecord', 'record_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih rekam medis')
                            ->suffixIcon('heroicon-m-identification'),

                        Select::make('patient_id')
                            ->label('Pasien')
                            ->relationship('patient', 'name')
                            ->searchable(['name', 'medical_record_number', 'nik'])
                            ->preload()
                            ->required()
                            ->placeholder('Pilih pasien')
                            ->suffixIcon('heroicon-m-user'),

                        Select::make('assessment_type')
                            ->label('Tipe Asesmen')
                            ->required()
                            ->options([
                                'triage' => 'Triase (IGD)',
                                'awal_perawat' => 'Asesmen Awal Perawat',
                                'awal_dokter' => 'Asesmen Awal Dokter',
                                'lanjutan' => 'Asesmen Lanjutan',
                            ])
                            ->native(false)
                            ->live(),

                        Select::make('assessed_by')
                            ->label('Petugas/Pelaksana')
                            ->relationship('assessedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih petugas')
                            ->suffixIcon('heroicon-m-user-circle'),

                        DateTimePicker::make('assessment_date')
                            ->label('Tanggal & Waktu Asesmen')
                            ->required()
                            ->default(now())
                            ->suffixIcon('heroicon-m-calendar'),

                        Textarea::make('chief_complaint')
                            ->label('Keluhan Utama')
                            ->placeholder('Masukkan keluhan utama pasien')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section 2: TTV Grid
                Section::make('Tanda-Tanda Vital (TTV)')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                // Blood Pressure
                                Fieldset::make('Tekanan Darah')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('vital_signs.systolic_bp')
                                                    ->label('Sistolik (mmHg)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(300)
                                                    ->placeholder('120')
                                                    ->suffixIcon('heroicon-m-heart')
                                                    ->live()
                                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                                        self::updateBpStatus($get, $set);
                                                    }),

                                                TextInput::make('vital_signs.diastolic_bp')
                                                    ->label('Diastolik (mmHg)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(200)
                                                    ->placeholder('80')
                                                    ->live()
                                                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) {
                                                        self::updateBpStatus($get, $set);
                                                    }),
                                            ]),

                                        Placeholder::make('bp_status_display')
                                            ->label('Status TD')
                                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                                                $systolic = $get('vital_signs.systolic_bp');
                                                $diastolic = $get('vital_signs.diastolic_bp');

                                                if (!$systolic || !$diastolic) {
                                                    return '-';
                                                }

                                                return self::getBpStatusLabel((int) $systolic, (int) $diastolic);
                                            })
                                            ->hidden(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => !$get('vital_signs.systolic_bp') || !$get('vital_signs.diastolic_bp')),
                                    ]),

                                // Heart Rate
                                TextInput::make('vital_signs.heart_rate')
                                    ->label('Denyut Jantung (bpm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(300)
                                    ->placeholder('72')
                                    ->suffixIcon('heroicon-m-heart')
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $hr = $get('vital_signs.heart_rate');
                                        if (!$hr) return null;
                                        return $hr >= 60 && $hr <= 100 ? '✓ Normal' : '⚠ Periksa';
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $hr = $get('vital_signs.heart_rate');
                                        if (!$hr) return null;
                                        return ($hr >= 60 && $hr <= 100) ? 'success' : 'warning';
                                    }),

                                // Respiratory Rate
                                TextInput::make('vital_signs.respiratory_rate')
                                    ->label('Pernapasan (x/menit)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('16')
                                    ->suffixIcon('heroicon-m-wind')
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $rr = $get('vital_signs.respiratory_rate');
                                        if (!$rr) return null;
                                        return $rr >= 12 && $rr <= 20 ? '✓ Normal' : '⚠ Periksa';
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $rr = $get('vital_signs.respiratory_rate');
                                        if (!$rr) return null;
                                        return ($rr >= 12 && $rr <= 20) ? 'success' : 'warning';
                                    }),

                                // Body Temperature
                                TextInput::make('vital_signs.body_temperature')
                                    ->label('Suhu Tubuh (°C)')
                                    ->numeric()
                                    ->minValue(30)
                                    ->maxValue(45)
                                    ->step(0.1)
                                    ->placeholder('36.5')
                                    ->suffixIcon('heroicon-m-thermometer')
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $temp = $get('vital_signs.body_temperature');
                                        if (!$temp) return null;
                                        $t = (float) $temp;
                                        return $t >= 36.1 && $t <= 37.2 ? '✓ Normal' : ($t >= 38 ? '⚠ Demam' : '⚠ Rendah');
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $temp = $get('vital_signs.body_temperature');
                                        if (!$temp) return null;
                                        $t = (float) $temp;
                                        return ($t >= 36.1 && $t <= 37.2) ? 'success' : 'warning';
                                    }),

                                // Oxygen Saturation
                                TextInput::make('vital_signs.oxygen_saturation')
                                    ->label('SpO2 (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.1)
                                    ->placeholder('98')
                                    ->suffixIcon('heroicon-m-bolt')
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $spo2 = $get('vital_signs.oxygen_saturation');
                                        if (!$spo2) return null;
                                        $s = (float) $spo2;
                                        return $s >= 95 ? '✓ Normal' : ($s >= 90 ? '⚠ Hipoksia Ringan' : '⚠ Hipoksia Berat');
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $spo2 = $get('vital_signs.oxygen_saturation');
                                        if (!$spo2) return null;
                                        $s = (float) $spo2;
                                        return $s >= 95 ? 'success' : ($s >= 90 ? 'warning' : 'danger');
                                    }),

                                // Pain Scale
                                Select::make('vital_signs.pain_scale')
                                    ->label('Skala Nyeri (0-10)')
                                    ->options(array_combine(range(0, 10), range(0, 10)))
                                    ->native(false)
                                    ->suffixIcon('heroicon-m-face-frown')
                                    ->live()
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $pain = $get('vital_signs.pain_scale');
                                        if ($pain === null || $pain === '') return null;
                                        $p = (int) $pain;
                                        return match (true) {
                                            $p === 0 => 'Tidak Nyeri',
                                            $p <= 3 => 'Nyeri Ringan',
                                            $p <= 6 => 'Nyeri Sedang',
                                            default => 'Nyeri Berat',
                                        };
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $pain = $get('vital_signs.pain_scale');
                                        if ($pain === null || $pain === '') return null;
                                        $p = (int) $pain;
                                        return match (true) {
                                            $p === 0 => 'success',
                                            $p <= 3 => 'info',
                                            $p <= 6 => 'warning',
                                            default => 'danger',
                                        };
                                    }),
                            ]),
                    ]),

                // Section 3: Kesadaran (GCS)
                Section::make('Status Kesadaran')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Select::make('physical_examination.consciousness_level')
                            ->label('Level Kesadaran')
                            ->options([
                                'compos_mentis' => 'Compos Mentis (CM)',
                                'somnolence' => 'Somnolence',
                                'stupor' => 'Stupor',
                                'coma' => 'Coma',
                            ])
                            ->native(false)
                            ->columnSpanFull(),

                        Fieldset::make('Glasgow Coma Scale (GCS)')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('physical_examination.gcs_eye')
                                            ->label('Buka Mata (E)')
                                            ->options([
                                                4 => '4 - Spontan',
                                                3 => '3 - Terhadap Suara',
                                                2 => '2 - Terhadap Nyeri',
                                                1 => '1 - Tidak Ada',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::calculateGcs($get, $set)),

                                        Select::make('physical_examination.gcs_verbal')
                                            ->label('Respon Verbal (V)')
                                            ->options([
                                                5 => '5 - Orientasi Baik',
                                                4 => '4 - Bingung',
                                                3 => '3 - Kata-kata Tak Jelas',
                                                2 => '2 - Suara Tak Jelas',
                                                1 => '1 - Tidak Ada',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::calculateGcs($get, $set)),

                                        Select::make('physical_examination.gcs_motor')
                                            ->label('Respon Motorik (M)')
                                            ->options([
                                                6 => '6 - Turuti Perintah',
                                                5 => '5 - Melokalisasi Nyeri',
                                                4 => '4 - Fleksi Withdrawal',
                                                3 => '3 - Fleksi Abnormal',
                                                2 => '2 - Ekstensi',
                                                1 => '1 - Tidak Ada',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::calculateGcs($get, $set)),

                                        Placeholder::make('gcs_total_display')
                                            ->label('Total GCS')
                                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                                                $eye = (int) ($get('physical_examination.gcs_eye') ?? 0);
                                                $verbal = (int) ($get('physical_examination.gcs_verbal') ?? 0);
                                                $motor = (int) ($get('physical_examination.gcs_motor') ?? 0);
                                                $total = $eye + $verbal + $motor;

                                                if ($total === 0) {
                                                    return '-';
                                                }

                                                $status = match (true) {
                                                    $total >= 13 => 'Ringan',
                                                    $total >= 9 => 'Sedang',
                                                    $total >= 3 => 'Berat',
                                                    default => '-',
                                                };

                                                $color = match (true) {
                                                    $total >= 13 => 'success',
                                                    $total >= 9 => 'warning',
                                                    $total >= 3 => 'danger',
                                                    default => 'gray',
                                                };

                                                return "{$total} ({$status})";
                                            }),
                                    ]),
                            ]),
                    ]),

                // Section 4: Antropometri (BB, TB, BMI)
                Section::make('Antropometri')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('vital_signs.weight_kg')
                                    ->label('Berat Badan (kg)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(500)
                                    ->step(0.1)
                                    ->placeholder('60')
                                    ->suffixIcon('heroicon-m-scale')
                                    ->live()
                                    ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::calculateBmi($get, $set)),

                                TextInput::make('vital_signs.height_cm')
                                    ->label('Tinggi Badan (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(300)
                                    ->step(0.1)
                                    ->placeholder('165')
                                    ->suffixIcon('heroicon-m-arrows-up-down')
                                    ->live()
                                    ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::calculateBmi($get, $set)),

                                Fieldset::make('Hasil BMI')
                                    ->schema([
                                        Placeholder::make('bmi_value_display')
                                            ->label('Nilai BMI')
                                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                                                $weight = $get('vital_signs.weight_kg');
                                                $height = $get('vital_signs.height_cm');

                                                if (!$weight || !$height) {
                                                    return '-';
                                                }

                                                $heightM = (float) $height / 100;
                                                if ($heightM <= 0) {
                                                    return '-';
                                                }

                                                $bmi = round((float) $weight / ($heightM * $heightM), 2);
                                                return (string) $bmi;
                                            }),

                                        Placeholder::make('bmi_category_display')
                                            ->label('Kategori')
                                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                                                $weight = $get('vital_signs.weight_kg');
                                                $height = $get('vital_signs.height_cm');

                                                if (!$weight || !$height) {
                                                    return '-';
                                                }

                                                $heightM = (float) $height / 100;
                                                if ($heightM <= 0) {
                                                    return '-';
                                                }

                                                $bmi = (float) $weight / ($heightM * $heightM);

                                                return match (true) {
                                                    $bmi < 18.5 => 'Kurus',
                                                    $bmi < 25 => 'Normal',
                                                    $bmi < 30 => 'Kelebihan Berat',
                                                    default => 'Obesitas',
                                                };
                                            }),
                                    ]),
                            ]),
                    ]),

                // Section 5: Triage (if IGD)
                Section::make('Kategori Triase (IGD)')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('assessment_type') === 'triage')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('triage_category')
                                    ->label('Kategori Triase')
                                    ->options([
                                        'red' => 'MERAH - Emergency (Gawat Darurat)',
                                        'yellow' => 'KUNING - Urgent (Darurat)',
                                        'green' => 'HIJAU - Non-Urgent (Tidak Darurat)',
                                        'black' => 'HITAM - Deceased (Meninggal)',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->hint(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        $category = $get('triage_category');
                                        return match ($category) {
                                            'red' => 'Segera ditangani, resiko kematian tinggi',
                                            'yellow' => 'Dapat ditunda pelayanan < 60 menit',
                                            'green' => 'Dapat ditunda pelayanan > 60 menit',
                                            'black' => 'Meninggal/tidak ada harapan',
                                            default => null,
                                        };
                                    })
                                    ->hintColor(function (\Filament\Schemas\Components\Utilities\Get $get): ?string {
                                        return match ($get('triage_category')) {
                                            'red' => 'danger',
                                            'yellow' => 'warning',
                                            'green' => 'success',
                                            'black' => 'gray',
                                            default => null,
                                        };
                                    }),

                                ColorPicker::make('triage_color_display')
                                    ->label('Warna Triase')
                                    ->default('#808080')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (\Filament\Schemas\Components\Utilities\Get $get): string {
                                        return match ($get('triage_category')) {
                                            'red' => '#EF4444',
                                            'yellow' => '#EAB308',
                                            'green' => '#22C55E',
                                            'black' => '#1F2937',
                                            default => '#808080',
                                        };
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                // Notes
                Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->placeholder('Masukkan catatan tambahan jika diperlukan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medicalRecord.record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Model $record): string => $record->patient->medical_record_number ?? '-'),

                BadgeColumn::make('assessment_type')
                    ->label('Tipe Asesmen')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'triage' => 'Triase',
                        'awal_perawat' => 'Awal Perawat',
                        'awal_dokter' => 'Awal Dokter',
                        'lanjutan' => 'Lanjutan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'triage' => 'danger',
                        'awal_perawat' => 'info',
                        'awal_dokter' => 'primary',
                        'lanjutan' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('ttv_summary')
                    ->label('Ringkasan TTV')
                    ->getStateUsing(function (Model $record): string {
                        $vitalSigns = $record->vital_signs ?? [];
                        $bp = ($vitalSigns['systolic_bp'] ?? '-') . '/' . ($vitalSigns['diastolic_bp'] ?? '-');
                        $hr = $vitalSigns['heart_rate'] ?? '-';
                        $temp = $vitalSigns['body_temperature'] ?? '-';
                        $spo2 = $vitalSigns['oxygen_saturation'] ?? '-';

                        return "TD: {$bp} | HR: {$hr} | Suhu: {$temp} | SpO2: {$spo2}";
                    })
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('gcs_total')
                    ->label('GCS')
                    ->getStateUsing(function (Model $record): string {
                        $physical = $record->physical_examination ?? [];
                        $eye = $physical['gcs_eye'] ?? 0;
                        $verbal = $physical['gcs_verbal'] ?? 0;
                        $motor = $physical['gcs_motor'] ?? 0;
                        $total = (int) $eye + (int) $verbal + (int) $motor;

                        if ($total === 0) {
                            return '-';
                        }

                        return (string) $total;
                    })
                    ->badge()
                    ->color(function (Model $record): string {
                        $physical = $record->physical_examination ?? [];
                        $total = (int) ($physical['gcs_eye'] ?? 0) + (int) ($physical['gcs_verbal'] ?? 0) + (int) ($physical['gcs_motor'] ?? 0);

                        return match (true) {
                            $total === 0 => 'gray',
                            $total >= 13 => 'success',
                            $total >= 9 => 'warning',
                            default => 'danger',
                        };
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query)
                    ->alignCenter(),

                BadgeColumn::make('triage_category')
                    ->label('Triase')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'red' => 'MERAH',
                        'yellow' => 'KUNING',
                        'green' => 'HIJAU',
                        'black' => 'HITAM',
                        default => '-',
                    })
                    ->color(fn (?string $state): ?string => match ($state) {
                        'red' => 'danger',
                        'yellow' => 'warning',
                        'green' => 'success',
                        'black' => 'gray',
                        default => null,
                    })
                    ->icon(fn (?string $state): ?string => match ($state) {
                        'red' => 'heroicon-m-exclamation-circle',
                        'yellow' => 'heroicon-m-clock',
                        'green' => 'heroicon-m-check-circle',
                        'black' => 'heroicon-m-x-circle',
                        default => null,
                    })
                    ->sortable(),

                TextColumn::make('assessedBy.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('assessment_date')
                    ->label('Tanggal Asesmen')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('assessment_date', 'desc')
            ->filters([
                SelectFilter::make('assessment_type')
                    ->label('Tipe Asesmen')
                    ->options([
                        'triage' => 'Triase',
                        'awal_perawat' => 'Awal Perawat',
                        'awal_dokter' => 'Awal Dokter',
                        'lanjutan' => 'Lanjutan',
                    ])
                    ->native(false),

                SelectFilter::make('triage_category')
                    ->label('Kategori Triase')
                    ->options([
                        'red' => 'Merah (Emergency)',
                        'yellow' => 'Kuning (Urgent)',
                        'green' => 'Hijau (Non-Urgent)',
                        'black' => 'Hitam (Deceased)',
                    ])
                    ->native(false),

                Filter::make('assessment_date')
                    ->label('Tanggal Asesmen')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('assessment_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('assessment_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada asesmen')
            ->emptyStateDescription('Buat asesmen pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers can be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssessments::route('/'),
            'create' => CreateAssessment::route('/create'),
            'view' => ViewAssessment::route('/{record}'),
            'edit' => EditAssessment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['medicalRecord', 'patient', 'assessedBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    // Helper methods

    private static function updateBpStatus(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        // BP status is calculated on display, no need to store
    }

    private static function getBpStatusLabel(int $systolic, int $diastolic): string
    {
        if ($systolic < 120 && $diastolic < 80) {
            return '✓ Normal';
        } elseif ($systolic < 130 && $diastolic < 80) {
            return '↑ Pre-hipertensi';
        } elseif ($systolic < 140 || $diastolic < 90) {
            return '↑ Hipertensi Stage 1';
        } else {
            return '↑↑ Hipertensi Stage 2';
        }
    }

    private static function calculateGcs(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        // GCS total is calculated on display, no need to store
    }

    private static function calculateBmi(\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        // BMI is calculated on display and also stored in the model's accessor
    }
}
