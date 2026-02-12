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
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\InpatientResource\Pages\ListInpatients;
use App\Filament\Resources\InpatientResource\Pages\CreateInpatient;
use App\Filament\Resources\InpatientResource\Pages\ViewInpatient;
use App\Filament\Resources\InpatientResource\Pages\EditInpatient;
use App\Filament\Resources\InpatientResource\Widgets\InpatientStats;
use App\Filament\Resources\InpatientResource\Widgets\RoomOccupancy;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\InpatientResource\Pages;
use App\Filament\Resources\InpatientResource\Widgets;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\InpatientService;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InpatientResource extends Resource
{
    protected static ?string $model = Visit::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Pasien Rawat Inap';

    protected static ?string $modelLabel = 'Pasien Rawat Inap';

    protected static ?string $pluralModelLabel = 'Pasien Rawat Inap';

    protected static ?int $navigationSort = 60;

    protected static UnitEnum|string|null $navigationGroup = 'Rawat Inap';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Registrasi')
                        ->icon('heroicon-o-clipboard-document')
                        ->schema([
                            Section::make('Informasi Pasien')
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

                                            return implode("\n", [
                                                "No. RM: {$patient->medical_record_number}",
                                                "Nama: {$patient->name}",
                                                "Jenis Kelamin: " . ($patient->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                                                "Tanggal Lahir: " . $patient->birth_date?->format('d M Y'),
                                                "Usia: {$patient->age} tahun",
                                                "No. HP: " . ($patient->phone ?? '-'),
                                            ]);
                                        })
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Dokter Penanggung Jawab')
                                ->schema([
                                    Select::make('doctor_id')
                                        ->label('Dokter PJ')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(fn () => Employee::where('is_doctor', true)
                                            ->where('status', 'aktif')
                                            ->pluck('name', 'id'))
                                        ->placeholder('Pilih dokter penanggung jawab')
                                        ->prefixIcon('heroicon-m-user-circle'),

                                    Textarea::make('admission_diagnosis')
                                        ->label('Diagnosa Masuk')
                                        ->required()
                                        ->placeholder('Masukkan diagnosa awal pasien')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Kamar')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Section::make('Pemilihan Kamar')
                                ->schema([
                                    Select::make('room_id')
                                        ->label('Kamar')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(fn () => Room::where('is_active', true)
                                            ->where('available_beds', '>', 0)
                                            ->get()
                                            ->mapWithKeys(fn ($room) => [
                                                $room->id => "{$room->name} ({$room->room_class}) - Tersedia: {$room->available_beds}"
                                            ])
                                            ->toArray())
                                        ->placeholder('Pilih kamar')
                                        ->prefixIcon('heroicon-m-home')
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('bed_id', null)),

                                    Select::make('bed_id')
                                        ->label('Tempat Tidur')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->options(function (Get $get): array {
                                            $roomId = $get('room_id');
                                            if (!$roomId) {
                                                return [];
                                            }

                                            return Bed::where('room_id', $roomId)
                                                ->where('status', 'kosong')
                                                ->where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(fn ($bed) => [
                                                    $bed->id => "Bed {$bed->bed_number}" . ($bed->bed_name ? " - {$bed->bed_name}" : '')
                                                ])
                                                ->toArray();
                                        })
                                        ->placeholder('Pilih tempat tidur')
                                        ->prefixIcon('heroicon-m-home-modern')
                                        ->disabled(fn (Get $get): bool => !$get('room_id')),

                                    Placeholder::make('room_info')
                                        ->label('Informasi Kamar')
                                        ->content(function (Get $get): string {
                                            $roomId = $get('room_id');
                                            if (!$roomId) {
                                                return 'Pilih kamar untuk melihat informasi';
                                            }

                                            $room = Room::find($roomId);
                                            if (!$room) {
                                                return 'Kamar tidak ditemukan';
                                            }

                                            $facilities = is_array($room->facilities) 
                                                ? implode(', ', array_keys($room->facilities)) 
                                                : '-';

                                            return implode("\n", [
                                                "Kelas: {$room->room_class}",
                                                "Lantai: {$room->floor}",
                                                "Gedung: {$room->building}",
                                                "Tarif Umum: Rp " . number_format($room->base_price, 0, ',', '.'),
                                                "Tarif BPJS: Rp " . number_format($room->bpjs_price, 0, ',', '.'),
                                                "Fasilitas: {$facilities}",
                                            ]);
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Step::make('Penanggung')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            Section::make('Cara Bayar')
                                ->schema([
                                    Select::make('payment_type')
                                        ->label('Cara Bayar')
                                        ->required()
                                        ->options([
                                            'umum' => 'Umum',
                                            'bpjs' => 'BPJS',
                                            'asuransi' => 'Asuransi',
                                            'perusahaan' => 'Perusahaan',
                                            'karyawan' => 'Karyawan',
                                        ])
                                        ->native(false)
                                        ->prefixIcon('heroicon-m-credit-card'),

                                    TextInput::make('insurance_name')
                                        ->label('Nama Asuransi/Perusahaan')
                                        ->maxLength(100)
                                        ->placeholder('Nama asuransi atau perusahaan')
                                        ->visible(fn (Get $get): bool => 
                                            in_array($get('payment_type'), ['asuransi', 'perusahaan'])),

                                    TextInput::make('insurance_number')
                                        ->label('Nomor Asuransi')
                                        ->maxLength(50)
                                        ->placeholder('Nomor kartu asuransi')
                                        ->visible(fn (Get $get): bool => 
                                            in_array($get('payment_type'), ['bpjs', 'asuransi', 'perusahaan'])),
                                ])
                                ->columns(2),

                            Section::make('Deposit')
                                ->schema([
                                    TextInput::make('deposit_amount')
                                        ->label('Jumlah Deposit')
                                        ->required()
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->default(0)
                                        ->minValue(0)
                                        ->step(1000),

                                    Select::make('deposit_payment_method')
                                        ->label('Metode Pembayaran Deposit')
                                        ->options([
                                            'cash' => 'Tunai',
                                            'transfer' => 'Transfer Bank',
                                            'card' => 'Kartu Debit/Kredit',
                                            'qris' => 'QRIS',
                                        ])
                                        ->native(false),
                                ])
                                ->columns(2),
                        ]),

                    Step::make('Info Tambahan')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Rujukan')
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

                            Section::make('Keluhan dan Keterangan')
                                ->schema([
                                    Textarea::make('complaint')
                                        ->label('Keluhan Utama')
                                        ->required()
                                        ->placeholder('Masukkan keluhan utama pasien')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    Textarea::make('notes')
                                        ->label('Catatan')
                                        ->placeholder('Catatan tambahan')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Status')
                                ->schema([
                                    Hidden::make('visit_type')
                                        ->default('rawat_inap'),

                                    Hidden::make('status')
                                        ->default('registered'),

                                    Toggle::make('is_completed')
                                        ->label('Kunjungan Selesai')
                                        ->default(false),
                                ]),
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

                TextColumn::make('bed.room.name')
                    ->label('Kamar')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('bed.bed_number')
                    ->label('Bed')
                    ->alignCenter()
                    ->placeholder('-'),

                BadgeColumn::make('bed.room.room_class')
                    ->label('Kelas')
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'VVIP' => 'danger',
                        'VIP' => 'warning',
                        'Kelas I' => 'primary',
                        'Kelas II' => 'info',
                        'Kelas III' => 'success',
                        'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('visit_date')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('length_of_stay')
                    ->label('LOS (Hari)')
                    ->alignCenter()
                    ->numeric()
                    ->state(fn (Model $record): int => 
                        $record->visit_date ? $record->visit_date->diffInDays(now()) + 1 : 0)
                    ->badge()
                    ->color(fn (int $state): string => $state > 10 ? 'danger' : ($state > 5 ? 'warning' : 'success')),

                BadgeColumn::make('inpatient_status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'registered' => 'Terdaftar',
                        'admitted' => 'Dirawat',
                        'discharge_planned' => 'Rencana Pulang',
                        'discharged' => 'Sudah Pulang',
                        'transferred' => 'Pindah',
                        default => ucfirst($state ?? '-'),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'registered' => 'info',
                        'admitted' => 'primary',
                        'discharge_planned' => 'warning',
                        'discharged' => 'success',
                        'transferred' => 'purple',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'registered' => 'heroicon-m-clipboard-document',
                        'admitted' => 'heroicon-m-home',
                        'discharge_planned' => 'heroicon-m-clock',
                        'discharged' => 'heroicon-m-check-circle',
                        'transferred' => 'heroicon-m-arrow-path',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                IconColumn::make('is_completed')
                    ->label('Selesai')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('visit_date', 'desc')
            ->filters([
                SelectFilter::make('room_class')
                    ->label('Kelas Kamar')
                    ->options([
                        'VVIP' => 'VVIP',
                        'VIP' => 'VIP',
                        'Kelas I' => 'Kelas I',
                        'Kelas II' => 'Kelas II',
                        'Kelas III' => 'Kelas III',
                        'ICU' => 'ICU',
                        'NICU' => 'NICU',
                        'PICU' => 'PICU',
                        'HCU' => 'HCU',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'] ?? null,
                                fn (Builder $query, $class): Builder => $query->whereHas('bed.room', fn ($q) => 
                                    $q->where('room_class', $class))
                            );
                    })
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Terdaftar',
                        'admitted' => 'Dirawat',
                        'discharge_planned' => 'Rencana Pulang',
                        'discharged' => 'Sudah Pulang',
                    ])
                    ->attribute('inpatient_status')
                    ->native(false),

                Filter::make('admission_date')
                    ->label('Tanggal Masuk')
                    ->schema([
                        DatePicker::make('visit_date_from')
                            ->label('Dari')
                            ->native(false),
                        DatePicker::make('visit_date_to')
                            ->label('Sampai')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['visit_date_from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['visit_date_to'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    }),

                Filter::make('has_deposit')
                    ->label('Ada Deposit')
                    ->query(fn (Builder $query): Builder => $query->where('deposit_amount', '>', 0))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('transferRoom')
                        ->label('Pindah Kamar')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => 
                            !$record->is_completed && 
                            in_array($record->inpatient_status, ['registered', 'admitted', 'transferred']))
                        ->schema([
                            Select::make('new_room_id')
                                ->label('Kamar Baru')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->options(fn () => Room::where('is_active', true)
                                    ->where('available_beds', '>', 0)
                                    ->pluck('name', 'id'))
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('new_bed_id', null)),

                            Select::make('new_bed_id')
                                ->label('Bed Baru')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->options(function (Get $get): array {
                                    $roomId = $get('new_room_id');
                                    if (!$roomId) {
                                        return [];
                                    }
                                    return Bed::where('room_id', $roomId)
                                        ->where('status', 'kosong')
                                        ->where('is_active', true)
                                        ->pluck('bed_number', 'id')
                                        ->toArray();
                                })
                                ->disabled(fn (Get $get): bool => !$get('new_room_id')),

                            Textarea::make('transfer_reason')
                                ->label('Alasan Pindah')
                                ->placeholder('Masukkan alasan pemindahan')
                                ->rows(2),
                        ])
                        ->action(function (Model $record, array $data, InpatientService $service): void {
                            $service->transferPatient($record->id, $data['new_bed_id']);
                            Notification::make()
                                ->title('Pasien berhasil dipindahkan')
                                ->success()
                                ->send();
                        }),

                    Action::make('discharge')
                        ->label('Pulang')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Model $record): bool => 
                            !$record->is_completed && 
                            in_array($record->inpatient_status, ['registered', 'admitted', 'discharge_planned', 'transferred']))
                        ->schema([
                            DatePicker::make('discharge_date')
                                ->label('Tanggal Pulang')
                                ->required()
                                ->default(now())
                                ->native(false),

                            Select::make('discharge_status')
                                ->label('Status Pulang')
                                ->required()
                                ->options([
                                    'sembuh' => 'Sembuh',
                                    'membaik' => 'Membaik',
                                    'belum_sembuh' => 'Belum Sembuh',
                                    'meninggal' => 'Meninggal',
                                    'dirujuk' => 'Dirujuk',
                                    'kabur' => 'Kabur',
                                    'atas_permintaan' => 'Atas Permintaan Sendiri',
                                ])
                                ->native(false),

                            Textarea::make('discharge_diagnosis')
                                ->label('Diagnosa Akhir')
                                ->placeholder('Masukkan diagnosa akhir')
                                ->rows(2),

                            Textarea::make('discharge_notes')
                                ->label('Catatan Pulang')
                                ->placeholder('Catatan tambahan')
                                ->rows(2),
                        ])
                        ->action(function (Model $record, array $data, InpatientService $service): void {
                            $service->dischargePatient($record->id, $data);
                            Notification::make()
                                ->title('Pasien berhasil dipulangkan')
                                ->success()
                                ->send();
                        }),

                    Action::make('planDischarge')
                        ->label('Rencana Pulang')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->visible(fn (Model $record): bool => 
                            !$record->is_completed && 
                            $record->inpatient_status === 'admitted')
                        ->schema([
                            DatePicker::make('planned_discharge_date')
                                ->label('Tanggal Rencana Pulang')
                                ->required()
                                ->default(now()->addDay())
                                ->native(false),

                            Textarea::make('discharge_plan_notes')
                                ->label('Catatan')
                                ->placeholder('Catatan rencana pulang')
                                ->rows(2),
                        ])
                        ->action(function (Model $record, array $data): void {
                            $record->update([
                                'inpatient_status' => 'discharge_planned',
                                'planned_discharge_date' => $data['planned_discharge_date'],
                                'discharge_plan_notes' => $data['discharge_plan_notes'],
                            ]);
                            Notification::make()
                                ->title('Rencana pulang berhasil disimpan')
                                ->success()
                                ->send();
                        }),

                    Action::make('printSummary')
                        ->label('Cetak Ringkasan')
                        ->icon('heroicon-m-printer')
                        ->color('gray')
                        ->url(fn (Model $record): string => route('inpatient.summary', $record))
                        ->openUrlInNewTab(),

                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pasien rawat inap')
            ->emptyStateDescription('Daftarkan pasien rawat inap pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-home');
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
            'index' => ListInpatients::route('/'),
            'create' => CreateInpatient::route('/create'),
            'view' => ViewInpatient::route('/{record}'),
            'edit' => EditInpatient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('visit_type', 'rawat_inap')
            ->with(['patient', 'doctor', 'bed.room']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('visit_type', 'rawat_inap')
            ->where('is_completed', false)
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
            InpatientStats::class,
            RoomOccupancy::class,
        ];
    }
}



