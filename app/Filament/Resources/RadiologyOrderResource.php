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
use Filament\Schemas\Components\Utilities\Get;
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
use App\Filament\Resources\RadiologyOrderResource\RelationManagers\RadiologyResultsRelationManager;
use App\Filament\Resources\RadiologyOrderResource\Pages\ListRadiologyOrders;
use App\Filament\Resources\RadiologyOrderResource\Pages\CreateRadiologyOrder;
use App\Filament\Resources\RadiologyOrderResource\Pages\ViewRadiologyOrder;
use App\Filament\Resources\RadiologyOrderResource\Pages\EditRadiologyOrder;
use App\Filament\Resources\RadiologyOrderResource\Widgets\RadiologyStats;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\RadiologyOrderResource\Pages;

/**
 * Radiology Order Resource
 * 
 * Filament resource for managing radiology examination orders.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\RadiologyOrderResource\RelationManagers;
use App\Filament\Resources\RadiologyOrderResource\Widgets;
use App\Models\Clinical\RadiologyOrder;
use App\Models\MasterData\Employee;
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

class RadiologyOrderResource extends Resource
{
    protected static ?string $model = RadiologyOrder::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationLabel = 'Radiologi';

    protected static ?string $modelLabel = 'Order Radiologi';

    protected static ?string $pluralModelLabel = 'Order Radiologi';

    protected static ?int $navigationSort = 71;

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
                            ->prefixIcon('heroicon-m-user-doctor'),

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
                                'emergency' => 'Emergency',
                            ])
                            ->default('normal')
                            ->native(false),
                    ])
                    ->columns(2),

                Section::make('Pemeriksaan Radiologi')
                    ->schema([
                        Select::make('examination_type')
                            ->label('Jenis Pemeriksaan')
                            ->required()
                            ->options([
                                'xray' => 'Rontgen (X-Ray)',
                                'ct_scan' => 'CT Scan',
                                'mri' => 'MRI',
                                'usg' => 'USG',
                                'mammografi' => 'Mammografi',
                                'fluoroskopi' => 'Fluoroskopi',
                                'angiografi' => 'Angiografi',
                                'dexa' => 'DEXA (Bone Densitometry)',
                                'pet_scan' => 'PET Scan',
                                'nuklir' => 'Pencitraan Nuklir',
                            ])
                            ->native(false)
                            ->searchable()
                            ->prefixIcon('heroicon-m-rectangle-stack'),

                        TextInput::make('body_area')
                            ->label('Area Tubuh')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Thorax, Abdomen, Kepala')
                            ->prefixIcon('heroicon-m-user'),

                        TextInput::make('position')
                            ->label('Posisi')
                            ->maxLength(100)
                            ->placeholder('Contoh: AP, Lateral, Oblique')
                            ->prefixIcon('heroicon-m-arrows-pointing-out'),

                        Toggle::make('contrast')
                            ->label('Menggunakan Kontras')
                            ->default(false)
                            ->live(),

                        TextInput::make('contrast_type')
                            ->label('Jenis Kontras')
                            ->maxLength(100)
                            ->placeholder('Contoh: Iodin, Gadolinium, Barium')
                            ->visible(fn (Get $get): bool => $get('contrast') === true),

                        Textarea::make('clinical_indication')
                            ->label('Indikasi Klinis')
                            ->placeholder('Indikasi klinis untuk pemeriksaan ini')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Jadwal')
                    ->schema([
                        DateTimePicker::make('scheduled_date')
                            ->label('Jadwal Pemeriksaan')
                            ->native(false)
                            ->placeholder('Pilih jadwal pemeriksaan'),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'pending' => 'Menunggu',
                                'scheduled' => 'Terjadwal',
                                'in_progress' => 'Sedang Dikerjakan',
                                'completed' => 'Selesai',
                                'reported' => 'Sudah Dibaca',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('pending')
                            ->native(false)
                            ->live(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan untuk pemeriksaan ini')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
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

                BadgeColumn::make('examination_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'xray' => 'Rontgen',
                        'ct_scan' => 'CT Scan',
                        'mri' => 'MRI',
                        'usg' => 'USG',
                        'mammografi' => 'Mammografi',
                        'fluoroskopi' => 'Fluoroskopi',
                        'angiografi' => 'Angiografi',
                        'dexa' => 'DEXA',
                        'pet_scan' => 'PET Scan',
                        'nuklir' => 'Nuklir',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'xray' => 'gray',
                        'ct_scan' => 'primary',
                        'mri' => 'info',
                        'usg' => 'success',
                        'mammografi' => 'warning',
                        'fluoroskopi' => 'purple',
                        'angiografi' => 'danger',
                        'dexa' => 'teal',
                        'pet_scan' => 'pink',
                        'nuklir' => 'orange',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('body_area')
                    ->label('Area')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('position')
                    ->label('Posisi')
                    ->placeholder('-'),

                IconColumn::make('contrast')
                    ->label('Kontras')
                    ->alignCenter()
                    ->boolean(),

                BadgeColumn::make('priority')
                    ->label('Prioritas')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'urgent' => 'Urgent',
                        'emergency' => 'Emergency',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'gray',
                        'urgent' => 'warning',
                        'emergency' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'normal' => 'heroicon-m-minus',
                        'urgent' => 'heroicon-m-exclamation-triangle',
                        'emergency' => 'heroicon-m-bolt',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'scheduled' => 'Terjadwal',
                        'in_progress' => 'Sedang Dikerjakan',
                        'completed' => 'Selesai',
                        'reported' => 'Sudah Dibaca',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'scheduled' => 'info',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'reported' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'scheduled' => 'heroicon-m-calendar',
                        'in_progress' => 'heroicon-m-play',
                        'completed' => 'heroicon-m-check-circle',
                        'reported' => 'heroicon-m-document-check',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('scheduled_date')
                    ->label('Jadwal')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'scheduled' => 'Terjadwal',
                        'in_progress' => 'Sedang Dikerjakan',
                        'completed' => 'Selesai',
                        'reported' => 'Sudah Dibaca',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('examination_type')
                    ->label('Jenis Pemeriksaan')
                    ->options([
                        'xray' => 'Rontgen',
                        'ct_scan' => 'CT Scan',
                        'mri' => 'MRI',
                        'usg' => 'USG',
                        'mammografi' => 'Mammografi',
                        'fluoroskopi' => 'Fluoroskopi',
                        'angiografi' => 'Angiografi',
                        'dexa' => 'DEXA',
                        'pet_scan' => 'PET Scan',
                        'nuklir' => 'Nuklir',
                    ])
                    ->native(false)
                    ->multiple(),

                Filter::make('scheduled_date')
                    ->label('Jadwal')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                            );
                    }),

                TernaryFilter::make('contrast')
                    ->label('Menggunakan Kontras')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('schedule')
                        ->label('Jadwalkan')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->schema([
                            DateTimePicker::make('scheduled_date')
                                ->label('Jadwal Pemeriksaan')
                                ->required()
                                ->native(false),
                        ])
                        ->requiresConfirmation()
                        ->visible(fn (RadiologyOrder $record): bool => $record->canBeScheduled())
                        ->action(function (RadiologyOrder $record, array $data): void {
                            $record->update([
                                'status' => 'scheduled',
                                'scheduled_date' => $data['scheduled_date'],
                            ]);
                            Notification::make()
                                ->title('Pemeriksaan berhasil dijadwalkan')
                                ->success()
                                ->send();
                        }),

                    Action::make('start')
                        ->label('Mulai Pemeriksaan')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (RadiologyOrder $record): bool => $record->canBeStarted())
                        ->action(function (RadiologyOrder $record): void {
                            $record->update(['status' => 'in_progress']);
                            Notification::make()
                                ->title('Pemeriksaan dimulai')
                                ->success()
                                ->send();
                        }),

                    Action::make('complete')
                        ->label('Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (RadiologyOrder $record): bool => $record->status === 'in_progress')
                        ->action(function (RadiologyOrder $record): void {
                            $record->update(['status' => 'completed']);
                            Notification::make()
                                ->title('Pemeriksaan selesai')
                                ->success()
                                ->send();
                        }),

                    Action::make('enterResults')
                        ->label('Entry Hasil')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn (RadiologyOrder $record): string => static::getUrl('edit', ['record' => $record]) . '?activeRelationManager=0')
                        ->visible(fn (RadiologyOrder $record): bool => $record->canEnterResults()),

                    Action::make('print')
                        ->label('Cetak Hasil')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (RadiologyOrder $record): string => '#')
                        ->visible(fn (RadiologyOrder $record): bool => in_array($record->status, ['completed', 'reported'])),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Order')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan order ini?')
                        ->visible(fn (RadiologyOrder $record): bool => $record->canBeCancelled())
                        ->action(function (RadiologyOrder $record): void {
                            $record->update(['status' => 'cancelled']);
                            Notification::make()
                                ->title('Order radiologi dibatalkan')
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
            ->emptyStateHeading('Belum ada order radiologi')
            ->emptyStateDescription('Buat order radiologi pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-x-circle');
    }

    public static function getRelations(): array
    {
        return [
            RadiologyResultsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRadiologyOrders::route('/'),
            'create' => CreateRadiologyOrder::route('/create'),
            'view' => ViewRadiologyOrder::route('/{record}'),
            'edit' => EditRadiologyOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'doctor', 'result']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['pending', 'scheduled', 'in_progress'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getWidgets(): array
    {
        return [
            RadiologyStats::class,
        ];
    }

    /**
     * Generate a unique order number.
     * Format: RAD-YYYYMMDD-XXXX
     */
    public static function generateOrderNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "RAD-{$date}-";

        $lastOrder = RadiologyOrder::where('order_number', 'like', "{$prefix}%")
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
