<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Employee;

/**
 * Employee Resource
 * 
 * Filament resource for managing hospital staff/employees.
 * 
 * @package App\Filament\Resources
 */

use App\Models\MasterData\Polyclinic;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    protected static ?int $navigationSort = 4;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('employee_code')
                            ->label('Kode Pegawai')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('EMP001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(50)
                            ->placeholder('198501012010011001')
                            ->prefixIcon('heroicon-m-identification'),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Dr. John Doe'),

                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->native(false),

                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->maxDate(now()),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->maxLength(65535)
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('08123456789')
                            ->prefixIcon('heroicon-m-phone'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->placeholder('email@example.com')
                            ->prefixIcon('heroicon-m-envelope'),

                        Select::make('employee_type')
                            ->label('Jenis Pegawai')
                            ->required()
                            ->options([
                                'tetap' => 'Tetap',
                                'kontrak' => 'Kontrak',
                                'honorer' => 'Honorer',
                                'outsourcing' => 'Outsourcing',
                            ])
                            ->default('tetap')
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Informasi Dokter')
                    ->schema([
                        Toggle::make('is_doctor')
                            ->label('Adalah Dokter')
                            ->live()
                            ->default(false),

                        TextInput::make('doctor_title')
                            ->label('Gelar Dokter')
                            ->maxLength(50)
                            ->placeholder('dr. / Dr. / drg.')
                            ->visible(fn (Get $get): bool => $get('is_doctor')),

                        TextInput::make('sip_number')
                            ->label('Nomor SIP')
                            ->maxLength(50)
                            ->placeholder('SIP-12345')
                            ->visible(fn (Get $get): bool => $get('is_doctor'))
                            ->prefixIcon('heroicon-m-document-check'),

                        DatePicker::make('sip_expiry_date')
                            ->label('Masa Berlaku SIP')
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('is_doctor')),

                        TextInput::make('str_number')
                            ->label('Nomor STR')
                            ->maxLength(50)
                            ->placeholder('STR-12345')
                            ->visible(fn (Get $get): bool => $get('is_doctor'))
                            ->prefixIcon('heroicon-m-document-text'),

                        DatePicker::make('str_expiry_date')
                            ->label('Masa Berlaku STR')
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('is_doctor')),

                        Select::make('specialist_polyclinic_id')
                            ->label('Poliklinik Spesialis')
                            ->relationship('specialistPolyclinic', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('is_doctor'))
                            ->placeholder('Pilih poliklinik'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Informasi Perawat')
                    ->schema([
                        Toggle::make('is_nurse')
                            ->label('Adalah Perawat')
                            ->live()
                            ->default(false),

                        TextInput::make('sip_nurse_number')
                            ->label('Nomor SIP Perawat')
                            ->maxLength(50)
                            ->placeholder('SIP-12345')
                            ->visible(fn (Get $get): bool => $get('is_nurse'))
                            ->prefixIcon('heroicon-m-document-check'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Profesi Lainnya')
                    ->schema([
                        TextInput::make('profession')
                            ->label('Profesi')
                            ->maxLength(50)
                            ->placeholder('Apoteker, Radiografer, dll'),

                        TextInput::make('certification_number')
                            ->label('Nomor Sertifikasi')
                            ->maxLength(50)
                            ->placeholder('SERT-12345'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Kepegawaian')
                    ->schema([
                        DatePicker::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        DatePicker::make('resign_date')
                            ->label('Tanggal Resign')
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'aktif' => 'Aktif',
                                'cuti' => 'Cuti',
                                'nonaktif' => 'Nonaktif',
                                'pensiun' => 'Pensiun',
                            ])
                            ->default('aktif')
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Foto')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Foto Pegawai')
                            ->image()
                            ->directory('employee-photos')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=User&background=random')
                    ->size(40),

                TextColumn::make('employee_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                BadgeColumn::make('gender')
                    ->label('JK')
                    ->formatStateUsing(fn (string $state): string => $state === 'L' ? 'Laki-laki' : 'Perempuan')
                    ->color(fn (string $state): string => $state === 'L' ? 'info' : 'pink')
                    ->sortable(),

                IconColumn::make('is_doctor')
                    ->label('Dokter')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_nurse')
                    ->label('Perawat')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('specialistPolyclinic.name')
                    ->label('Poliklinik')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sip_number')
                    ->label('SIP')
                    ->placeholder('-')
                    ->toggleable()
                    ->visible(fn (?Model $record): bool => $record?->is_doctor === true),

                BadgeColumn::make('sip_status')
                    ->label('Status SIP')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'valid' => 'Valid',
                        'expiring_soon' => 'Segera Expired',
                        'expired' => 'Expired',
                        'no_license' => 'Tidak Ada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        'no_license' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'valid' => 'heroicon-o-check-circle',
                        'expiring_soon' => 'heroicon-o-exclamation-triangle',
                        'expired' => 'heroicon-o-x-circle',
                        'no_license' => 'heroicon-o-minus-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->visible(fn (?Model $record): bool => $record?->is_doctor === true)
                    ->toggleable(),

                BadgeColumn::make('str_status')
                    ->label('Status STR')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'valid' => 'Valid',
                        'expiring_soon' => 'Segera Expired',
                        'expired' => 'Expired',
                        'no_license' => 'Tidak Ada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        'no_license' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'valid' => 'heroicon-o-check-circle',
                        'expiring_soon' => 'heroicon-o-exclamation-triangle',
                        'expired' => 'heroicon-o-x-circle',
                        'no_license' => 'heroicon-o-minus-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->visible(fn (?Model $record): bool => $record?->is_doctor === true)
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aktif' => 'Aktif',
                        'cuti' => 'Cuti',
                        'nonaktif' => 'Nonaktif',
                        'pensiun' => 'Pensiun',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'cuti' => 'warning',
                        'nonaktif' => 'danger',
                        'pensiun' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('join_date')
                    ->label('Bergabung')
                    ->date('d M Y')
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
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('employee_type')
                    ->label('Jenis Pegawai')
                    ->options([
                        'tetap' => 'Tetap',
                        'kontrak' => 'Kontrak',
                        'honorer' => 'Honorer',
                        'outsourcing' => 'Outsourcing',
                    ])
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'cuti' => 'Cuti',
                        'nonaktif' => 'Nonaktif',
                        'pensiun' => 'Pensiun',
                    ])
                    ->native(false),

                Filter::make('is_doctor')
                    ->label('Dokter')
                    ->query(fn (Builder $query): Builder => $query->where('is_doctor', true))
                    ->toggle(),

                Filter::make('is_nurse')
                    ->label('Perawat')
                    ->query(fn (Builder $query): Builder => $query->where('is_nurse', true))
                    ->toggle(),

                SelectFilter::make('specialist_polyclinic_id')
                    ->label('Poliklinik')
                    ->relationship('specialistPolyclinic', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('sip_expiring')
                    ->label('SIP Segera Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_doctor', true)
                        ->where('sip_expiry_date', '<=', now()->addDays(30))
                        ->where('sip_expiry_date', '>=', now()))
                    ->toggle(),

                Filter::make('sip_expired')
                    ->label('SIP Sudah Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('is_doctor', true)
                        ->where('sip_expiry_date', '<', now()))
                    ->toggle(),
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
            ->emptyStateHeading('Belum ada pegawai')
            ->emptyStateDescription('Buat pegawai pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['specialistPolyclinic']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

