<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use App\Filament\Resources\LabTestResource\Pages\ListLabTests;
use App\Filament\Resources\LabTestResource\Pages\CreateLabTest;
use App\Filament\Resources\LabTestResource\Pages\ViewLabTest;
use App\Filament\Resources\LabTestResource\Pages\EditLabTest;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\LabTest;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class LabTestResource extends Resource
{
    protected static ?string $model = LabTest::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Pemeriksaan Lab';

    protected static ?string $modelLabel = 'Pemeriksaan Lab';

    protected static ?string $pluralModelLabel = 'Pemeriksaan Lab';

    protected static ?int $navigationSort = 8;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pemeriksaan')
                    ->schema([
                        TextInput::make('test_code')
                            ->label('Kode Pemeriksaan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('LAB001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Pemeriksaan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Hemoglobin (Hb)'),

                        Select::make('category')
                            ->label('Kategori')
                            ->required()
                            ->options([
                                'hematologi' => 'Hematologi',
                                'kimia_darah' => 'Kimia Darah',
                                'urinalisa' => 'Urinalisa',
                                'mikrobiologi' => 'Mikrobiologi',
                                'imunologi' => 'Imunologi',
                                'serologi' => 'Serologi',
                                'endokrinologi' => 'Endokrinologi',
                                'tumor_marker' => 'Tumor Marker',
                                'elektrolit' => 'Elektrolit',
                                'gula_darah' => 'Gula Darah',
                                'fungsi_ginjal' => 'Fungsi Ginjal',
                                'fungsi_hati' => 'Fungsi Hati',
                                'lemak_darah' => 'Lemak Darah',
                                'koagulasi' => 'Koagulasi',
                                'gas_darah' => 'Gas Darah',
                                'sitologi' => 'Sitologi',
                                'patologi_anatomi' => 'Patologi Anatomi',
                                'molekuler' => 'Molekuler',
                                'lainnya' => 'Lainnya',
                            ])
                            ->native(false),

                        Select::make('specimen_type')
                            ->label('Jenis Spesimen')
                            ->required()
                            ->options([
                                'darah' => 'Darah',
                                'urine' => 'Urine',
                                'feses' => 'Feses',
                                'sputum' => 'Sputum',
                                'lendir' => 'Lendir',
                                'jaringan' => 'Jaringan',
                                'cairan_serebrospinal' => 'Cairan Serebrospinal',
                                'cairan_sendi' => 'Cairan Sendi',
                                'cairan_pleura' => 'Cairan Pleura',
                                'swab' => 'Swab',
                                'lainnya' => 'Lainnya',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Nilai Rujukan')
                    ->schema([
                        Textarea::make('reference_value')
                            ->label('Nilai Rujukan/Normal')
                            ->placeholder('Contoh: Pria: 13.5-17.5 g/dL, Wanita: 12.0-16.0 g/dL')
                            ->rows(2)
                            ->columnSpanFull(),

                        TextInput::make('unit')
                            ->label('Satuan')
                            ->maxLength(50)
                            ->placeholder('g/dL, mg/dL, mmol/L'),
                    ])
                    ->collapsible(),

                Section::make('Harga')
                    ->schema([
                        TextInput::make('base_price')
                            ->label('Harga Dasar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),
                    ])
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
                TextColumn::make('test_code')
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
                        'hematologi' => 'Hematologi',
                        'kimia_darah' => 'Kimia Darah',
                        'urinalisa' => 'Urinalisa',
                        'mikrobiologi' => 'Mikrobiologi',
                        'imunologi' => 'Imunologi',
                        'serologi' => 'Serologi',
                        'endokrinologi' => 'Endokrinologi',
                        'tumor_marker' => 'Tumor Marker',
                        'elektrolit' => 'Elektrolit',
                        'gula_darah' => 'Gula Darah',
                        'fungsi_ginjal' => 'Fungsi Ginjal',
                        'fungsi_hati' => 'Fungsi Hati',
                        'lemak_darah' => 'Lemak Darah',
                        'koagulasi' => 'Koagulasi',
                        'gas_darah' => 'Gas Darah',
                        'sitologi' => 'Sitologi',
                        'patologi_anatomi' => 'Patologi Anatomi',
                        'molekuler' => 'Molekuler',
                        'lainnya' => 'Lainnya',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'hematologi' => 'danger',
                        'kimia_darah' => 'primary',
                        'urinalisa' => 'warning',
                        'mikrobiologi' => 'success',
                        'imunologi' => 'info',
                        'serologi' => 'purple',
                        'endokrinologi' => 'pink',
                        'tumor_marker' => 'orange',
                        'elektrolit' => 'cyan',
                        'gula_darah' => 'teal',
                        'fungsi_ginjal' => 'indigo',
                        'fungsi_hati' => 'amber',
                        'lemak_darah' => 'lime',
                        'koagulasi' => 'rose',
                        'gas_darah' => 'sky',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('specimen_type')
                    ->label('Spesimen')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'darah' => 'Darah',
                        'urine' => 'Urine',
                        'feses' => 'Feses',
                        'sputum' => 'Sputum',
                        'lendir' => 'Lendir',
                        'jaringan' => 'Jaringan',
                        'cairan_serebrospinal' => 'Cairan Serebrospinal',
                        'cairan_sendi' => 'Cairan Sendi',
                        'cairan_pleura' => 'Cairan Pleura',
                        'swab' => 'Swab',
                        'lainnya' => 'Lainnya',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color('secondary')
                    ->sortable(),

                TextColumn::make('reference_value')
                    ->label('Nilai Rujukan')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('base_price')
                    ->label('Harga')
                    ->money('IDR')
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
            ->defaultSort('test_code', 'asc')
            ->groups([
                Group::make('category')
                    ->label('Kategori')
                    ->collapsible(),
                Group::make('specimen_type')
                    ->label('Jenis Spesimen')
                    ->collapsible(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'hematologi' => 'Hematologi',
                        'kimia_darah' => 'Kimia Darah',
                        'urinalisa' => 'Urinalisa',
                        'mikrobiologi' => 'Mikrobiologi',
                        'imunologi' => 'Imunologi',
                        'serologi' => 'Serologi',
                        'endokrinologi' => 'Endokrinologi',
                        'tumor_marker' => 'Tumor Marker',
                        'elektrolit' => 'Elektrolit',
                        'gula_darah' => 'Gula Darah',
                        'fungsi_ginjal' => 'Fungsi Ginjal',
                        'fungsi_hati' => 'Fungsi Hati',
                        'lemak_darah' => 'Lemak Darah',
                        'koagulasi' => 'Koagulasi',
                        'gas_darah' => 'Gas Darah',
                        'sitologi' => 'Sitologi',
                        'patologi_anatomi' => 'Patologi Anatomi',
                        'molekuler' => 'Molekuler',
                        'lainnya' => 'Lainnya',
                    ])
                    ->native(false),

                SelectFilter::make('specimen_type')
                    ->label('Jenis Spesimen')
                    ->options([
                        'darah' => 'Darah',
                        'urine' => 'Urine',
                        'feses' => 'Feses',
                        'sputum' => 'Sputum',
                        'lendir' => 'Lendir',
                        'jaringan' => 'Jaringan',
                        'cairan_serebrospinal' => 'Cairan Serebrospinal',
                        'cairan_sendi' => 'Cairan Sendi',
                        'cairan_pleura' => 'Cairan Pleura',
                        'swab' => 'Swab',
                        'lainnya' => 'Lainnya',
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
            ->emptyStateHeading('Belum ada pemeriksaan lab')
            ->emptyStateDescription('Buat pemeriksaan lab pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-beaker');
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
            'index' => ListLabTests::route('/'),
            'create' => CreateLabTest::route('/create'),
            'view' => ViewLabTest::route('/{record}'),
            'edit' => EditLabTest::route('/{record}/edit'),
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
