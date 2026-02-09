<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PrescriptionResource\RelationManagers\PrescriptionItemsRelationManager;
use App\Filament\Resources\PrescriptionResource\Pages\ListPrescriptions;
use App\Filament\Resources\PrescriptionResource\Pages\CreatePrescription;
use App\Filament\Resources\PrescriptionResource\Pages\ViewPrescription;
use App\Filament\Resources\PrescriptionResource\Pages\EditPrescription;
use App\Filament\Resources\PrescriptionResource\Widgets\PrescriptionStats;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\PrescriptionResource\Pages;

/**
 * Prescription Resource
 * 
 * Filament resource for managing patient prescriptions.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\PrescriptionResource\RelationManagers;
use App\Filament\Resources\PrescriptionResource\Widgets;
use App\Models\Clinical\Prescription;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Medicine;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PrescriptionResource extends Resource
{
    protected static ?string $model = Prescription::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'E-Resep';

    protected static ?string $modelLabel = 'Resep';

    protected static ?string $pluralModelLabel = 'E-Resep';

    protected static ?int $navigationSort = 30;

    protected static UnitEnum|string|null $navigationGroup = 'Farmasi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Info Resep')
                    ->schema([
                        TextInput::make('prescription_number')
                            ->label('Nomor Resep')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('RSP-2024-XXXX')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Select::make('medical_record_id')
                            ->label('Rekam Medis')
                            ->relationship('medicalRecord', 'id')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => "#{$record->id} - {$record->patient?->name}")
                            ->required(),

                        Select::make('prescribed_by')
                            ->label('Dokter')
                            ->relationship('prescribedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Employee::doctors()->pluck('name', 'id'))
                            ->required()
                            ->prefixIcon('heroicon-m-user-doctor'),

                        DateTimePicker::make('prescription_date')
                            ->label('Tanggal Resep')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'verified' => 'Terverifikasi',
                                'processed' => 'Diproses',
                                'dispensed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('draft')
                            ->native(false)
                            ->live(),

                        Select::make('prescription_type')
                            ->label('Jenis Resep')
                            ->required()
                            ->options([
                                'non_racik' => 'Non-Racik',
                                'racik' => 'Racik',
                            ])
                            ->default('non_racik')
                            ->native(false),

                        Select::make('priority')
                            ->label('Prioritas')
                            ->required()
                            ->options([
                                'normal' => 'Normal',
                                'urgent' => 'Urgent',
                                'stat' => 'STAT',
                            ])
                            ->default('normal')
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Klinis')
                    ->schema([
                        Textarea::make('clinical_indication')
                            ->label('Indikasi Klinis')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('allergies')
                            ->label('Alergi')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Item Obat')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('medicine_id')
                                            ->label('Obat')
                                            ->relationship('medicine', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->optionsLimit(50)
                                            ->getOptionLabelFromRecordUsing(fn (Medicine $record): string => "{$record->code} - {$record->name} (Stok: {$record->stock})")
                                            ->required()
                                            ->columnSpan(5)
                                            ->hint(fn (Medicine $record): ?string => $record?->is_low_stock ? 'Stok Rendah!' : ($record?->is_out_of_stock ? 'Stok Habis!' : null))
                                            ->hintColor(fn (Medicine $record): ?string => $record?->is_low_stock ? 'warning' : ($record?->is_out_of_stock ? 'danger' : null)),

                                        TextInput::make('quantity')
                                            ->label('Jumlah')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->columnSpan(2),

                                        TextInput::make('frequency')
                                            ->label('Frekuensi')
                                            ->placeholder('3x1')
                                            ->maxLength(50)
                                            ->columnSpan(2),

                                        TextInput::make('duration_days')
                                            ->label('Hari')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->placeholder('7')
                                            ->columnSpan(1),

                                        Select::make('route_of_administration')
                                            ->label('Rute')
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
                                            ->native(false)
                                            ->columnSpan(2),

                                        Textarea::make('dosage_instructions')
                                            ->label('Instruksi Dosis')
                                            ->placeholder('Sesudah makan')
                                            ->rows(1)
                                            ->columnSpan(6),

                                        Textarea::make('instructions')
                                            ->label('Instruksi Pemakaian')
                                            ->placeholder('Aturan pakai lengkap')
                                            ->rows(1)
                                            ->columnSpan(6),

                                        TextInput::make('unit_price')
                                            ->label('Harga Satuan')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->step(0.01)
                                            ->default(0)
                                            ->columnSpan(3)
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
                                            ->default(0)
                                            ->columnSpan(3)
                                            ->disabled(),

                                        Toggle::make('is_substitutable')
                                            ->label('Bisa Disubstitusi')
                                            ->default(false)
                                            ->columnSpan(3),

                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(1)
                                            ->columnSpan(3),
                                    ]),
                            ])
                            ->addActionLabel('Tambah Obat')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['generic_name'] ?? null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Total & Catatan')
                    ->schema([
                        Placeholder::make('total_amount')
                            ->label('Total Estimasi')
                            ->content(fn (?Prescription $record): string => $record ? 'Rp ' . number_format($record->total_estimated_cost, 0, ',', '.') : 'Rp 0'),

                        Textarea::make('notes')
                            ->label('Catatan Resep')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Verifikasi & Dispensing')
                    ->schema([
                        Toggle::make('verified_by_pharmacist')
                            ->label('Terverifikasi Farmasis')
                            ->default(false)
                            ->disabled(fn (Get $get): bool => $get('status') === 'draft'),

                        DateTimePicker::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->disabled()
                            ->visible(fn (Get $get): bool => $get('verified_at') !== null),

                        DateTimePicker::make('dispensed_at')
                            ->label('Waktu Dispensing')
                            ->disabled()
                            ->visible(fn (Get $get): bool => $get('dispensed_at') !== null),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('prescription_number')
                    ->label('No. Resep')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('prescribedBy.name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('total_items')
                    ->label('Jumlah Item')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_estimated_cost')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'verified' => 'Terverifikasi',
                        'processed' => 'Diproses',
                        'dispensed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'verified' => 'warning',
                        'processed' => 'info',
                        'dispensed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-o-pencil',
                        'verified' => 'heroicon-o-check-circle',
                        'processed' => 'heroicon-o-cog-6-tooth',
                        'dispensed' => 'heroicon-o-check-badge',
                        'cancelled' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                BadgeColumn::make('prescription_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'non_racik' => 'Non-Racik',
                        'racik' => 'Racik',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'non_racik' => 'primary',
                        'racik' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('prescription_date')
                    ->label('Tanggal Resep')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                IconColumn::make('verified_by_pharmacist')
                    ->label('Verifikasi')
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('prescription_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'verified' => 'Terverifikasi',
                        'processed' => 'Diproses',
                        'dispensed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false),

                SelectFilter::make('prescription_type')
                    ->label('Jenis Resep')
                    ->options([
                        'non_racik' => 'Non-Racik',
                        'racik' => 'Racik',
                    ])
                    ->native(false),

                Filter::make('prescription_date')
                    ->label('Tanggal Resep')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescription_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('prescription_date', '<=', $date),
                            );
                    }),

                SelectFilter::make('prescribed_by')
                    ->label('Dokter')
                    ->relationship('prescribedBy', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('verify')
                        ->label('Verifikasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi Resep')
                        ->modalDescription('Apakah Anda yakin ingin memverifikasi resep ini?')
                        ->visible(fn (Prescription $record): bool => $record->status === 'draft')
                        ->action(function (Prescription $record): void {
                            $record->update([
                                'status' => 'verified',
                                'verified_by_pharmacist' => true,
                                'verified_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Resep berhasil diverifikasi')
                                ->success()
                                ->send();
                        }),

                    Action::make('process')
                        ->label('Proses')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Proses Resep')
                        ->modalDescription('Apakah Anda yakin ingin memproses resep ini?')
                        ->visible(fn (Prescription $record): bool => $record->status === 'verified')
                        ->action(function (Prescription $record): void {
                            $record->update([
                                'status' => 'processed',
                            ]);
                            Notification::make()
                                ->title('Resep berhasil diproses')
                                ->success()
                                ->send();
                        }),

                    Action::make('dispense')
                        ->label('Dispense')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Selesaikan Resep')
                        ->modalDescription('Apakah Anda yakin ingin menyelesaikan resep ini? Stok obat akan berkurang.')
                        ->visible(fn (Prescription $record): bool => $record->status === 'processed')
                        ->action(function (Prescription $record): void {
                            $record->update([
                                'status' => 'dispensed',
                                'dispensed_at' => now(),
                                'dispensed_by' => auth()->id(),
                            ]);
                            // Update stock for each item
                            foreach ($record->items as $item) {
                                if ($item->medicine) {
                                    $item->medicine->updateStock($item->quantity, 'out');
                                    $item->update([
                                        'is_dispensed' => true,
                                        'dispensed_quantity' => $item->quantity,
                                    ]);
                                }
                            }
                            Notification::make()
                                ->title('Resep berhasil diselesaikan')
                                ->success()
                                ->send();
                        }),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Resep')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan resep ini?')
                        ->visible(fn (Prescription $record): bool => in_array($record->status, ['draft', 'verified', 'processed']))
                        ->action(function (Prescription $record): void {
                            $record->update([
                                'status' => 'cancelled',
                            ]);
                            Notification::make()
                                ->title('Resep berhasil dibatalkan')
                                ->success()
                                ->send();
                        }),

                    // Tables\Actions\Action::make('print')
                    //     ->label('Cetak')
                    //     ->icon('heroicon-o-printer')
                    //     ->color('gray')
                    //     ->url(fn (Prescription $record): string => route('prescriptions.print', $record))
                    //     ->openUrlInNewTab(),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada resep')
            ->emptyStateDescription('Buat resep pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [
            PrescriptionItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrescriptions::route('/'),
            'create' => CreatePrescription::route('/create'),
            'view' => ViewPrescription::route('/{record}'),
            'edit' => EditPrescription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'prescribedBy', 'items']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['draft', 'verified', 'processed'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getWidgets(): array
    {
        return [
            PrescriptionStats::class,
        ];
    }
}
