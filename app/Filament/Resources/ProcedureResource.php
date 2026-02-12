<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ProcedureResource\Pages\ListProcedures;
use App\Filament\Resources\ProcedureResource\Pages\CreateProcedure;
use App\Filament\Resources\ProcedureResource\Pages\ViewProcedure;
use App\Filament\Resources\ProcedureResource\Pages\EditProcedure;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Procedure;
use App\Models\MasterData\ProcedureCategory;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProcedureResource extends Resource
{
    protected static ?string $model = Procedure::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Tindakan';

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Tindakan';

    protected static ?int $navigationSort = 7;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tindakan')
                    ->schema([
                        TextInput::make('procedure_code')
                            ->label('Kode Tindakan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('TND001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Tindakan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Pemeriksaan Darah Lengkap'),

                        Select::make('category_id')
                            ->label('Kategori')
                            ->required()
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Harga')
                    ->schema([
                        TextInput::make('base_price')
                            ->label('Harga Dasar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $basePrice = floatval($get('base_price') ?? 0);
                                $materialCost = floatval($get('material_cost') ?? 0);
                                $set('total_price', $basePrice + $materialCost);
                            }),

                        TextInput::make('bpjs_tariff')
                            ->label('Tarif BPJS')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),

                        TextInput::make('material_cost')
                            ->label('Biaya Bahan/Material')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $basePrice = floatval($get('base_price') ?? 0);
                                $materialCost = floatval($get('material_cost') ?? 0);
                                $set('total_price', $basePrice + $materialCost);
                            }),

                        TextInput::make('total_price')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function (Get $get, $state) {
                                $basePrice = floatval($get('base_price') ?? 0);
                                $materialCost = floatval($get('material_cost') ?? 0);
                                return $basePrice + $materialCost;
                            }),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_bpjs_covered')
                            ->label('Ditanggung BPJS')
                            ->required()
                            ->default(true),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('procedure_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                BadgeColumn::make('category.name')
                    ->label('Kategori')
                    ->color(fn (Model $record): string => $record->category?->color ?? 'gray')
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Harga Dasar')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('bpjs_tariff')
                    ->label('Tarif BPJS')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('material_cost')
                    ->label('Biaya Bahan')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                IconColumn::make('is_bpjs_covered')
                    ->label('BPJS')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

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
            ->defaultSort('procedure_code', 'asc')
            ->groups([
                Group::make('category.name')
                    ->label('Kategori')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                TernaryFilter::make('is_bpjs_covered')
                    ->label('Ditanggung BPJS')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
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
            ->emptyStateHeading('Belum ada tindakan')
            ->emptyStateDescription('Buat tindakan pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
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
            'index' => ListProcedures::route('/'),
            'create' => CreateProcedure::route('/create'),
            'view' => ViewProcedure::route('/{record}'),
            'edit' => EditProcedure::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('category');
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

