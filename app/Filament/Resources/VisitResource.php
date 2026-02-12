<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\VisitResource\Pages\ListVisits;
use App\Filament\Resources\VisitResource\Pages\CreateVisit;
use App\Filament\Resources\VisitResource\Pages\ViewVisit;
use App\Filament\Resources\VisitResource\Pages\EditVisit;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\VisitResource\Pages;

/**
 * Visit Resource
 * 
 * Filament resource for managing patient visits/registration.
 * Handles visit workflow from registration to completion.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\VisitResource\Widgets;
use App\Models\Patient\Visit;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Pendaftaran Kunjungan';

    protected static ?string $modelLabel = 'Kunjungan';

    protected static ?string $pluralModelLabel = 'Kunjungan';

    protected static ?int $navigationSort = 11;

    protected static UnitEnum|string|null $navigationGroup = 'Pendaftaran';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Cari/Input Pasien')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Section::make('Data Pasien')
                                ->schema([
                                    Select::make('patient_id')
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

                                    Placeholder::make('patient_info')
                                        ->label('Informasi Pasien')
                                        ->content(function (Get $get): string {
                                            $patientId = $get('patient_id');
                                            if (!$patientId) {
                                                return 'Pilih pasien untuk melihat informasi';
                                            }

                                            $patient = Patient::find($patientId);
                                            if (!$patient) {
                                                return 'Pasien tidak ditemukan';
                                            }

                                            return implode('\n', [
                                                "No. RM: {$patient->medical_record_number}",
                                                "Nama: {$patient->name}",
                                                "Jenis Kelamin: " . ($patient->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                                                "Tanggal Lahir: " . $patient->birth_date?->format('d M Y'),
                                                "Usia: {$patient->age} tahun",
                                                "No. HP: " . ($patient->phone ?? '-'),
                                                "Alamat: " . ($patient->address ?? '-'),
                                            ]);
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Data Kunjungan')
                        ->icon('heroicon-o-clipboard-document')
                        ->schema([
                            Section::make('Informasi Kunjungan')
                                ->schema([
                                    TextInput::make('visit_number')
                                        ->label('Nomor Kunjungan')
                                        ->disabled()
                                        ->dehydrated()
                                        ->placeholder('Otomatis terisi')
                                        ->prefixIcon('heroicon-m-hashtag'),

                                    DatePicker::make('visit_date')
                                        ->label('Tanggal Kunjungan')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-calendar'),

                                    Select::make('visit_type')
                                        ->label('Jenis Kunjungan')
                                        ->required()
                                        ->options([
                                            'rawat_jalan' => 'Rawat Jalan',
                                            'rawat_inap' => 'Rawat Inap',
                                            'igd' => 'IGD',
                                        ])
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-building-office'),

                                    Select::make('registration_type')
                                        ->label('Jenis Pendaftaran')
                                        ->required()
                                        ->options([
                                            'baru' => 'Baru',
                                            'lama' => 'Lama',
                                            'rujukan' => 'Rujukan',
                                            'kontrol' => 'Kontrol',
                                        ])
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-document-text'),
                                ])
                                ->columns(2),

                            Section::make('Lokasi dan Dokter')
                                ->schema([
                                    Select::make('polyclinic_id')
                                        ->label('Poliklinik')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(fn () => Polyclinic::where('is_active', true)->pluck('name', 'id'))
                                        ->placeholder('Pilih poliklinik')
                                        ->prefixIcon('heroicon-m-building-office-2'),

                                    Select::make('doctor_id')
                                        ->label('Dokter')
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(fn () => Employee::whereHas('employmentDetail', function ($query) {
                                            $query->where('job_title', 'like', '%dokter%')
                                                ->orWhere('job_title', 'like', '%dr.%');
                                        })->pluck('name', 'id'))
                                        ->placeholder('Pilih dokter (opsional)')
                                        ->prefixIcon('heroicon-m-user-circle'),
                                ])
                                ->columns(2),

                            Section::make('Keluhan dan Prioritas')
                                ->schema([
                                    Select::make('priority')
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

                                    Textarea::make('complaint')
                                        ->label('Keluhan')
                                        ->required()
                                        ->placeholder('Masukkan keluhan pasien')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(1),
                        ]),

                    Step::make('Info Tambahan')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Informasi Rujukan')
                                ->schema([
                                    TextInput::make('referral_from')
                                        ->label('Rujukan Dari')
                                        ->maxLength(100)
                                        ->placeholder('Nama faskes perujuk')
                                        ->prefixIcon('heroicon-m-building-library'),

                                    TextInput::make('referral_number')
                                        ->label('Nomor Rujukan')
                                        ->maxLength(50)
                                        ->placeholder('Nomor rujukan')
                                        ->prefixIcon('heroicon-m-document'),
                                ])
                                ->columns(2)
                                ->collapsible(),

                            Section::make('Informasi BPJS')
                                ->schema([
                                    TextInput::make('bpjs_sep_number')
                                        ->label('Nomor SEP BPJS')
                                        ->maxLength(50)
                                        ->placeholder('Nomor Surat Eligibilitas Peserta')
                                        ->prefixIcon('heroicon-m-identification'),
                                ])
                                ->collapsible(),

                            Section::make('Status dan Catatan')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status')
                                        ->required()
                                        ->options([
                                            'registered' => 'Terdaftar',
                                            'waiting' => 'Menunggu',
                                            'in_progress' => 'Sedang Dilayani',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan',
                                        ])
                                        ->default('registered')
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-signal'),

                                    Toggle::make('is_completed')
                                        ->label('Kunjungan Selesai')
                                        ->default(false),

                                    Textarea::make('notes')
                                        ->label('Catatan')
                                        ->placeholder('Catatan tambahan')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
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
                TextColumn::make('visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->copyable(),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->description(fn (Model $record): string => $record->patient?->medical_record_number ?? '-'),

                TextColumn::make('polyclinic.name')
                    ->label('Poli')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                BadgeColumn::make('status')
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

                BadgeColumn::make('priority')
                    ->label('Prioritas')
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
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'normal' => 'heroicon-m-minus',
                        'urgent' => 'heroicon-m-exclamation-triangle',
                        'emergency' => 'heroicon-m-bolt',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('visit_type')
                    ->label('Jenis')
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
                    })
                    ->sortable(),

                TextColumn::make('check_in_at')
                    ->label('Check-in')
                    ->dateTime('H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('check_out_at')
                    ->label('Check-out')
                    ->dateTime('H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('visit_date', 'desc')
            ->filters([
                SelectFilter::make('status')
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

                SelectFilter::make('visit_type')
                    ->label('Jenis Kunjungan')
                    ->options([
                        'rawat_jalan' => 'Rawat Jalan',
                        'rawat_inap' => 'Rawat Inap',
                        'igd' => 'IGD',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('polyclinic_id')
                    ->label('Poliklinik')
                    ->relationship('polyclinic', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options([
                        'normal' => 'Normal',
                        'urgent' => 'Urgent',
                        'emergency' => 'Emergency',
                    ])
                    ->native(false)
                    ->multiple(),

                Filter::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->schema([
                        DatePicker::make('visit_date')
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
                Action::make('check_in')
                    ->label('Check-in')
                    ->icon('heroicon-m-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => 
                        in_array($record->status, ['registered', 'waiting']) && 
                        is_null($record->check_in_at)
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'check_in_at' => now(),
                            'status' => 'in_progress',
                        ]);
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => 
                        in_array($record->status, ['registered', 'waiting', 'in_progress']) && 
                        !$record->is_completed
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'check_out_at' => now(),
                            'status' => 'completed',
                            'is_completed' => true,
                        ]);
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                Action::make('cancel')
                    ->label('Batal')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin membatalkan kunjungan ini?')
                    ->visible(fn (Model $record): bool => 
                        !in_array($record->status, ['completed', 'cancelled'])
                    )
                    ->action(function (Model $record): void {
                        $record->update([
                            'status' => 'cancelled',
                        ]);
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
            ->emptyStateHeading('Belum ada kunjungan')
            ->emptyStateDescription('Daftarkan kunjungan pasien pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
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
            'index' => ListVisits::route('/'),
            'create' => CreateVisit::route('/create'),
            'view' => ViewVisit::route('/{record}'),
            'edit' => EditVisit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'polyclinic', 'doctor', 'medicalRecord', 'invoice']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereDate('visit_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * Generate a unique visit number.
     * Format: V-YYYYMMDD-XXXX
     */
    public static function generateVisitNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "V-{$date}-";
        
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
}


