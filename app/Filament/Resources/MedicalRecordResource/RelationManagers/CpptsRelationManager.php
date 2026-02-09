<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\RelationManagers;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Clinical\Cppt;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class CpptsRelationManager extends RelationManager
{
    protected static string $relationship = 'cppts';

    protected static ?string $title = 'CPPT';

    protected static ?string $recordTitleAttribute = 'cppt_date';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi CPPT')
                    ->schema([
                        DatePicker::make('cppt_date')
                            ->label('Tanggal CPPT')
                            ->required()
                            ->default(now()),

                        TimePicker::make('cppt_time')
                            ->label('Waktu CPPT')
                            ->default(now()),
                    ])
                    ->columns(2),

                Section::make('SOAP')
                    ->schema([
                        Textarea::make('subjective')
                            ->label('Subjective (S)')
                            ->placeholder('Keluhan pasien...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('objective')
                            ->label('Objective (O)')
                            ->placeholder('Hasil pemeriksaan...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('assessment')
                            ->label('Assessment (A)')
                            ->placeholder('Penilaian/diagnosa...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('plan')
                            ->label('Plan (P)')
                            ->placeholder('Rencana tindakan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Instruksi & Progress')
                    ->schema([
                        Textarea::make('instruction')
                            ->label('Instruksi')
                            ->placeholder('Instruksi untuk pasien...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('progress_notes')
                            ->label('Catatan Perkembangan')
                            ->placeholder('Catatan perkembangan pasien...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Verifikasi')
                    ->schema([
                        Toggle::make('is_verified')
                            ->label('Sudah Diverifikasi')
                            ->default(false)
                            ->live(),

                        DateTimePicker::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->hidden(fn (Get $get): bool => !$get('is_verified'))
                            ->default(now()),

                        Select::make('verified_by')
                            ->label('Diverifikasi Oleh')
                            ->relationship('verifiedBy', 'name')
                            ->hidden(fn (Get $get): bool => !$get('is_verified'))
                            ->native(false),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cppt_date')
            ->columns([
                TextColumn::make('cppt_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('cppt_time')
                    ->label('Waktu')
                    ->time('H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subjective')
                    ->label('Subjective')
                    ->limit(30)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                TextColumn::make('objective')
                    ->label('Objective')
                    ->limit(30)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                TextColumn::make('assessment')
                    ->label('Assessment')
                    ->limit(30)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                TextColumn::make('plan')
                    ->label('Plan')
                    ->limit(30)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                IconColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('verifiedBy.name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('cppt_date', 'desc')
            ->filters([
                TernaryFilter::make('is_verified')
                    ->label('Status Verifikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Verifikasi'),

                Filter::make('cppt_date')
                    ->label('Tanggal CPPT')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('cppt_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('cppt_date', '<=', $data['to']));
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah CPPT')
                    ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),

                DeleteAction::make()
                    ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),

                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => !$record->is_verified && !$this->getOwnerRecord()->is_finalized)
                    ->action(function (Model $record): void {
                        $record->update([
                            'is_verified' => true,
                            'verified_at' => now(),
                            'verified_by' => auth()->id(),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),
                ]),
            ])
            ->emptyStateHeading('Belum ada CPPT')
            ->emptyStateDescription('Tambahkan CPPT pertama untuk rekam medis ini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
