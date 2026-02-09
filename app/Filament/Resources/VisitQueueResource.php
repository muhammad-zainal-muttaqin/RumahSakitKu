<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\TextSize;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Grouping\Group;
use App\Filament\Resources\VisitQueueResource\Pages\ListVisitQueues;
use App\Filament\Resources\VisitQueueResource\Pages\ManageVisitQueue;
use App\Filament\Resources\VisitQueueResource\Widgets\QueueStats;
use App\Filament\Resources\VisitQueueResource\Widgets\LiveQueueDisplay;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\VisitQueueResource\Pages;
use App\Filament\Resources\VisitQueueResource\Widgets;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\VisitQueue;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VisitQueueResource extends Resource
{
    protected static ?string $model = VisitQueue::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Antrian Poliklinik';

    protected static ?string $modelLabel = 'Antrian';

    protected static ?string $pluralModelLabel = 'Antrian Poliklinik';

    protected static ?int $navigationSort = 12;

    protected static UnitEnum|string|null $navigationGroup = 'Pendaftaran';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Antrian')
                    ->description('Detail antrian kunjungan pasien')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        TextInput::make('display_number')
                            ->label('Nomor Antrian')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'waiting' => 'Menunggu',
                                'called' => 'Dipanggil',
                                'in_progress' => 'Sedang Dilayani',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                'skipped' => 'Dilewati',
                            ])
                            ->native(false)
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->placeholder('Tambahkan catatan jika diperlukan...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_number')
                    ->label('No. Antrian')
                    ->searchable()
                    ->sortable()
                    ->weight('font-bold')
                    ->size(TextSize::Large)
                    ->copyable()
                    ->icon('heroicon-o-ticket'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->description(fn (Model $record): string => $record->patient?->medical_record_number ?? '-'),

                TextColumn::make('polyclinic.name')
                    ->label('Poli')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'Menunggu',
                        'called' => 'Dipanggil',
                        'in_progress' => 'Sedang Dilayani',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'skipped' => 'Dilewati',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'gray',
                        'called' => 'warning',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'skipped' => 'orange',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'waiting' => 'heroicon-o-clock',
                        'called' => 'heroicon-o-speaker-wave',
                        'in_progress' => 'heroicon-o-user',
                        'completed' => 'heroicon-o-check-circle',
                        'cancelled' => 'heroicon-o-x-circle',
                        'skipped' => 'heroicon-o-forward',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                TextColumn::make('called_at')
                    ->label('Waktu Panggil')
                    ->dateTime('H:i:s')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('waiting_time')
                    ->label('Waktu Tunggu')
                    ->suffix(' menit')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('counter_number')
                    ->label('Loket')
                    ->alignCenter()
                    ->badge()
                    ->color('secondary')
                    ->placeholder('-'),
            ])
            ->defaultSort('queue_number', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'waiting' => 'Menunggu',
                        'called' => 'Dipanggil',
                        'in_progress' => 'Sedang Dilayani',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                        'skipped' => 'Dilewati',
                    ])
                    ->native(false)
                    ->multiple(),

                SelectFilter::make('polyclinic_id')
                    ->label('Poliklinik')
                    ->options(fn () => Polyclinic::active()->pluck('name', 'id'))
                    ->searchable()
                    ->native(false),

                SelectFilter::make('date')
                    ->label('Tanggal')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query->whereDate('created_at', today());
                        }

                        return match ($data['value']) {
                            'today' => $query->whereDate('created_at', today()),
                            'yesterday' => $query->whereDate('created_at', today()->subDay()),
                            'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye'),

                Action::make('call')
                    ->label('Panggil')
                    ->icon('heroicon-o-speaker-wave')
                    ->color('warning')
                    ->visible(fn (VisitQueue $record): bool => $record->can_be_called)
                    ->action(function (VisitQueue $record) {
                        $record->markAsCalled('Loket 1');

                        Notification::make()
                            ->title('Antrian dipanggil')
                            ->body("Nomor antrian {$record->display_number} telah dipanggil ke Loket 1")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Panggil Antrian')
                    ->modalDescription(fn (VisitQueue $record): string => "Anda yakin ingin memanggil antrian {$record->display_number}?")
                    ->modalSubmitActionLabel('Ya, Panggil'),

                Action::make('skip')
                    ->label('Lewati')
                    ->icon('heroicon-o-forward')
                    ->color('orange')
                    ->visible(fn (VisitQueue $record): bool => $record->can_be_skipped)
                    ->action(function (VisitQueue $record) {
                        $record->markAsSkipped();

                        Notification::make()
                            ->title('Antrian dilewati')
                            ->body("Nomor antrian {$record->display_number} telah dilewati")
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Lewati Antrian')
                    ->modalDescription(fn (VisitQueue $record): string => "Anda yakin ingin melewati antrian {$record->display_number}?")
                    ->modalSubmitActionLabel('Ya, Lewati'),

                Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (VisitQueue $record): bool => $record->can_be_completed)
                    ->action(function (VisitQueue $record) {
                        $record->markAsCompleted();

                        if ($record->visit) {
                            $record->visit->update([
                                'status' => 'completed',
                                'is_completed' => true,
                                'check_out_at' => now(),
                            ]);
                        }

                        Notification::make()
                            ->title('Antrian selesai')
                            ->body("Nomor antrian {$record->display_number} telah selesai dilayani")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Antrian')
                    ->modalDescription(fn (VisitQueue $record): string => "Anda yakin ingin menyelesaikan antrian {$record->display_number}?")
                    ->modalSubmitActionLabel('Ya, Selesai'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Bulk actions can be added here if needed
                ]),
            ])
            ->emptyStateHeading('Belum ada antrian')
            ->emptyStateDescription('Tidak ada antrian poliklinik untuk ditampilkan.')
            ->emptyStateIcon('heroicon-o-queue-list')
            ->poll('10s')
            ->groups([
                Group::make('polyclinic.name')
                    ->label('Poliklinik')
                    ->collapsible(),
            ])
            ->defaultGroup('polyclinic.name');
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
            'index' => ListVisitQueues::route('/'),
            'manage' => ManageVisitQueue::route('/manage'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'polyclinic', 'visit'])
            ->whereDate('created_at', today());
    }

    public static function getWidgets(): array
    {
        return [
            QueueStats::class,
            LiveQueueDisplay::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::today()->waiting()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
