<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStats;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\InvoiceResource\Pages;

/**
 * Invoice Resource
 * 
 * Filament resource for managing billing invoices.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Filament\Resources\InvoiceResource\Widgets;
use App\Models\Financial\Invoice;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Tagihan (Billing)';

    protected static ?string $modelLabel = 'Tagihan';

    protected static ?string $pluralModelLabel = 'Tagihan';

    protected static ?int $navigationSort = 40;

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tagihan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Nomor Tagihan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('INV-2024-0001')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Select::make('visit_id')
                            ->label('Kunjungan')
                            ->relationship('visit', 'visit_number', fn ($query) => $query->with('patient'))
                            ->getOptionLabelFromRecordUsing(fn (Visit $record) => "{$record->visit_number} - {$record->patient?->name}")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $visit = Visit::with('patient')->find($state);
                                    if ($visit) {
                                        $set('patient_id', $visit->patient_id);
                                    }
                                }
                            }),

                        Select::make('patient_id')
                            ->label('Pasien')
                            ->relationship('patient', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->medical_record_number} - {$record->name}")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required(),

                        DateTimePicker::make('invoice_date')
                            ->label('Tanggal Tagihan')
                            ->required()
                            ->default(now())
                            ->native(false),

                        DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7))
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Rincian Item')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('items')
                            ->label('Daftar Item')
                            ->schema([
                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Nama tindakan/layanan'),

                                TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $price = $get('unit_price') ?? 0;
                                        $set('total_price', ($state ?? 1) * $price);
                                    }),

                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $qty = $get('quantity') ?? 1;
                                        $set('total_price', $qty * ($state ?? 0));
                                    }),

                                TextInput::make('total_price')
                                    ->label('Total Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Tambah Item')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $subtotal = collect($state)->sum('total_price');
                                $set('subtotal', $subtotal);
                                static::recalculateTotals($get, $set);
                            }),
                    ]),

                Section::make('Perhitungan')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set)),

                        TextInput::make('discount_amount')
                            ->label('Diskon')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set)),

                        TextInput::make('tax_amount')
                            ->label('Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set)),

                        TextInput::make('total_amount')
                            ->label('Total Tagihan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('paid_amount')
                            ->label('Sudah Dibayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('balance_due')
                            ->label('Sisa Tagihan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Section::make('Status & Informasi Tambahan')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Terkirim',
                                'partial' => 'Dibayar Sebagian',
                                'paid' => 'Lunas',
                                'cancelled' => 'Dibatalkan',
                                'refunded' => 'Dikembalikan',
                            ])
                            ->default('draft')
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function recalculateTotals(Get $get, Set $set): void
    {
        $subtotal = $get('subtotal') ?? 0;
        $discount = $get('discount_amount') ?? 0;
        $tax = $get('tax_amount') ?? 0;
        $paid = $get('paid_amount') ?? 0;

        $total = $subtotal - $discount + $tax;
        $balance = $total - $paid;

        $set('total_amount', $total);
        $set('balance_due', $balance);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): string => $record->patient?->medical_record_number ?? '-'),

                TextColumn::make('visit.visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('balance_due')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right')
                    ->color(fn (Invoice $record): string => $record->balance_due > 0 ? 'danger' : 'success'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                        'refunded' => 'Dikembalikan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'purple',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-m-document',
                        'sent' => 'heroicon-m-paper-airplane',
                        'partial' => 'heroicon-m-banknotes',
                        'paid' => 'heroicon-m-check-circle',
                        'cancelled' => 'heroicon-m-x-circle',
                        'refunded' => 'heroicon-m-arrow-uturn-left',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (Invoice $record): ?string => $record->is_overdue ? 'danger' : null)
                    ->icon(fn (Invoice $record): ?string => $record->is_overdue ? 'heroicon-m-exclamation-triangle' : null),

                TextColumn::make('invoice_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'partial' => 'Dibayar Sebagian',
                        'paid' => 'Lunas',
                        'cancelled' => 'Dibatalkan',
                        'refunded' => 'Dikembalikan',
                    ])
                    ->native(false)
                    ->multiple(),

                Filter::make('invoice_date')
                    ->label('Tanggal Tagihan')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    }),

                Filter::make('due_date')
                    ->label('Jatuh Tempo')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),

                Filter::make('overdue')
                    ->label('Jatuh Tempo Terlewat')
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pay')
                    ->label('Bayar')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->balance_due > 0 && !in_array($record->status, ['paid', 'cancelled']))
                    ->url(fn (Invoice $record): string => PaymentResource::getUrl('create', ['invoice_id' => $record->id])),
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->url(fn (Invoice $record): string => route('invoices.print', $record))
                    ->openUrlInNewTab(),
                Action::make('cancel')
                    ->label('Batal')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $record): bool => !in_array($record->status, ['paid', 'cancelled', 'refunded']))
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Tagihan')
                    ->modalDescription('Apakah Anda yakin ingin membatalkan tagihan ini?')
                    ->modalSubmitActionLabel('Ya, Batalkan')
                    ->action(fn (Invoice $record) => $record->update(['status' => 'cancelled'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada tagihan')
            ->emptyStateDescription('Buat tagihan pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            InvoiceStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'visit']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['draft', 'sent', 'partial'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
