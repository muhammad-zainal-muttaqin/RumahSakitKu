<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use App\Services\InpatientService;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BedResource\Pages\ListBeds;
use App\Filament\Resources\BedResource\Pages\CreateBed;
use App\Filament\Resources\BedResource\Pages\ViewBed;
use App\Filament\Resources\BedResource\Pages\EditBed;
use App\Filament\Resources\BedResource\Pages\BedManagement;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BedResource extends Resource
{
    protected static ?string $model = Bed::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Tempat Tidur';

    protected static ?string $modelLabel = 'Tempat Tidur';

    protected static ?string $pluralModelLabel = 'Tempat Tidur';

    protected static ?int $navigationSort = 3;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tempat Tidur')
                    ->schema([
                        Select::make('room_id')
                            ->label('Kamar')
                            ->required()
                            ->relationship('room', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        TextInput::make('bed_number')
                            ->label('Nomor Tempat Tidur')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('1')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('bed_name')
                            ->label('Nama Tempat Tidur')
                            ->maxLength(50)
                            ->placeholder('Bed A1'),

                        Select::make('bed_type')
                            ->label('Tipe')
                            ->required()
                            ->options([
                                'standard' => 'Standard',
                                'elektrik' => 'Elektrik',
                                'air' => 'Air',
                                'bayi' => 'Bayi',
                                'iso' => 'Isolasi',
                            ])
                            ->default('standard')
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'kosong' => 'Kosong',
                                'terisi' => 'Terisi',
                                'reserved' => 'Dipesan',
                                'maintenance' => 'Maintenance',
                                'cleaning' => 'Dibersihkan',
                            ])
                            ->default('kosong')
                            ->native(false)
                            ->live(),

                        Select::make('current_visit_id')
                            ->label('Kunjungan Saat Ini')
                            ->relationship('currentVisit', 'id')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('status') === 'terisi')
                            ->placeholder('Pilih kunjungan'),
                    ])
                    ->columns(2),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Status')
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
                TextColumn::make('room.name')
                    ->label('Kamar')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('room.room_class')
                    ->label('Kelas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'VVIP' => 'danger',
                        'VIP' => 'warning',
                        'Kelas I' => 'primary',
                        'Kelas II' => 'info',
                        'Kelas III' => 'success',
                        'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('bed_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bed_name')
                    ->label('Nama')
                    ->searchable()
                    ->placeholder('-'),

                BadgeColumn::make('bed_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'elektrik' => 'Elektrik',
                        'air' => 'Air',
                        'bayi' => 'Bayi',
                        'iso' => 'Isolasi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'elektrik' => 'primary',
                        'air' => 'info',
                        'bayi' => 'warning',
                        'iso' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kosong' => 'Kosong',
                        'terisi' => 'Terisi',
                        'reserved' => 'Dipesan',
                        'maintenance' => 'Maintenance',
                        'cleaning' => 'Dibersihkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kosong' => 'success',
                        'terisi' => 'danger',
                        'reserved' => 'warning',
                        'maintenance' => 'gray',
                        'cleaning' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'kosong' => 'heroicon-o-check-circle',
                        'terisi' => 'heroicon-o-user',
                        'reserved' => 'heroicon-o-clock',
                        'maintenance' => 'heroicon-o-wrench',
                        'cleaning' => 'heroicon-o-sparkles',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('currentVisit.patient.name')
                    ->label('Pasien')
                    ->placeholder('-')
                    ->searchable()
                    ->visible(fn (?Model $record): bool => $record?->status === 'terisi'),

                TextColumn::make('occupied_at')
                    ->label('Dihuni Sejak')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

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
            ->defaultSort('room_id', 'asc')
            ->filters([
                SelectFilter::make('room_id')
                    ->label('Kamar')
                    ->relationship('room', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'kosong' => 'Kosong',
                        'terisi' => 'Terisi',
                        'reserved' => 'Dipesan',
                        'maintenance' => 'Maintenance',
                        'cleaning' => 'Dibersihkan',
                    ])
                    ->native(false),

                SelectFilter::make('bed_type')
                    ->label('Tipe')
                    ->options([
                        'standard' => 'Standard',
                        'elektrik' => 'Elektrik',
                        'air' => 'Air',
                        'bayi' => 'Bayi',
                        'iso' => 'Isolasi',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('occupy')
                        ->label('Isi')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->visible(fn (?Model $record): bool => $record?->status === 'kosong')
                        ->url(fn (Model $record): string => route('filament.admin.resources.inpatients.create', ['bed_id' => $record->id])),

                    Action::make('setReserved')
                        ->label('Pesan')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Pesan Tempat Tidur')
                        ->modalDescription('Apakah Anda yakin ingin memesan tempat tidur ini?')
                        ->modalSubmitActionLabel('Ya, Pesan')
                        ->visible(fn (?Model $record): bool => $record?->status === 'kosong')
                        ->action(function (Model $record): void {
                            $record->setReserved();
                            Notification::make()
                                ->title('Tempat tidur dipesan')
                                ->success()
                                ->send();
                        }),

                    Action::make('setAvailable')
                        ->label('Tersedia')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Jadikan Tersedia')
                        ->modalDescription('Apakah Anda yakin ingin menjadikan tempat tidur ini tersedia?')
                        ->modalSubmitActionLabel('Ya, Jadikan Tersedia')
                        ->visible(fn (?Model $record): bool => in_array($record?->status, ['reserved', 'maintenance', 'cleaning']))
                        ->action(function (Model $record): void {
                            $record->status = 'kosong';
                            $record->save();
                            Notification::make()
                                ->title('Tempat tidur tersedia')
                                ->success()
                                ->send();
                        }),

                    Action::make('setMaintenance')
                        ->label('Maintenance')
                        ->icon('heroicon-o-wrench')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Set Maintenance')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status tempat tidur ini menjadi maintenance?')
                        ->modalSubmitActionLabel('Ya, Set Maintenance')
                        ->visible(fn (?Model $record): bool => !in_array($record?->status, ['terisi', 'maintenance']))
                        ->action(function (Model $record): void {
                            $record->setMaintenance('Maintenance oleh admin');
                            Notification::make()
                                ->title('Status diubah ke Maintenance')
                                ->success()
                                ->send();
                        }),

                    Action::make('setCleaning')
                        ->label('Bersihkan')
                        ->icon('heroicon-o-sparkles')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Set Cleaning')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status tempat tidur ini menjadi dibersihkan?')
                        ->modalSubmitActionLabel('Ya, Set Cleaning')
                        ->visible(fn (?Model $record): bool => !in_array($record?->status, ['terisi', 'cleaning']))
                        ->action(function (Model $record): void {
                            $record->setCleaning();
                            Notification::make()
                                ->title('Status diubah ke Cleaning')
                                ->success()
                                ->send();
                        }),

                    Action::make('vacate')
                        ->label('Kosongkan')
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Kosongkan Tempat Tidur')
                        ->modalDescription('Apakah Anda yakin ingin mengosongkan tempat tidur ini?')
                        ->modalSubmitActionLabel('Ya, Kosongkan')
                        ->visible(fn (?Model $record): bool => $record?->status === 'terisi')
                        ->action(function (Model $record): void {
                            $record->vacate();
                            Notification::make()
                                ->title('Tempat tidur dikosongkan')
                                ->success()
                                ->send();
                        }),

                    Action::make('transfer')
                        ->label('Pindah')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->visible(fn (?Model $record): bool => $record?->status === 'terisi')
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
                        ->action(function (Model $record, array $data): void {
                            $service = app(InpatientService::class);
                            $service->transferPatient($record->current_visit_id, $data['new_bed_id'], $data['transfer_reason'] ?? null);
                            Notification::make()
                                ->title('Pasien berhasil dipindahkan')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada tempat tidur')
            ->emptyStateDescription('Buat tempat tidur pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-home-modern');
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
            'index' => ListBeds::route('/'),
            'create' => CreateBed::route('/create'),
            'view' => ViewBed::route('/{record}'),
            'edit' => EditBed::route('/{record}/edit'),
            'management' => BedManagement::route('/management'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['room', 'currentVisit.patient']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}


