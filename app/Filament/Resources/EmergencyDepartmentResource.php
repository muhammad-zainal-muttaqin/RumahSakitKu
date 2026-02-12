<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use BackedEnum;

use App\Filament\Resources\EmergencyDepartmentResource\Pages;
use App\Filament\Resources\EmergencyDepartmentResource\Widgets;
use App\Models\Clinical\Assessment;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\TriageService;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class EmergencyDepartmentResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'IGD / Emergency';

    protected static ?string $modelLabel = 'Pasien IGD';

    protected static ?string $pluralModelLabel = 'Pasien IGD';

    protected static ?int $navigationSort = 50;

    protected static UnitEnum|string|null $navigationGroup = 'Gawat Darurat';

    /**
     * Get the eloquent query with necessary relationships.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('visit_type', 'igd')
            ->with([
                'patient',
                'doctor',
                'medicalRecord',
                'medicalRecord.assessments' => function ($query) {
                    $query->where('assessment_type', 'triage');
                },
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Wizard::make([
                    // Step 1: Patient Selection
                    Forms\Components\Wizard\Step::make('Pasien')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make('Data Pasien')
                                ->schema([
                                    Forms\Components\Select::make('patient_id')
                                        ->label('Pasien')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->optionsLimit(20)
                                        ->getSearchResultsUsing(fn (string $search): array =>
                                            Patient::query()
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere('medical_record_number', 'like', "%{$search}%")
                                                ->orWhere('nik', 'like', "%{$search}%")
                                                ->limit(20)
                                                ->pluck('name', 'id')
                                                ->toArray()
                                        )
                                        ->getOptionLabelUsing(fn ($value): ?string =>
                                            Patient::find($value)?->name
                                        )
                                        ->placeholder('Cari berdasarkan nama, nomor RM, atau NIK')
                                        ->prefixIcon('heroicon-m-magnifying-glass'),

                                    Forms\Components\Placeholder::make('patient_info')
                                        ->label('Informasi Pasien')
                                        ->content(function (Forms\Get $get): string {
                                            $patientId = $get('patient_id');
                                            if (!$patientId) {
                                                return 'Pilih pasien untuk melihat informasi';
                                            }

                                            $patient = Patient::find($patientId);
                                            if (!$patient) {
                                                return 'Pasien tidak ditemukan';
                                            }

                                            return implode("\n", [
                                                "No. RM: {$patient->medical_record_number}",
                                                "Nama: {$patient->name}",
                                                "Jenis Kelamin: " . ($patient->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                                                "Tanggal Lahir: " . $patient->birth_date?->format('d M Y'),
                                                "Usia: {$patient->age} tahun",
                                                "Gol. Darah: " . ($patient->blood_type ?? '-'),
                                                "No. HP: " . ($patient->phone ?? '-'),
                                            ]);
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // Step 2: Triage Assessment
                    Forms\Components\Wizard\Step::make('Triase')
                        ->icon('heroicon-o-heart')
                        ->schema([
                            Forms\Components\Section::make('Keluhan Utama')
                                ->schema([
                                    Forms\Components\Textarea::make('complaint')
                                        ->label('Keluhan')
                                        ->required()
                                        ->placeholder('Masukkan keluhan utama pasien')
                                        ->rows(3)
                                        ->columnSpanFull()
                                        ->live(onBlur: true),
                                ]),

                            Forms\Components\Section::make('Tanda-Tanda Vital (TTV)')
                                ->icon('heroicon-o-heart')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            // Blood Pressure
                                            Forms\Components\Fieldset::make('Tekanan Darah')
                                                ->schema([
                                                    Forms\Components\Grid::make(2)
                                                        ->schema([
                                                            Forms\Components\TextInput::make('vital_signs.systolic_bp')
                                                                ->label('Sistolik (mmHg)')
                                                                ->numeric()
                                                                ->minValue(0)
                                                                ->maxValue(300)
                                                                ->placeholder('120')
                                                                ->suffixIcon('heroicon-m-heart')
                                                                ->live()
                                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                                            Forms\Components\TextInput::make('vital_signs.diastolic_bp')
                                                                ->label('Diastolik (mmHg)')
                                                                ->numeric()
                                                                ->minValue(0)
                                                                ->maxValue(200)
                                                                ->placeholder('80')
                                                                ->suffixIcon('heroicon-m-heart')
                                                                ->live()
                                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),
                                                        ]),
                                                ]),

                                            // Heart Rate
                                            Forms\Components\TextInput::make('vital_signs.heart_rate')
                                                ->label('Denyut Jantung (bpm)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(300)
                                                ->placeholder('72')
                                                ->suffixIcon('heroicon-m-heart')
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                            // Respiratory Rate
                                            Forms\Components\TextInput::make('vital_signs.respiratory_rate')
                                                ->label('Pernapasan (x/menit)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->placeholder('16')
                                                ->suffixIcon('heroicon-m-cloud')
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                            // Body Temperature
                                            Forms\Components\TextInput::make('vital_signs.body_temperature')
                                                ->label('Suhu Tubuh (°C)')
                                                ->numeric()
                                                ->minValue(30)
                                                ->maxValue(45)
                                                ->step(0.1)
                                                ->placeholder('36.5')
                                                ->suffixIcon('heroicon-m-fire')
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                            // Oxygen Saturation
                                            Forms\Components\TextInput::make('vital_signs.oxygen_saturation')
                                                ->label('SpO2 (%)')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->step(0.1)
                                                ->placeholder('98')
                                                ->suffixIcon('heroicon-m-bolt')
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                            // GCS
                                            Forms\Components\Fieldset::make('GCS')
                                                ->schema([
                                                    Forms\Components\Grid::make(3)
                                                        ->schema([
                                                            Forms\Components\Select::make('vital_signs.gcs_eye')
                                                                ->label('E')
                                                                ->options([4 => '4', 3 => '3', 2 => '2', 1 => '1'])
                                                                ->native(false)
                                                                ->live()
                                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                                            Forms\Components\Select::make('vital_signs.gcs_verbal')
                                                                ->label('V')
                                                                ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1'])
                                                                ->native(false)
                                                                ->live()
                                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),

                                                            Forms\Components\Select::make('vital_signs.gcs_motor')
                                                                ->label('M')
                                                                ->options([6 => '6', 5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1'])
                                                                ->native(false)
                                                                ->live()
                                                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateTriage($get, $set)),
                                                        ]),
                                                ]),
                                        ]),
                                ]),

                            Forms\Components\Section::make('Hasil Triase')
                                ->icon('heroicon-o-exclamation-triangle')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('triage_category')
                                                ->label('Kategori Triase')
                                                ->options(TriageService::getCategoryOptions())
                                                ->native(false)
                                                ->live()
                                                ->required()
                                                ->afterStateHydrated(function (Set $set, ?string $state) {
                                                    if (!$state) {
                                                        $set('triage_category', 'green');
                                                    }
                                                })
                                                ->suffixIcon('heroicon-m-flag'),

                                            Forms\Components\ColorPicker::make('triage_color_display')
                                                ->label('Warna Triase')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->formatStateUsing(fn (Get $get): string =>
                                                    TriageService::getCategoryHexColor($get('triage_category') ?? 'green')
                                                ),
                                        ]),

                                    Forms\Components\Placeholder::make('triage_info')
                                        ->label('Informasi Triase')
                                        ->content(function (Get $get): string {
                                            $category = $get('triage_category') ?? 'green';
                                            $label = TriageService::getCategoryLabel($category);
                                            $description = TriageService::getCategoryDescription($category);
                                            $waitTime = TriageService::getCategoryWaitTime($category);

                                            return "{$label}\n{$description}\nWaktu tunggu: {$waitTime}";
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->afterStateHydrated(function (array $state, Set $set) {
                            // Set default triage category if not set
                            if (empty($state['triage_category'])) {
                                $set('triage_category', 'green');
                            }
                        }),

                    // Step 3: Treatment
                    Forms\Components\Wizard\Step::make('Penanganan')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Forms\Components\Section::make('Penugasan Dokter')
                                ->schema([
                                    Forms\Components\Select::make('doctor_id')
                                        ->label('Dokter Penanggung Jawab')
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(fn () => Employee::doctors()->pluck('name', 'id'))
                                        ->placeholder('Pilih dokter (opsional)')
                                        ->prefixIcon('heroicon-m-user-circle'),
                                ]),

                            Forms\Components\Section::make('Status dan Prioritas')
                                ->schema([
                                    Forms\Components\Select::make('priority')
                                        ->label('Prioritas')
                                        ->required()
                                        ->options([
                                            'normal' => 'Normal',
                                            'urgent' => 'Urgent',
                                            'emergency' => 'Emergency',
                                        ])
                                        ->default('normal')
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-flag'),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->required()
                                        ->options([
                                            'registered' => 'Terdaftar',
                                            'waiting' => 'Menunggu',
                                            'in_progress' => 'Sedang Dilayani',
                                        ])
                                        ->default('registered')
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-signal'),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Informasi Kunjungan')
                                ->schema([
                                    Forms\Components\TextInput::make('visit_number')
                                        ->label('Nomor Kunjungan')
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(fn () => self::generateVisitNumber())
                                        ->prefixIcon('heroicon-m-hashtag'),

                                    Forms\Components\DatePicker::make('visit_date')
                                        ->label('Tanggal Kunjungan')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-calendar'),

                                    Forms\Components\Hidden::make('visit_type')
                                        ->default('igd'),

                                    Forms\Components\Hidden::make('registration_type')
                                        ->default('baru'),
                                ])
                                ->columns(2)
                                ->collapsed(),
                        ]),
                ])
                ->skippable(false)
                ->persistStepInQueryString()
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->copyable(),

                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->description(fn (Model $record): string => $record->patient?->medical_record_number ?? '-'),

                Tables\Columns\BadgeColumn::make('triage_category_display')
                    ->label('Triase')
                    ->getStateUsing(function (Model $record): string {
                        $assessment = $record->medicalRecord?->assessments->first();
                        return $assessment?->triage_category ?? 'unknown';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'red' => 'MERAH',
                        'yellow' => 'KUNING',
                        'green' => 'HIJAU',
                        'black' => 'HITAM',
                        default => '-',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'red' => 'danger',
                        'yellow' => 'warning',
                        'green' => 'success',
                        'black' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'red' => 'heroicon-m-exclamation-circle',
                        'yellow' => 'heroicon-m-clock',
                        'green' => 'heroicon-m-check-circle',
                        'black' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('complaint')
                    ->label('Keluhan')
                    ->limit(30)
                    ->tooltip(fn (Model $record): string => $record->complaint ?? '-')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
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
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'registered' => 'heroicon-m-clipboard-document',
                        'waiting' => 'heroicon-m-clock',
                        'in_progress' => 'heroicon-m-play',
                        'completed' => 'heroicon-m-check-circle',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('wait_time')
                    ->label('Waktu Tunggu')
                    ->getStateUsing(function (Model $record): string {
                        $startTime = $record->check_in_at ?? $record->created_at;
                        $diff = $startTime?->diffInMinutes(now()) ?? 0;

                        if ($diff < 60) {
                            return $diff . ' menit';
                        }

                        $hours = floor($diff / 60);
                        $minutes = $diff % 60;

                        return $hours . 'j ' . $minutes . 'm';
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query)
                    ->badge()
                    ->color(function (Model $record) {
                        $startTime = $record->check_in_at ?? $record->created_at;
                        $diff = $startTime?->diffInMinutes(now()) ?? 0;

                        if ($diff > 120) {
                            return 'danger';
                        } elseif ($diff > 60) {
                            return 'warning';
                        }
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('check_in_at')
                    ->label('Waktu Masuk')
                    ->dateTime('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('check_in_at', 'asc')
            ->groups([
                Tables\Grouping\Group::make('triage_category_display')
                    ->label('Kategori Triase')
                    ->getTitleFromRecordUsing(function (Model $record): string {
                        $assessment = $record->medicalRecord?->assessments->first();
                        $category = $assessment?->triage_category ?? 'unknown';
                        return TriageService::getCategoryLabel($category);
                    })
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('triage_category')
                    ->label('Kategori Triase')
                    ->options(TriageService::getCategoryOptions())
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $category): Builder =>
                                $query->whereHas('medicalRecord.assessments', fn (Builder $q) =>
                                    $q->where('assessment_type', 'triage')
                                        ->where('triage_category', $category)
                                )
                        );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Terdaftar',
                        'waiting' => 'Menunggu',
                        'in_progress' => 'Sedang Dilayani',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false)
                    ->multiple(),

                Tables\Filters\Filter::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->form([
                        Forms\Components\DatePicker::make('visit_date')
                            ->label('Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['visit_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', $date),
                            );
                    }),
            ])
            ->recordActions([
                // Check-in Action
                Action::make('check_in')
                    ->label('Check-in')
                    ->icon('heroicon-m-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (?Model $record): bool =>
                        in_array($record?->status, ['registered', 'waiting']) &&
                        is_null($record?->check_in_at)
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'check_in_at' => now(),
                            'status' => 'in_progress',
                        ]);

                        Notification::make()
                            ->title('Check-in Berhasil')
                            ->body("Pasien {$record->patient?->name} sekarang sedang dilayani")
                            ->success()
                            ->send();
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                // Admission Action
                Action::make('admission')
                    ->label('Admisi')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin mengadmisi pasien ini?')
                    ->visible(fn (?Model $record): bool =>
                        !in_array($record?->status, ['completed', 'cancelled'])
                    )
                    ->action(function (Model $record): void {
                        Notification::make()
                            ->title('Admisi Berhasil')
                            ->body("Pasien {$record->patient?->name} telah diadmisi")
                            ->success()
                            ->send();
                    }),

                // Transfer to Inpatient (Ranap) Action
                Action::make('transfer_inpatient')
                    ->label('Transfer ke Ranap')
                    ->icon('heroicon-m-home-modern')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin mentransfer pasien ini ke Rawat Inap?')
                    ->visible(fn (?Model $record): bool =>
                        !in_array($record?->status, ['completed', 'cancelled'])
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'status' => 'completed',
                            'is_completed' => true,
                            'check_out_at' => now(),
                        ]);

                        // Here you would typically create an inpatient admission record
                        Notification::make()
                            ->title('Transfer Berhasil')
                            ->body("Pasien {$record->patient?->name} telah ditransfer ke Rawat Inap")
                            ->success()
                            ->send();
                    }),

                // Discharge Action
                Action::make('discharge')
                    ->label('Discharge')
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin discharge pasien ini?')
                    ->visible(fn (?Model $record): bool =>
                        !in_array($record?->status, ['completed', 'cancelled'])
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'status' => 'completed',
                            'is_completed' => true,
                            'check_out_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Discharge Berhasil')
                            ->body("Pasien {$record->patient?->name} telah discharge dari IGD")
                            ->success()
                            ->send();
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pasien IGD')
            ->emptyStateDescription('Daftarkan pasien IGD pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-truck');
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
            'index' => Pages\ListEmergencyDepartments::route('/'),
            'create' => Pages\CreateEmergencyDepartment::route('/create'),
            'view' => Pages\ViewEmergencyDepartment::route('/{record}'),
            'edit' => Pages\EditEmergencyDepartment::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\TriageStats::class,
            Widgets\LiveTriageBoard::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('visit_type', 'igd')
            ->whereDate('visit_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $redCount = static::getModel()::where('visit_type', 'igd')
            ->whereDate('visit_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereHas('medicalRecord.assessments', function (Builder $query) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', TriageService::CATEGORY_RED);
            })
            ->count();

        return $redCount > 0 ? 'danger' : 'warning';
    }

    /**
     * Generate a unique visit number.
     * Format: IGD-YYYYMMDD-XXXX
     */
    public static function generateVisitNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "IGD-{$date}-";

        $lastVisit = Visit::where('visit_number', 'like', "{$prefix}%")
            ->orderBy('visit_number', 'desc')
            ->first();

        if ($lastVisit) {
            $lastNumber = (int) substr($lastVisit->visit_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate triage category based on vital signs.
     */
    private static function calculateTriage(Get $get, Set $set): void
    {
        $vitalSigns = [
            'systolic_bp' => $get('vital_signs.systolic_bp'),
            'diastolic_bp' => $get('vital_signs.diastolic_bp'),
            'heart_rate' => $get('vital_signs.heart_rate'),
            'respiratory_rate' => $get('vital_signs.respiratory_rate'),
            'body_temperature' => $get('vital_signs.body_temperature'),
            'oxygen_saturation' => $get('vital_signs.oxygen_saturation'),
            'gcs_eye' => $get('vital_signs.gcs_eye'),
            'gcs_verbal' => $get('vital_signs.gcs_verbal'),
            'gcs_motor' => $get('vital_signs.gcs_motor'),
        ];

        $chiefComplaint = $get('complaint');

        $category = TriageService::calculateTriageCategory($vitalSigns, $chiefComplaint);
        $set('triage_category', $category);
    }

    /**
     * Create assessment record after visit is created.
     *
     * @param Model $record
     * @param array<string, mixed> $data
     */
    public static function createAssessment(Model $record, array $data): void
    {
        // Create or update medical record
        $medicalRecord = $record->medicalRecord;

        if (!$medicalRecord) {
            $medicalRecord = $record->medicalRecord()->create([
                'patient_id' => $record->patient_id,
                'record_number' => MedicalRecord::generateRecordNumber(),
                'visit_date' => $record->visit_date,
            ]);
        }

        // Create triage assessment
        $medicalRecord->assessments()->create([
            'patient_id' => $record->patient_id,
            'visit_id' => $record->id,
            'assessment_type' => 'triage',
            'assessment_date' => now(),
            'chief_complaint' => $data['complaint'] ?? null,
            'vital_signs' => $data['vital_signs'] ?? [],
            'triage_category' => $data['triage_category'] ?? 'green',
            'assessed_by' => auth()->id(),
        ]);
    }
}




