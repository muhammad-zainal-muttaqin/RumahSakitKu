<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\PaymentResource\Pages\ViewPayment;
use App\Filament\Resources\PaymentResource\Pages\EditPayment;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\MasterData\Employee;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pembayaran (Kasir)';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?int $navigationSort = 41;

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('payment_number')
                            ->label('Nomor Pembayaran')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('PAY-2024-0001')
                            ->prefixIcon('heroicon-m-hashtag')
                            ->default(fn () => 'PAY-' . date('Ymd') . '-' . str_pad((Payment::whereDate('payment_date', today())->count() + 1), 4, '0', STR_PAD_LEFT)),

                        DateTimePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now())
                            ->native(false),

                        TimePicker::make('payment_time')
                            ->label('Waktu')
                            ->default(now())
                            ->seconds(false),
                    ])
                    ->columns(3),

                Section::make('Informasi Tagihan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Select::make('invoice_id')
                            ->label('Tagihan')
                            ->relationship('invoice', 'invoice_number', fn ($query) => $query->with('patient'))
                            ->getOptionLabelFromRecordUsing(fn (Invoice $record) => "{$record->invoice_number} - {$record->patient?->name} (Sisa: Rp " . number_format($record->balance_due, 0, ',', '.') . ")")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->required()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $invoice = Invoice::find($state);
                                    if ($invoice) {
                                        $set('amount', $invoice->balance_due);
                                        $set('_invoice_info', [
                                            'patient_name' => $invoice->patient?->name,
                                            'total_amount' => $invoice->total_amount,
                                            'paid_amount' => $invoice->paid_amount,
                                            'balance_due' => $invoice->balance_due,
                                        ]);
                                    }
                                }
                            }),

                        Placeholder::make('invoice_info')
                            ->label('Detail Tagihan')
                            ->content(function (Get $get): string {
                                $info = $get('_invoice_info');
                                if (!$info) {
                                    return 'Pilih tagihan untuk melihat detail';
                                }
                                return "Pasien: {$info['patient_name']}\nTotal: Rp " . number_format($info['total_amount'], 0, ',', '.') . "\nSudah Dibayar: Rp " . number_format($info['paid_amount'], 0, ',', '.') . "\nSisa: Rp " . number_format($info['balance_due'], 0, ',', '.');
                            })
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => !empty($get('_invoice_info'))),
                    ]),

                Section::make('Detail Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->required()
                            ->options([
                                'cash' => 'Tunai',
                                'credit_card' => 'Kartu Kredit',
                                'debit_card' => 'Kartu Debit',
                                'bank_transfer' => 'Transfer Bank',
                                'mobile_payment' => 'Pembayaran Mobile',
                                'insurance' => 'Asuransi',
                                'bpjs' => 'BPJS',
                            ])
                            ->native(false)
                            ->live(),

                        TextInput::make('amount')
                            ->label('Jumlah Pembayaran')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->hint(fn (Get $get): string => $get('invoice_id') ? 'Maksimal: Rp ' . number_format(Invoice::find($get('invoice_id'))?->balance_due ?? 0, 0, ',', '.') : ''),

                        TextInput::make('reference_number')
                            ->label('Nomor Referensi')
                            ->maxLength(100)
                            ->placeholder('No. Transaksi/Referensi')
                            ->visible(fn (Get $get): bool => in_array($get('payment_method'), ['credit_card', 'debit_card', 'bank_transfer', 'mobile_payment'])),

                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => in_array($get('payment_method'), ['credit_card', 'debit_card', 'bank_transfer'])),

                        TextInput::make('card_number')
                            ->label('Nomor Kartu')
                            ->maxLength(20)
                            ->placeholder('**** **** **** ****')
                            ->visible(fn (Get $get): bool => in_array($get('payment_method'), ['credit_card', 'debit_card'])),

                        TextInput::make('approval_code')
                            ->label('Kode Approval')
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => in_array($get('payment_method'), ['credit_card', 'debit_card'])),

                        Select::make('received_by')
                            ->label('Diterima Oleh')
                            ->relationship('receivedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->default(fn () => Auth::id()),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Informasi Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([
                        Toggle::make('is_refunded')
                            ->label('Sudah Direfund')
                            ->disabled()
                            ->dehydrated(),

                        DateTimePicker::make('refunded_at')
                            ->label('Tanggal Refund')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('refunded_amount')
                            ->label('Jumlah Refund')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),

                        Textarea::make('refund_reason')
                            ->label('Alasan Refund')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (string $operation, ?Model $record): bool => $operation === 'edit' && $record?->is_refunded)
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('invoice.invoice_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Payment $record): string => InvoiceResource::getUrl('view', ['record' => $record->invoice_id]))
                    ->openUrlInNewTab(),

                TextColumn::make('invoice.patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                BadgeColumn::make('payment_method')
                    ->label('Metode')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'mobile_payment' => 'Mobile',
                        'insurance' => 'Asuransi',
                        'bpjs' => 'BPJS',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'credit_card' => 'primary',
                        'debit_card' => 'info',
                        'bank_transfer' => 'warning',
                        'mobile_payment' => 'purple',
                        'insurance' => 'indigo',
                        'bpjs' => 'teal',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'cash' => 'heroicon-m-banknotes',
                        'credit_card' => 'heroicon-m-credit-card',
                        'debit_card' => 'heroicon-m-credit-card',
                        'bank_transfer' => 'heroicon-m-building-library',
                        'mobile_payment' => 'heroicon-m-device-phone-mobile',
                        'insurance' => 'heroicon-m-shield-check',
                        'bpjs' => 'heroicon-m-identification',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('receivedBy.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                IconColumn::make('is_refunded')
                    ->label('Refund')
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-m-arrow-uturn-left')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-m-check')
                    ->falseColor('success'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'mobile_payment' => 'Mobile',
                        'insurance' => 'Asuransi',
                        'bpjs' => 'BPJS',
                    ])
                    ->native(false)
                    ->multiple(),

                Filter::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),

                TernaryFilter::make('is_refunded')
                    ->label('Status Refund')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Refund')
                    ->falseLabel('Belum Refund'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Payment $record): bool => !$record->is_refunded),
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->url(fn (Payment $record): string => route('payments.print', $record))
                    ->openUrlInNewTab(),
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->can_be_refunded)
                    ->requiresConfirmation()
                    ->modalHeading('Refund Pembayaran')
                    ->modalDescription('Apakah Anda yakin ingin melakukan refund pembayaran ini?')
                    ->modalSubmitActionLabel('Ya, Refund')
                    ->schema([
                        TextInput::make('refund_amount')
                            ->label('Jumlah Refund')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Payment $record): float => $record->amount)
                            ->maxValue(fn (Payment $record): float => $record->refundable_amount),
                        Textarea::make('refund_reason')
                            ->label('Alasan Refund')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->refund($data['refund_amount'], $data['refund_reason']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Bulk actions can be added here
                ]),
            ])
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateDescription('Buat pembayaran pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-credit-card');
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['invoice.patient', 'receivedBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('payment_date', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
