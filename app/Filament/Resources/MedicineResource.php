<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\MedicineResource\Pages\ListMedicines;
use App\Filament\Resources\MedicineResource\Pages\CreateMedicine;
use App\Filament\Resources\MedicineResource\Pages\ViewMedicine;
use App\Filament\Resources\MedicineResource\Pages\EditMedicine;
use BackedEnum;
use UnitEnum;

use App\Models\MasterData\Medicine;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Obat';

    protected static ?string $modelLabel = 'Obat';

    protected static ?string $pluralModelLabel = 'Obat';

    protected static ?int $navigationSort = 5;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Obat')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Obat')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('OBT001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        TextInput::make('name')
                            ->label('Nama Obat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Paracetamol 500mg'),

                        Select::make('classification')
                            ->label('Klasifikasi')
                            ->required()
                            ->options([
                                'obat_bebas' => 'Obat Bebas',
                                'obat_bebas_terbatas' => 'Obat Bebas Terbatas',
                                'obat_keras' => 'Obat Keras',
                                'narkotika' => 'Narkotika',
                                'psikotropik' => 'Psikotropik',
                            ])
                            ->native(false),

                        Select::make('dosage_form')
                            ->label('Bentuk Sediaan')
                            ->required()
                            ->options([
                                'tablet' => 'Tablet',
                                'kapsul' => 'Kapsul',
                                'sirup' => 'Sirup',
                                'injeksi' => 'Injeksi',
                                'salep' => 'Salep',
                                'krim' => 'Krim',
                                'gel' => 'Gel',
                                'tetes' => 'Tetes',
                                'inhaler' => 'Inhaler',
                                'supositoria' => 'Supositoria',
                                'suspensi' => 'Suspensi',
                                'eliksir' => 'Eliksir',
                                'serbuk' => 'Serbuk',
                                'patch' => 'Patch',
                            ])
                            ->native(false),

                        TextInput::make('unit')
                            ->label('Satuan')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('tablet, botol, ampul'),

                        TextInput::make('manufacturer')
                            ->label('Pabrik/Manufaktur')
                            ->maxLength(150)
                            ->placeholder('PT. Kimia Farma'),

                        TextInput::make('registration_number')
                            ->label('Nomor Registrasi BPOM')
                            ->maxLength(50)
                            ->placeholder('BPOM-12345'),

                        Toggle::make('is_generic')
                            ->label('Obat Generik')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Stok')
                    ->schema([
                        TextInput::make('stock')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),

                        TextInput::make('min_stock')
                            ->label('Stok Minimum')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Section::make('Harga')
                    ->schema([
                        TextInput::make('purchase_price')
                            ->label('Harga Beli')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),

                        TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Section::make('Kadaluarsa')
                    ->schema([
                        DatePicker::make('expired_date')
                            ->label('Tanggal Kadaluarsa')
                            ->native(false),
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

                BadgeColumn::make('classification')
                    ->label('Klasifikasi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'obat_bebas' => 'Obat Bebas',
                        'obat_bebas_terbatas' => 'Obat Bebas Terbatas',
                        'obat_keras' => 'Obat Keras',
                        'narkotika' => 'Narkotika',
                        'psikotropik' => 'Psikotropik',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'obat_bebas' => 'success',
                        'obat_bebas_terbatas' => 'info',
                        'obat_keras' => 'warning',
                        'narkotika' => 'danger',
                        'psikotropik' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('dosage_form')
                    ->label('Bentuk')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tablet' => 'Tablet',
                        'kapsul' => 'Kapsul',
                        'sirup' => 'Sirup',
                        'injeksi' => 'Injeksi',
                        'salep' => 'Salep',
                        'krim' => 'Krim',
                        'gel' => 'Gel',
                        'tetes' => 'Tetes',
                        'inhaler' => 'Inhaler',
                        'supositoria' => 'Supositoria',
                        'suspensi' => 'Suspensi',
                        'eliksir' => 'Eliksir',
                        'serbuk' => 'Serbuk',
                        'patch' => 'Patch',
                        default => ucfirst($state),
                    })
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn (Model $record): string => $record->is_out_of_stock ? 'danger' : ($record->is_low_stock ? 'warning' : 'success'))
                    ->sortable(),

                TextColumn::make('min_stock')
                    ->label('Min Stok')
                    ->alignCenter()
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('expired_date')
                    ->label('Kadaluarsa')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn (?Model $record): string => $record?->is_expired ? 'danger' : ($record?->is_expiring_soon ? 'warning' : 'success'))
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
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('classification')
                    ->label('Klasifikasi')
                    ->options([
                        'obat_bebas' => 'Obat Bebas',
                        'obat_bebas_terbatas' => 'Obat Bebas Terbatas',
                        'obat_keras' => 'Obat Keras',
                        'narkotika' => 'Narkotika',
                        'psikotropik' => 'Psikotropik',
                    ])
                    ->native(false),

                SelectFilter::make('dosage_form')
                    ->label('Bentuk Sediaan')
                    ->options([
                        'tablet' => 'Tablet',
                        'kapsul' => 'Kapsul',
                        'sirup' => 'Sirup',
                        'injeksi' => 'Injeksi',
                        'salep' => 'Salep',
                        'krim' => 'Krim',
                        'gel' => 'Gel',
                        'tetes' => 'Tetes',
                        'inhaler' => 'Inhaler',
                        'supositoria' => 'Supositoria',
                        'suspensi' => 'Suspensi',
                        'eliksir' => 'Eliksir',
                        'serbuk' => 'Serbuk',
                        'patch' => 'Patch',
                    ])
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),

                Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 0))
                    ->toggle(),

                Filter::make('expiring_soon')
                    ->label('Segera Kadaluarsa')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('expired_date', '>=', now())
                        ->where('expired_date', '<=', now()->addDays(30)))
                    ->toggle(),

                Filter::make('expired')
                    ->label('Sudah Kadaluarsa')
                    ->query(fn (Builder $query): Builder => $query->where('expired_date', '<', now()))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('adjustStock')
                        ->label('Penyesuaian Stok')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->color('warning')
                        ->schema([
                            Select::make('type')
                                ->label('Tipe')
                                ->required()
                                ->options([
                                    'in' => 'Tambah Stok',
                                    'out' => 'Kurangi Stok',
                                ])
                                ->native(false),

                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->required()
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01),

                            Textarea::make('reason')
                                ->label('Alasan')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function (Model $record, array $data): void {
                            $record->updateStock($data['quantity'], $data['type']);
                            Notification::make()
                                ->title('Stok berhasil disesuaikan')
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
            ->emptyStateHeading('Belum ada obat')
            ->emptyStateDescription('Buat obat pertama Anda untuk memulai.')
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
            'index' => ListMedicines::route('/'),
            'create' => CreateMedicine::route('/create'),
            'view' => ViewMedicine::route('/{record}'),
            'edit' => EditMedicine::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}

