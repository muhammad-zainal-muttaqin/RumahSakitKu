<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PatientResource\RelationManagers\VisitsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\MedicalRecordsRelationManager;
use App\Filament\Resources\PatientResource\Pages\ListPatients;
use App\Filament\Resources\PatientResource\Pages\CreatePatient;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\Pages\EditPatient;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\PatientResource\Pages;

/**
 * Patient Resource
 * 
 * Filament resource for managing patient data.
 * Provides CRUD operations for patient registration.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\PatientResource\RelationManagers;
use App\Models\Patient\Patient;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Data Pasien';

    protected static ?string $modelLabel = 'Pasien';

    protected static ?string $pluralModelLabel = 'Data Pasien';

    protected static ?int $navigationSort = 10;

    protected static UnitEnum|string|null $navigationGroup = 'Pendaftaran';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pribadi')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('medical_record_number')
                            ->label('No. Rekam Medis')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->prefixIcon('heroicon-m-clipboard-document')
                            ->placeholder('20260101-0001')
                            ->default(fn (): string => self::generateMedicalRecordNumber()),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Nama lengkap pasien'),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(16)
                            ->minLength(16)
                            ->placeholder('3175xxxxxxxxxxxx')
                            ->prefixIcon('heroicon-m-identification'),

                        TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Jakarta'),

                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->native(false)
                            ->maxDate(now()),

                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ])
                            ->native(false),

                        Select::make('blood_type')
                            ->label('Golongan Darah')
                            ->required()
                            ->options([
                                'A' => 'A',
                                'B' => 'B',
                                'AB' => 'AB',
                                'O' => 'O',
                                'unknown' => 'Tidak Tahu',
                            ])
                            ->default('unknown')
                            ->native(false),

                        Select::make('marital_status')
                            ->label('Status Pernikahan')
                            ->required()
                            ->options([
                                'single' => 'Belum Menikah',
                                'married' => 'Menikah',
                                'divorced' => 'Cerai',
                                'widowed' => 'Duda/Janda',
                            ])
                            ->native(false),

                        TextInput::make('occupation')
                            ->label('Pekerjaan')
                            ->maxLength(50)
                            ->placeholder('Pegawai Swasta'),
                    ])
                    ->columns(2),

                Section::make('Kontak')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->required()
                            ->maxLength(15)
                            ->tel()
                            ->placeholder('08123456789')
                            ->prefixIcon('heroicon-m-phone'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100)
                            ->placeholder('email@example.com')
                            ->prefixIcon('heroicon-m-envelope'),

                        TextInput::make('emergency_contact_name')
                            ->label('Nama Kontak Darurat')
                            ->maxLength(100)
                            ->placeholder('Nama keluarga'),

                        TextInput::make('emergency_contact_phone')
                            ->label('No. Telepon Darurat')
                            ->maxLength(15)
                            ->tel()
                            ->placeholder('08123456789')
                            ->prefixIcon('heroicon-m-phone'),
                    ])
                    ->columns(2),

                Section::make('Asuransi')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('insurance_type')
                            ->label('Jenis Asuransi')
                            ->required()
                            ->options([
                                'umum' => 'Umum',
                                'bpjs' => 'BPJS',
                                'asuransi' => 'Asuransi',
                            ])
                            ->native(false)
                            ->live(),

                        TextInput::make('insurance_number')
                            ->label('Nomor Asuransi')
                            ->maxLength(50)
                            ->placeholder('Nomor polis asuransi')
                            ->visible(fn (Get $get): bool => $get('insurance_type') === 'asuransi'),

                        TextInput::make('bpjs_card_number')
                            ->label('Nomor Kartu BPJS')
                            ->maxLength(20)
                            ->placeholder('00012345678')
                            ->prefixIcon('heroicon-m-credit-card')
                            ->visible(fn (Get $get): bool => $get('insurance_type') === 'bpjs'),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->required()
                            ->default(true),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medical_record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 6) . '****' . substr($state, -4)),

                BadgeColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'primary',
                        'female' => 'pink',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Umur')
                    ->alignCenter()
                    ->getStateUsing(fn (Model $record): int => $record->age)
                    ->suffix(' th'),

                BadgeColumn::make('blood_type')
                    ->label('Golongan Darah')
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'primary',
                        'B' => 'success',
                        'AB' => 'warning',
                        'O' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('insurance_type')
                    ->label('Asuransi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => 'Umum',
                        'bpjs' => 'BPJS',
                        'asuransi' => 'Asuransi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'gray',
                        'bpjs' => 'success',
                        'asuransi' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('bpjs_card_number')
                    ->label('No. BPJS')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('registered_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('registered_at', 'desc')
            ->filters([
                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ])
                    ->native(false),

                SelectFilter::make('blood_type')
                    ->label('Golongan Darah')
                    ->options([
                        'A' => 'A',
                        'B' => 'B',
                        'AB' => 'AB',
                        'O' => 'O',
                        'unknown' => 'Tidak Tahu',
                    ])
                    ->native(false),

                SelectFilter::make('insurance_type')
                    ->label('Jenis Asuransi')
                    ->options([
                        'umum' => 'Umum',
                        'bpjs' => 'BPJS',
                        'asuransi' => 'Asuransi',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
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
            ->emptyStateHeading('Belum ada data pasien')
            ->emptyStateDescription('Tambahkan data pasien pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            VisitsRelationManager::class,
            MedicalRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['visits', 'medicalRecords']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    /**
     * Generate medical record number in format YYYYMMDD-XXXX
     */
    private static function generateMedicalRecordNumber(): string
    {
        $prefix = now()->format('Ymd');
        $lastRecord = Patient::where('medical_record_number', 'like', $prefix . '-%')
            ->orderBy('medical_record_number', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->medical_record_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
