<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SurgeryResource\RelationManagers\SurgeryImplantsRelationManager;
use App\Filament\Resources\SurgeryResource\Pages\ListSurgeries;
use App\Filament\Resources\SurgeryResource\Pages\CreateSurgery;
use App\Filament\Resources\SurgeryResource\Pages\ViewSurgery;
use App\Filament\Resources\SurgeryResource\Pages\EditSurgery;
use App\Filament\Resources\SurgeryResource\Widgets\SurgeryStats;
use App\Filament\Resources\SurgeryResource\Widgets\OperatingRoomSchedule;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\SurgeryResource\Pages;

/**
 * Surgery Resource
 * 
 * Filament resource for managing surgical procedures.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\SurgeryResource\RelationManagers;
use App\Filament\Resources\SurgeryResource\Widgets;
use App\Models\Clinical\Surgery;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\SurgeryService;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SurgeryResource extends Resource
{
    protected static ?string $model = Surgery::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Jadwal & Operasi';

    protected static ?string $modelLabel = 'Operasi';

    protected static ?string $pluralModelLabel = 'Jadwal & Operasi';

    protected static ?int $navigationSort = 80;

    protected static UnitEnum|string|null $navigationGroup = 'Bedah Sentral';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section: Registrasi OK
                Section::make('Registrasi OK')
                    ->icon('heroicon-o-clipboard-document')
                    ->schema([
                        TextInput::make('surgery_number')
                            ->label('Nomor Operasi')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Otomatis terisi')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Select::make('visit_id')
                            ->label('Kunjungan')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->optionsLimit(20)
                            ->getSearchResultsUsing(fn (string $search): array =>
                                Visit::query()
                                    ->with('patient')
                                    ->whereHas('patient', fn ($q) => $q->search($search))
                                    ->orWhere('visit_number', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($visit) => [
                                        $visit->id => "{$visit->visit_number} - {$visit->patient?->name}"
                                    ])
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                Visit::with('patient')->find($value)?->visit_number
                            )
                            ->placeholder('Cari kunjungan pasien')
                            ->prefixIcon('heroicon-m-magnifying-glass')
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $visit = Visit::find($state);
                                    if ($visit) {
                                        $set('patient_id', $visit->patient_id);
                                    }
                                }
                            }),

                        Select::make('patient_id')
                            ->label('Pasien')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->optionsLimit(20)
                            ->getSearchResultsUsing(fn (string $search): array =>
                                Patient::query()
                                    ->search($search)
                                    ->limit(20)
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                Patient::find($value)?->name
                            )
                            ->placeholder('Cari pasien')
                            ->prefixIcon('heroicon-m-user')
                            ->disabled(fn (Get $get) => $get('visit_id')),

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

                                return implode("\n", [
                                    "No. RM: {$patient->medical_record_number}",
                                    "Nama: {$patient->name}",
                                    "Jenis Kelamin: " . ($patient->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                                    "Tanggal Lahir: " . ($patient->birth_date?->format('d M Y') ?? '-'),
                                    "Usia: {$patient->age} tahun",
                                    "Gol. Darah: " . ($patient->blood_type ?? '-'),
                                ]);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section: Jadwal
                Section::make('Jadwal Operasi')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        DatePicker::make('scheduled_date')
                            ->label('Tanggal Operasi')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-m-calendar'),

                        DateTimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-m-clock'),

                        DateTimePicker::make('estimated_end_time')
                            ->label('Estimasi Selesai')
                            ->native(false)
                            ->prefixIcon('heroicon-m-clock')
                            ->after('start_time'),

                        Select::make('operating_room')
                            ->label('Ruang OK')
                            ->required()
                            ->options(Surgery::getOperatingRooms())
                            ->native(false)
                            ->prefixIcon('heroicon-m-building-office'),
                    ])
                    ->columns(2),

                // Section: Tim Operasi
                Section::make('Tim Operasi')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Select::make('surgeon_id')
                            ->label('Operator (Dokter Bedah)')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Employee::where('is_doctor', true)
                                ->pluck('full_name_with_title', 'id'))
                            ->placeholder('Pilih operator')
                            ->prefixIcon('heroicon-m-user-circle'),

                        Select::make('assistant_surgeon_id')
                            ->label('Asisten Operator')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Employee::where('is_doctor', true)
                                ->pluck('full_name_with_title', 'id'))
                            ->placeholder('Pilih asisten')
                            ->prefixIcon('heroicon-m-user-circle'),

                        Select::make('anesthesiologist_id')
                            ->label('Dokter Anestesi')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Employee::where('is_doctor', true)
                                ->pluck('full_name_with_title', 'id'))
                            ->placeholder('Pilih dokter anestesi')
                            ->prefixIcon('heroicon-m-heart'),

                        Select::make('anesthesia_type')
                            ->label('Jenis Anestesi')
                            ->options(Surgery::getAnesthesiaTypes())
                            ->native(false)
                            ->placeholder('Pilih jenis anestesi')
                            ->prefixIcon('heroicon-m-beaker'),

                        Select::make('nurse_id')
                            ->label('Perawat Instrument')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Employee::where('is_nurse', true)
                                ->pluck('name', 'id'))
                            ->placeholder('Pilih perawat')
                            ->prefixIcon('heroicon-m-user'),

                        Select::make('circulating_nurse_id')
                            ->label('Perawat Sirkuler')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn () => Employee::where('is_nurse', true)
                                ->pluck('name', 'id'))
                            ->placeholder('Pilih perawat sirkuler')
                            ->prefixIcon('heroicon-m-user'),
                    ])
                    ->columns(2),

                // Section: Tindakan
                Section::make('Tindakan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Select::make('surgery_type')
                            ->label('Jenis Operasi')
                            ->required()
                            ->options([
                                'elektif' => 'Elektif',
                                'urgent' => 'Urgent',
                                'cito' => 'CITO',
                                'emergency' => 'Emergency',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-m-flag')
                            ->default('elektif'),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'scheduled' => 'Terjadwal',
                                'preparation' => 'Persiapan',
                                'in_progress' => 'Sedang Berlangsung',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-m-signal')
                            ->default('scheduled'),

                        TextInput::make('procedure_name')
                            ->label('Nama Tindakan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Appendektomi, Hernioreparasi')
                            ->prefixIcon('heroicon-m-document-text'),

                        TextInput::make('procedure_code')
                            ->label('Kode Tindakan (ICD-9)')
                            ->maxLength(20)
                            ->placeholder('Contoh: 47.0')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Textarea::make('pre_diagnosis')
                            ->label('Diagnosa Pre Operasi')
                            ->rows(3)
                            ->placeholder('Diagnosa sebelum operasi')
                            ->columnSpanFull(),

                        Textarea::make('post_diagnosis')
                            ->label('Diagnosa Post Operasi')
                            ->rows(3)
                            ->placeholder('Diagnosa setelah operasi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section: Safety Checklist (WHO)
                Section::make('Safety Checklist (WHO)')
                    ->icon('heroicon-o-shield-check')
                    ->description('Checklist keselamatan bedah sesuai standar WHO')
                    ->collapsible()
                    ->schema([
                        Toggle::make('safety_checklist_sign_in')
                            ->label('Sign In (Sebelum Induksi Anestesi)')
                            ->helperText('Konfirmasi identitas pasien, prosedur, lokasi operasi, dan persetujuan')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('safety_checklist_sign_in_at', now());
                                }
                            }),

                        DateTimePicker::make('safety_checklist_sign_in_at')
                            ->label('Waktu Sign In')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (Get $get) => !$get('safety_checklist_sign_in')),

                        Toggle::make('safety_checklist_time_out')
                            ->label('Time Out (Sebelum Insisi)')
                            ->helperText('Konfirmasi tim operasi, identifikasi pasien dan prosedur, antisipasi kritis')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('safety_checklist_time_out_at', now());
                                }
                            }),

                        DateTimePicker::make('safety_checklist_time_out_at')
                            ->label('Waktu Time Out')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (Get $get) => !$get('safety_checklist_time_out')),

                        Toggle::make('safety_checklist_sign_out')
                            ->label('Sign Out (Sebelum Pasien Keluar OK)')
                            ->helperText('Konfirmasi penghitungan instrumen, spesimen, dan perhatian pasca operasi')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('safety_checklist_sign_out_at', now());
                                }
                            }),

                        DateTimePicker::make('safety_checklist_sign_out_at')
                            ->label('Waktu Sign Out')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (Get $get) => !$get('safety_checklist_sign_out')),
                    ])
                    ->columns(2),

                // Section: Laporan Operasi
                Section::make('Laporan Operasi')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'create')
                    ->schema([
                        DateTimePicker::make('actual_start')
                            ->label('Waktu Mulai Aktual')
                            ->native(false)
                            ->prefixIcon('heroicon-m-clock'),

                        DateTimePicker::make('actual_end')
                            ->label('Waktu Selesai Aktual')
                            ->native(false)
                            ->prefixIcon('heroicon-m-clock')
                            ->after('actual_start'),

                        Textarea::make('procedure_notes')
                            ->label('Prosedur Operasi')
                            ->rows(4)
                            ->placeholder('Deskripsi detail prosedur operasi...')
                            ->columnSpanFull(),

                        Textarea::make('findings')
                            ->label('Temuan')
                            ->rows(3)
                            ->placeholder('Temuan selama operasi...')
                            ->columnSpanFull(),

                        Textarea::make('complications')
                            ->label('Komplikasi')
                            ->rows(3)
                            ->placeholder('Komplikasi yang terjadi (jika ada)...')
                            ->columnSpanFull(),

                        Textarea::make('specimens')
                            ->label('Spesimen')
                            ->rows(2)
                            ->placeholder('Spesimen yang diambil...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section: Pembatalan/Penundaan
                Section::make('Pembatalan/Penundaan')
                    ->icon('heroicon-o-x-circle')
                    ->collapsible()
                    ->collapsed(fn (string $operation) => $operation === 'create')
                    ->visible(fn (Get $get) => $get('is_postponed') || $get('status') === 'cancelled')
                    ->schema([
                        Toggle::make('is_postponed')
                            ->label('Ditunda')
                            ->disabled(),

                        Textarea::make('postponed_reason')
                            ->label('Alasan Penundaan')
                            ->rows(2)
                            ->disabled(),

                        DateTimePicker::make('cancelled_at')
                            ->label('Waktu Pembatalan')
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->rows(2),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->placeholder('Catatan tambahan...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('surgery_number')
                    ->label('No. Operasi')
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

                TextColumn::make('procedure_name')
                    ->label('Tindakan')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Model $record): string => $record->procedure_name),

                TextColumn::make('surgeon.full_name_with_title')
                    ->label('Operator')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('scheduled_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Jam')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('operating_room')
                    ->label('Ruang')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                BadgeColumn::make('surgery_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'elektif' => 'Elektif',
                        'urgent' => 'Urgent',
                        'cito' => 'CITO',
                        'emergency' => 'Emergency',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'elektif' => 'info',
                        'urgent' => 'warning',
                        'cito' => 'danger',
                        'emergency' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'elektif' => 'heroicon-m-calendar',
                        'urgent' => 'heroicon-m-exclamation-triangle',
                        'cito' => 'heroicon-m-bolt',
                        'emergency' => 'heroicon-m-fire',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'scheduled' => 'Terjadwal',
                        'preparation' => 'Persiapan',
                        'in_progress' => 'Sedang Berlangsung',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'preparation' => 'warning',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'scheduled' => 'heroicon-m-calendar',
                        'preparation' => 'heroicon-m-clock',
                        'in_progress' => 'heroicon-m-play',
                        'completed' => 'heroicon-m-check-circle',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                IconColumn::make('is_postponed')
                    ->label('Ditunda')
                    ->alignCenter()
                    ->boolean()
                    ->visible(fn () => true),

                TextColumn::make('duration')
                    ->label('Durasi')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} menit" : '-')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Terjadwal',
                        'preparation' => 'Persiapan',
                        'in_progress' => 'Sedang Berlangsung',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('surgery_type')
                    ->label('Jenis Operasi')
                    ->options([
                        'elektif' => 'Elektif',
                        'urgent' => 'Urgent',
                        'cito' => 'CITO',
                        'emergency' => 'Emergency',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('operating_room')
                    ->label('Ruang OK')
                    ->options(Surgery::getOperatingRooms())
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('surgeon_id')
                    ->label('Operator')
                    ->relationship('surgeon', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('scheduled_date')
                    ->label('Tanggal Operasi')
                    ->schema([
                        DatePicker::make('scheduled_date')
                            ->label('Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['scheduled_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', $date),
                            );
                    }),
            ])
            ->recordActions([
                // Jadwalkan
                Action::make('schedule')
                    ->label('Jadwalkan')
                    ->icon('heroicon-m-calendar')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (?Model $record): bool =>
                        in_array($record?->status, ['cancelled'])
                    )
                    ->action(function (Model $record, SurgeryService $service): void {
                        $record->update([
                            'status' => 'scheduled',
                            'cancelled_at' => null,
                            'cancellation_reason' => null,
                        ]);
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                // Mulai Operasi
                Action::make('start')
                    ->label('Mulai')
                    ->icon('heroicon-m-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin memulai operasi ini?')
                    ->visible(fn (?Model $record): bool =>
                        in_array($record?->status, ['scheduled', 'preparation'])
                    )
                    ->action(function (Model $record, SurgeryService $service): void {
                        $service->startSurgery($record->id);
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                // Selesai
                Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Tandai operasi sebagai selesai?')
                    ->visible(fn (?Model $record): bool => $record?->status === 'in_progress')
                    ->action(function (Model $record, SurgeryService $service): void {
                        $service->completeSurgery($record->id, [
                            'safety_checklist_sign_out' => true,
                        ]);
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                // Batal
                Action::make('cancel')
                    ->label('Batal')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin membatalkan operasi ini?')
                    ->visible(fn (?Model $record): bool =>
                        !in_array($record?->status, ['completed', 'cancelled'])
                    )
                    ->schema([
                        Textarea::make('cancellation_reason')
                            ->label('Alasan Pembatalan')
                            ->required(),
                    ])
                    ->action(function (Model $record, array $data, SurgeryService $service): void {
                        $service->cancelSurgery(
                            $record->id,
                            $data['cancellation_reason'] ?? null,
                            Auth::id()
                        );
                    })
                    ->after(fn () => redirect(request()->header('Referer'))),

                // Persiapan
                Action::make('prepare')
                    ->label('Persiapan')
                    ->icon('heroicon-m-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (?Model $record): bool => $record?->status === 'scheduled')
                    ->action(function (Model $record): void {
                        $record->update(['status' => 'preparation']);
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
            ->emptyStateHeading('Belum ada operasi')
            ->emptyStateDescription('Buat jadwal operasi pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getRelations(): array
    {
        return [
            SurgeryImplantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurgeries::route('/'),
            'create' => CreateSurgery::route('/create'),
            'view' => ViewSurgery::route('/{record}'),
            'edit' => EditSurgery::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'surgeon', 'visit']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::today()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getWidgets(): array
    {
        return [
            SurgeryStats::class,
            OperatingRoomSchedule::class,
        ];
    }
}


