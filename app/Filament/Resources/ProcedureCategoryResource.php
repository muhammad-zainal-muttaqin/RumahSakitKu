<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ProcedureCategoryResource\RelationManagers\ProceduresRelationManager;
use App\Filament\Resources\ProcedureCategoryResource\Pages\ListProcedureCategories;
use App\Filament\Resources\ProcedureCategoryResource\Pages\CreateProcedureCategory;
use App\Filament\Resources\ProcedureCategoryResource\Pages\ViewProcedureCategory;
use App\Filament\Resources\ProcedureCategoryResource\Pages\EditProcedureCategory;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\ProcedureCategory;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProcedureCategoryResource extends Resource
{
    protected static ?string $model = ProcedureCategory::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Kategori Tindakan';

    protected static ?string $modelLabel = 'Kategori Tindakan';

    protected static ?string $pluralModelLabel = 'Kategori Tindakan';

    protected static ?int $navigationSort = 6;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('KAT001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Tindakan Bedah'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Tampilan')
                    ->schema([
                        ColorPicker::make('color')
                            ->label('Warna')
                            ->default('#3b82f6'),

                        TextInput::make('icon')
                            ->label('Icon (Heroicons)')
                            ->maxLength(50)
                            ->placeholder('heroicon-o-scissors')
                            ->prefixIcon('heroicon-m-paint-brush'),
                    ])
                    ->columns(2)
                    ->collapsible(),

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
                ColorColumn::make('color')
                    ->label('Warna')
                    ->copyable(),

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

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->placeholder('-')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('icon')
                    ->label('Icon')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('procedures_count')
                    ->label('Jumlah Tindakan')
                    ->counts('procedures')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
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
            ->defaultSort('name', 'asc')
            ->filters([
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
            ->emptyStateHeading('Belum ada kategori tindakan')
            ->emptyStateDescription('Buat kategori tindakan pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-folder');
    }

    public static function getRelations(): array
    {
        return [
            ProceduresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcedureCategories::route('/'),
            'create' => CreateProcedureCategory::route('/create'),
            'view' => ViewProcedureCategory::route('/{record}'),
            'edit' => EditProcedureCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('procedures');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
