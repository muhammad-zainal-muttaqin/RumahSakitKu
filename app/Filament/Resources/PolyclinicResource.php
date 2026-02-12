<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
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
use App\Filament\Resources\PolyclinicResource\Pages\ListPolyclinics;
use App\Filament\Resources\PolyclinicResource\Pages\CreatePolyclinic;
use App\Filament\Resources\PolyclinicResource\Pages\ViewPolyclinic;
use App\Filament\Resources\PolyclinicResource\Pages\EditPolyclinic;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Polyclinic;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PolyclinicResource extends Resource
{
    protected static ?string $model = Polyclinic::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Poliklinik';

    protected static ?string $modelLabel = 'Poliklinik';

    protected static ?string $pluralModelLabel = 'Poliklinik';

    protected static ?int $navigationSort = 1;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Poliklinik')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->placeholder('POL001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Poliklinik')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Poliklinik Umum'),

                        Select::make('category')
                            ->label('Kategori')
                            ->required()
                            ->options([
                                'umum' => 'Umum',
                                'spesialis' => 'Spesialis',
                                'gigi' => 'Gigi',
                                'anak' => 'Anak',
                                'bedah' => 'Bedah',
                                'penyakit_dalam' => 'Penyakit Dalam',
                                'syaraf' => 'Syaraf',
                                'jiwa' => 'Jiwa',
                                'rehabilitasi' => 'Rehabilitasi',
                                'radiologi' => 'Radiologi',
                                'laboratorium' => 'Laboratorium',
                            ])
                            ->native(false),

                        TextInput::make('queue_prefix')
                            ->label('Prefix Antrian')
                            ->required()
                            ->maxLength(5)
                            ->default('A')
                            ->placeholder('A'),

                        TextInput::make('bpjs_poli_code')
                            ->label('Kode BPJS')
                            ->maxLength(10)
                            ->placeholder('001')
                            ->prefixIcon('heroicon-m-identification'),

                        TextInput::make('bpjs_poli_name')
                            ->label('Nama BPJS')
                            ->maxLength(100)
                            ->placeholder('Poli Umum'),
                    ])
                    ->columns(2),

                Section::make('Operasional')
                    ->schema([
                        TimePicker::make('open_time')
                            ->label('Jam Buka')
                            ->required()
                            ->default('08:00'),

                        TimePicker::make('close_time')
                            ->label('Jam Tutup')
                            ->required()
                            ->default('16:00'),

                        TextInput::make('max_queue_per_day')
                            ->label('Maksimal Antrian per Hari')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(1)
                            ->maxValue(1000),
                    ])
                    ->columns(3),

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

                BadgeColumn::make('category')
                    ->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => 'Umum',
                        'spesialis' => 'Spesialis',
                        'gigi' => 'Gigi',
                        'anak' => 'Anak',
                        'bedah' => 'Bedah',
                        'penyakit_dalam' => 'Penyakit Dalam',
                        'syaraf' => 'Syaraf',
                        'jiwa' => 'Jiwa',
                        'rehabilitasi' => 'Rehabilitasi',
                        'radiologi' => 'Radiologi',
                        'laboratorium' => 'Laboratorium',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'gray',
                        'spesialis' => 'primary',
                        'gigi' => 'success',
                        'anak' => 'warning',
                        'bedah' => 'danger',
                        'penyakit_dalam' => 'info',
                        'syaraf' => 'purple',
                        'jiwa' => 'pink',
                        'rehabilitasi' => 'teal',
                        'radiologi' => 'indigo',
                        'laboratorium' => 'cyan',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('queue_prefix')
                    ->label('Prefix')
                    ->alignCenter()
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('bpjs_poli_code')
                    ->label('Kode BPJS')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('formatted_operating_hours')
                    ->label('Jam Operasional')
                    ->alignCenter(),

                TextColumn::make('max_queue_per_day')
                    ->label('Maks Antrian')
                    ->alignCenter()
                    ->numeric()
                    ->sortable(),

                TextColumn::make('today_visit_count')
                    ->label('Antrian Hari Ini')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color(fn (Model $record): string => $record->has_reached_quota ? 'danger' : 'success'),

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
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'umum' => 'Umum',
                        'spesialis' => 'Spesialis',
                        'gigi' => 'Gigi',
                        'anak' => 'Anak',
                        'bedah' => 'Bedah',
                        'penyakit_dalam' => 'Penyakit Dalam',
                        'syaraf' => 'Syaraf',
                        'jiwa' => 'Jiwa',
                        'rehabilitasi' => 'Rehabilitasi',
                        'radiologi' => 'Radiologi',
                        'laboratorium' => 'Laboratorium',
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
            ->emptyStateHeading('Belum ada poliklinik')
            ->emptyStateDescription('Buat poliklinik pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-building-office-2');
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
            'index' => ListPolyclinics::route('/'),
            'create' => CreatePolyclinic::route('/create'),
            'view' => ViewPolyclinic::route('/{record}'),
            'edit' => EditPolyclinic::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['visits' => fn ($query) => $query->whereDate('visit_date', today())]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}

