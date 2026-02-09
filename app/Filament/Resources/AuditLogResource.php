<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogResource\Pages\ViewAuditLog;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    protected static ?int $navigationSort = 200;

    protected static UnitEnum|string|null $navigationGroup = 'Sistem';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Audit')
                    ->schema([
                        TextInput::make('event')
                            ->label('Aksi')
                            ->disabled(),

                        TextInput::make('user.name')
                            ->label('User')
                            ->disabled()
                            ->placeholder('-'),

                        TextInput::make('model_type_label')
                            ->label('Model Type')
                            ->disabled(),

                        TextInput::make('auditable_id')
                            ->label('Model ID')
                            ->disabled(),

                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),

                        Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->rows(2),

                        TextInput::make('url')
                            ->label('URL')
                            ->disabled()
                            ->columnSpanFull(),

                        TextInput::make('created_at')
                            ->label('Waktu')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Perubahan Data')
                    ->schema([
                        KeyValue::make('old_values')
                            ->label('Nilai Lama')
                            ->disabled()
                            ->keyLabel('Field')
                            ->valueLabel('Nilai'),

                        KeyValue::make('new_values')
                            ->label('Nilai Baru')
                            ->disabled()
                            ->keyLabel('Field')
                            ->valueLabel('Nilai'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System')
                    ->url(fn (AuditLog $record): ?string => $record->user ? UserResource::getUrl('view', ['record' => $record->user]) : null)
                    ->openUrlInNewTab(),

                BadgeColumn::make('event')
                    ->label('Aksi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Create',
                        'updated' => 'Update',
                        'deleted' => 'Delete',
                        'restored' => 'Restore',
                        'force_deleted' => 'Force Delete',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        'force_deleted' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'created' => 'heroicon-o-plus-circle',
                        'updated' => 'heroicon-o-pencil-square',
                        'deleted' => 'heroicon-o-trash',
                        'restored' => 'heroicon-o-arrow-uturn-left',
                        'force_deleted' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                TextColumn::make('model_type_label')
                    ->label('Model Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('auditable_id')
                    ->label('Model ID')
                    ->copyable()
                    ->sortable(),

                TextColumn::make('changes_summary')
                    ->label('Perubahan')
                    ->limit(50)
                    ->tooltip(fn (AuditLog $record): ?string => $record->changes_summary)
                    ->placeholder('-'),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Create',
                        'updated' => 'Update',
                        'deleted' => 'Delete',
                        'restored' => 'Restore',
                        'force_deleted' => 'Force Delete',
                    ])
                    ->native(false),

                SelectFilter::make('user')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('created_at')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('auditable_type')
                    ->label('Model Type')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->pluck('auditable_type')
                        ->filter()
                        ->mapWithKeys(fn ($type): array => [
                            $type => class_basename($type),
                        ])
                        ->toArray()
                    )
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                // No bulk delete allowed for audit logs
            ])
            ->emptyStateHeading('Tidak ada audit log')
            ->emptyStateDescription('Audit log akan muncul ketika ada aktivitas di sistem.')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']);
    }
}
