<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\RoomResource\RelationManagers\BedsRelationManager;
use App\Filament\Resources\RoomResource\Pages\ListRooms;
use App\Filament\Resources\RoomResource\Pages\CreateRoom;
use App\Filament\Resources\RoomResource\Pages\ViewRoom;
use App\Filament\Resources\RoomResource\Pages\EditRoom;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Room;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Kamar';

    protected static ?string $modelLabel = 'Kamar';

    protected static ?string $pluralModelLabel = 'Kamar';

    protected static ?int $navigationSort = 2;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kamar')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('K001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Kamar')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Kamar Melati'),

                        Select::make('room_class')
                            ->label('Kelas Kamar')
                            ->required()
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
                            ->native(false),

                        TextInput::make('floor')
                            ->label('Lantai')
                            ->maxLength(10)
                            ->placeholder('1')
                            ->prefixIcon('heroicon-m-building-office'),

                        TextInput::make('building')
                            ->label('Gedung')
                            ->maxLength(50)
                            ->placeholder('Gedung A')
                            ->prefixIcon('heroicon-m-building-library'),

                        Select::make('gender_preference')
                            ->label('Preferensi Gender')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                                'Campur' => 'Campur',
                            ])
                            ->native(false)
                            ->placeholder('Pilih preferensi'),
                    ])
                    ->columns(2),

                Section::make('Kapasitas')
                    ->schema([
                        TextInput::make('total_beds')
                            ->label('Total Tempat Tidur')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(50),

                        TextInput::make('available_beds')
                            ->label('Tempat Tidur Tersedia')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Tarif')
                    ->schema([
                        TextInput::make('base_price')
                            ->label('Harga Dasar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),

                        TextInput::make('bpjs_price')
                            ->label('Harga BPJS')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Section::make('Fasilitas')
                    ->schema([
                        KeyValue::make('facilities')
                            ->label('Fasilitas')
                            ->keyLabel('Fasilitas')
                            ->valueLabel('Keterangan')
                            ->addable()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Deskripsi')
                    ->schema([
                        Textarea::make('description')
                            ->label('Deskripsi')
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
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                BadgeColumn::make('room_class')
                    ->label('Kelas')
                    ->formatStateUsing(fn (string $state): string => $state)
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

                TextColumn::make('floor')
                    ->label('Lantai')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('building')
                    ->label('Gedung')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('gender_preference')
                    ->label('Gender')
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        'Campur' => 'Campur',
                        default => '-',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'pink',
                        'Campur' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('occupancy_rate')
                    ->label('Tingkat Hunian')
                    ->alignCenter()
                    ->suffix('%')
                    ->numeric(decimalPlaces: 1)
                    ->badge()
                    ->color(fn (Model $record): string => $record->occupancy_rate >= 80 ? 'danger' : ($record->occupancy_rate >= 50 ? 'warning' : 'success'))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';
                        return $query->orderByRaw('(total_beds - available_beds) / NULLIF(total_beds, 0) ' . $direction);
                    }),

                TextColumn::make('available_beds')
                    ->label('Tersedia')
                    ->alignCenter()
                    ->numeric()
                    ->formatStateUsing(fn (Model $record): string => "{$record->available_beds}/{$record->total_beds}")
                    ->badge()
                    ->color(fn (Model $record): string => $record->available_beds === 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Harga Dasar')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('bpjs_price')
                    ->label('Harga BPJS')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('-')
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
            ->defaultSort('code', 'asc')
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
                    ->native(false),

                SelectFilter::make('floor')
                    ->label('Lantai')
                    ->options(fn () => Room::distinct()->pluck('floor', 'floor')->toArray())
                    ->native(false),

                SelectFilter::make('gender_preference')
                    ->label('Preferensi Gender')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                        'Campur' => 'Campur',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('has_available_beds')
                    ->label('Tersedia Tempat Tidur')
                    ->query(fn (Builder $query): Builder => $query->where('available_beds', '>', 0))
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
            ->emptyStateHeading('Belum ada kamar')
            ->emptyStateDescription('Buat kamar pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-home');
    }

    public static function getRelations(): array
    {
        return [
            BedsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'view' => ViewRoom::route('/{record}'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
