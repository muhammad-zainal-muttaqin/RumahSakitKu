<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Models\Financial\Payment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Riwayat Pembayaran';

    protected static ?string $recordTitleAttribute = 'payment_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('payment_number')
                    ->label('Nomor Pembayaran')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('PAY-2024-0001'),

                DateTimePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->required()
                    ->default(now())
                    ->native(false),

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
                    ->native(false),

                TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(0.01),

                TextInput::make('reference_number')
                    ->label('Nomor Referensi')
                    ->maxLength(100)
                    ->placeholder('No. Transaksi/Referensi'),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                TextColumn::make('payment_number')
                    ->label('No. Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

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

                TextColumn::make('reference_number')
                    ->label('Referensi')
                    ->searchable()
                    ->placeholder('-'),

                IconColumn::make('is_refunded')
                    ->label('Refund')
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-m-arrow-uturn-left')
                    ->falseIcon('heroicon-m-check'),

                TextColumn::make('refunded_at')
                    ->label('Tanggal Refund')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Metode')
                    ->options([
                        'cash' => 'Tunai',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'mobile_payment' => 'Mobile',
                        'insurance' => 'Asuransi',
                        'bpjs' => 'BPJS',
                    ])
                    ->native(false),

                TernaryFilter::make('is_refunded')
                    ->label('Status Refund')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Refund')
                    ->falseLabel('Belum Refund'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pembayaran')
                    ->url(fn (): string => 
                        str_replace(
                            ['{invoice_id}', '%7Binvoice_id%7D'],
                            [$this->ownerRecord->id, $this->ownerRecord->id],
                            route('filament.admin.resources.payments.create', ['invoice_id' => $this->ownerRecord->id])
                        )
                    )
                    ->visible(fn (): bool => $this->ownerRecord->balance_due > 0),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Payment $record): string => route('filament.admin.resources.payments.view', $record)),
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
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
