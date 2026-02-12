<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\LaboratoryOrderResource\RelationManagers\LabResultsRelationManager;
use App\Filament\Resources\LaboratoryOrderResource\Pages\ListLaboratoryOrders;
use App\Filament\Resources\LaboratoryOrderResource\Pages\CreateLaboratoryOrder;
use App\Filament\Resources\LaboratoryOrderResource\Pages\ViewLaboratoryOrder;
use App\Filament\Resources\LaboratoryOrderResource\Pages\EditLaboratoryOrder;
use App\Filament\Resources\LaboratoryOrderResource\Widgets\LabOrderStats;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\LaboratoryOrderResource\Pages;

/**
 * Laboratory Order Resource
 * 
 * Filament resource for managing laboratory test orders.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\LaboratoryOrderResource\RelationManagers;
use App\Filament\Resources\LaboratoryOrderResource\Widgets;
use App\Models\Clinical\LaboratoryOrder;
use App\Models\MasterData\Employee;
use App\Models\MasterData\LabTest;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LaboratoryOrderResource extends Resource
{
    protected static ?string $model = LaboratoryOrder::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Laboratorium';

    protected static ?string $modelLabel = 'Order Lab';

    protected static ?string $pluralModelLabel = 'Order Laboratorium';

    protected static ?int $navigationSort = 70;

    protected static UnitEnum|string|null $navigationGroup = 'Penunjang Medis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Order')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Nomor Order')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Otomatis terisi')
                            ->prefixIcon('heroicon-m-hashtag'),

                        Select::make('visit_id')
                            ->label('Kunjungan')
                            ->relationship('visit', 'visit_number')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => "{$record->visit_number} - {$record->patient?->name}")
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state) {
                                    $visit = Visit::find($state);
                                    if ($visit) {
                                        $set('patient_id', $visit->patient_id);
                                        $set('doctor_id', $visit->doctor_id);
                                    }
                                }
                            }),

                        Select::make('patient_id')
                            ->label('Pasien')
                            ->relationship('patient', 'name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => "{$record->medical_record_number} - {$record->name}")
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Select::make('doctor_id')
                            ->label('Dokter Pengirim')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Employee::doctors()->pluck('name', 'id'))
                            ->required()
                            ->prefixIcon('heroicon-m-user-circle'),

                        DateTimePicker::make('order_date')
                            ->label('Tanggal Order')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Select::make('priority')
                            ->label('Prioritas')
                            ->required()
                            ->options([
                                'normal' => 'Normal',
                                'urgent' => 'Urgent',
                                'cito' => 'CITO',
                            ])
                            ->default('normal')
                            ->native(false),

                        Toggle::make('is_cito')
                            ->label('CITO (Darurat)')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?bool $state): void {
                                if ($state) {
                                    $set('priority', 'cito');
                                }
                            }),
                    ])
                    ->columns(2),

                Section::make('Pemeriksaan Laboratorium')
                    ->schema([
                        Repeater::make('results')
                            ->label('')
                            ->relationship('results')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('lab_test_id')
                                            ->label('Pemeriksaan')
                                            ->relationship('labTest', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->optionsLimit(50)
                                            ->getOptionLabelFromRecordUsing(fn (LabTest $record): string => "{$record->test_code} - {$record->name} ({$record->category_label})")
                                            ->required()
                                            ->columnSpan(6)
                                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                if ($state) {
                                                    $labTest = LabTest::find($state);
                                                    if ($labTest) {
                                                        $set('reference_range', $labTest->reference_value);
                                                        $set('unit', $labTest->unit);
                                                    }
                                                }
                                            }),

                                        TextInput::make('unit')
                                            ->label('Satuan')
                                            ->maxLength(50)
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('reference_range')
                                            ->label('Nilai Rujukan')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(4),

                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(2)
                                            ->columnSpan(12),
                                    ]),
                            ])
                            ->addActionLabel('Tambah Pemeriksaan')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Klinis')
                    ->schema([
                        Textarea::make('diagnosis_notes')
                            ->label('Diagnosa')
                            ->placeholder('Diagnosa klinis')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('clinical_notes')
                            ->label('Catatan Klinis')
                            ->placeholder('Catatan klinis tambahan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->label('Status Order')
                            ->required()
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'Diproses',
                                'completed' => 'Selesai',
                                'validated' => 'Divalidasi',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->native(false)
                            ->live(),
                    ])
                    ->collapsible()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Order')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->description(fn (Model $record): string => $record->patient?->medical_record_number ?? '-'),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('total_tests')
                    ->label('Jumlah Pemeriksaan')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('priority')
                    ->label('Prioritas')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'urgent' => 'Urgent',
                        'cito' => 'CITO',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'gray',
                        'urgent' => 'warning',
                        'cito' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'normal' => 'heroicon-m-minus',
                        'urgent' => 'heroicon-m-exclamation-triangle',
                        'cito' => 'heroicon-m-bolt',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'in_progress' => 'Diproses',
                        'completed' => 'Selesai',
                        'validated' => 'Divalidasi',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'validated' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'in_progress' => 'heroicon-m-cog-6-tooth',
                        'completed' => 'heroicon-m-check-circle',
                        'validated' => 'heroicon-m-check-badge',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('order_date')
                    ->label('Tanggal Order')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                IconColumn::make('is_cito')
                    ->label('CITO')
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'Diproses',
                        'completed' => 'Selesai',
                        'validated' => 'Divalidasi',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options([
                        'normal' => 'Normal',
                        'urgent' => 'Urgent',
                        'cito' => 'CITO',
                    ])
                    ->native(false)
                    ->multiple(),

                Filter::make('order_date')
                    ->label('Tanggal Order')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('order_date', '<=', $date),
                            );
                    }),

                TernaryFilter::make('is_cito')
                    ->label('CITO')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('process')
                        ->label('Proses')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Proses Order')
                        ->modalDescription('Apakah Anda yakin ingin memproses order laboratorium ini?')
                        ->visible(fn (LaboratoryOrder $record): bool => $record->canBeProcessed())
                        ->action(function (LaboratoryOrder $record): void {
                            $record->update(['status' => 'in_progress']);
                            Notification::make()
                                ->title('Order laboratorium sedang diproses')
                                ->success()
                                ->send();
                        }),

                    Action::make('enterResults')
                        ->label('Entry Hasil')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (LaboratoryOrder $record): string => static::getUrl('edit', ['record' => $record]) . '?activeRelationManager=0')
                        ->visible(fn (LaboratoryOrder $record): bool => $record->canEnterResults()),

                    Action::make('validate')
                        ->label('Validasi')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Validasi Order')
                        ->modalDescription('Apakah Anda yakin ingin memvalidasi semua hasil pemeriksaan ini?')
                        ->visible(fn (LaboratoryOrder $record): bool => $record->canBeValidated())
                        ->action(function (LaboratoryOrder $record): void {
                            $record->update(['status' => 'validated']);
                            foreach ($record->results as $result) {
                                $result->update([
                                    'validated_by' => auth()->id(),
                                    'validated_at' => now(),
                                ]);
                            }
                            Notification::make()
                                ->title('Order laboratorium telah divalidasi')
                                ->success()
                                ->send();
                        }),

                    Action::make('print')
                        ->label('Cetak Hasil')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (LaboratoryOrder $record): string => '#')
                        ->visible(fn (LaboratoryOrder $record): bool => in_array($record->status, ['completed', 'validated'])),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Order')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan order ini?')
                        ->visible(fn (LaboratoryOrder $record): bool => $record->canBeCancelled())
                        ->action(function (LaboratoryOrder $record): void {
                            $record->update(['status' => 'cancelled']);
                            Notification::make()
                                ->title('Order laboratorium dibatalkan')
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
            ->emptyStateHeading('Belum ada order laboratorium')
            ->emptyStateDescription('Buat order laboratorium pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-beaker');
    }

    public static function getRelations(): array
    {
        return [
            LabResultsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaboratoryOrders::route('/'),
            'create' => CreateLaboratoryOrder::route('/create'),
            'view' => ViewLaboratoryOrder::route('/{record}'),
            'edit' => EditLaboratoryOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'doctor', 'results']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['pending', 'in_progress'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getWidgets(): array
    {
        return [
            LabOrderStats::class,
        ];
    }

    /**
     * Generate a unique order number.
     * Format: LAB-YYYYMMDD-XXXX
     */
    public static function generateOrderNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "LAB-{$date}-";

        $lastOrder = LaboratoryOrder::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}


