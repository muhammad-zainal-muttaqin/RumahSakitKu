<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionResource\RelationManagers;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\MasterData\Medicine;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class PrescriptionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Obat';

    protected static ?string $recordTitleAttribute = 'generic_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('medicine_id')
                            ->label('Obat')
                            ->relationship('medicine', 'name')
                            ->searchable()
                            ->preload()
                            ->optionsLimit(50)
                            ->getOptionLabelFromRecordUsing(fn (Medicine $record): string => "{$record->code} - {$record->name} (Stok: {$record->stock})")
                            ->required()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                if ($state) {
                                    $medicine = Medicine::find($state);
                                    if ($medicine) {
                                        $set('generic_name', $medicine->name);
                                        $set('dosage_form', $medicine->dosage_form);
                                        $set('unit', $medicine->unit);
                                        $set('unit_price', $medicine->selling_price);
                                    }
                                }
                            }),

                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $qty = $get('quantity') ?? 0;
                                $price = $get('unit_price') ?? 0;
                                $set('total_price', $qty * $price);
                            }),

                        TextInput::make('generic_name')
                            ->label('Nama Generik')
                            ->maxLength(255)
                            ->disabled(),

                        TextInput::make('brand_name')
                            ->label('Nama Merk')
                            ->maxLength(255)
                            ->placeholder('-'),

                        TextInput::make('dosage_form')
                            ->label('Bentuk Sediaan')
                            ->maxLength(100)
                            ->disabled(),

                        TextInput::make('strength')
                            ->label('Kekuatan')
                            ->maxLength(100)
                            ->placeholder('500mg'),

                        TextInput::make('unit')
                            ->label('Satuan')
                            ->maxLength(50)
                            ->disabled(),

                        TextInput::make('frequency')
                            ->label('Frekuensi')
                            ->placeholder('3x1')
                            ->maxLength(50),

                        TextInput::make('duration_days')
                            ->label('Durasi (Hari)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(7),

                        Select::make('route_of_administration')
                            ->label('Rute Pemberian')
                            ->options([
                                'oral' => 'Oral',
                                'injeksi' => 'Injeksi',
                                'topical' => 'Topical',
                                'inhalasi' => 'Inhalasi',
                                'suppositoria' => 'Supositoria',
                                'sublingual' => 'Sublingual',
                                'intravena' => 'Intravena',
                                'intramuskular' => 'Intramuskular',
                                'subkutan' => 'Subkutan',
                            ])
                            ->native(false),

                        TextInput::make('unit_price')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                $qty = $get('quantity') ?? 0;
                                $price = $get('unit_price') ?? 0;
                                $set('total_price', $qty * $price);
                            }),

                        TextInput::make('total_price')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->disabled(),
                    ]),

                Textarea::make('dosage_instructions')
                    ->label('Instruksi Dosis')
                    ->placeholder('Contoh: 1 tablet setelah makan')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('instructions')
                    ->label('Instruksi Lengkap')
                    ->placeholder('Instruksi penggunaan obat lengkap')
                    ->rows(2)
                    ->columnSpanFull(),

                Toggle::make('is_substitutable')
                    ->label('Boleh Disubstitusi')
                    ->default(false),

                Textarea::make('substitution_notes')
                    ->label('Catatan Substitusi')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('generic_name')
            ->columns([
                TextColumn::make('medicine.name')
                    ->label('Obat')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('generic_name')
                    ->label('Nama Generik')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('strength')
                    ->label('Kekuatan')
                    ->placeholder('-'),

                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter(),

                TextColumn::make('frequency')
                    ->label('Frekuensi')
                    ->alignCenter()
                    ->placeholder('-'),

                TextColumn::make('duration_days')
                    ->label('Hari')
                    ->alignCenter()
                    ->numeric(),

                BadgeColumn::make('route_of_administration')
                    ->label('Rute')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'oral' => 'Oral',
                        'injeksi' => 'Injeksi',
                        'topical' => 'Topical',
                        'inhalasi' => 'Inhalasi',
                        'suppositoria' => 'Supositoria',
                        'sublingual' => 'Sublingual',
                        'intravena' => 'IV',
                        'intramuskular' => 'IM',
                        'subkutan' => 'SC',
                        default => $state ?? '-',
                    })
                    ->color('gray'),

                TextColumn::make('formatted_dosage')
                    ->label('Instruksi')
                    ->placeholder('-'),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                IconColumn::make('is_substitutable')
                    ->label('Substitusi')
                    ->alignCenter()
                    ->boolean(),

                IconColumn::make('is_dispensed')
                    ->label('Dispensed')
                    ->alignCenter()
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_substitutable')
                    ->label('Boleh Disubstitusi')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),

                TernaryFilter::make('is_dispensed')
                    ->label('Status Dispensing')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Obat'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada item obat')
            ->emptyStateDescription('Tambahkan item obat untuk resep ini.')
            ->emptyStateIcon('heroicon-o-beaker');
    }

    public function isReadOnly(): bool
    {
        $prescription = $this->getOwnerRecord();
        return in_array($prescription->status, ['dispensed', 'cancelled']);
    }
}
