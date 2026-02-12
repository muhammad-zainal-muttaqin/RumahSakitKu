<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\RelationManagers;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class PrescriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'prescriptions';

    protected static ?string $title = 'Resep';

    protected static ?string $recordTitleAttribute = 'prescription_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Resep')
                    ->schema([
                        TextInput::make('prescription_number')
                            ->label('No. Resep')
                            ->required()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-m-hashtag')
                            ->placeholder('RES-2024-XXXX'),

                        DatePicker::make('prescription_date')
                            ->label('Tanggal Resep')
                            ->required()
                            ->default(now()),

                        Select::make('prescription_type')
                            ->label('Tipe Resep')
                            ->required()
                            ->options([
                                'regular' => 'Reguler',
                                'compound' => 'Racikan',
                                'external' => 'Luar',
                                'psychotropic' => 'Psikotropika',
                                'narcotic' => 'Narkotika',
                            ])
                            ->default('regular')
                            ->native(false),

                        Select::make('priority')
                            ->label('Prioritas')
                            ->required()
                            ->options([
                                'normal' => 'Normal',
                                'urgent' => 'Segera',
                                'stat' => 'STAT/Immediate',
                            ])
                            ->default('normal')
                            ->native(false),

                        Select::make('prescribed_by')
                            ->label('Dokter Penulis')
                            ->relationship('prescribedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->label('Status Resep')
                            ->required()
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Menunggu',
                                'verified' => 'Terverifikasi',
                                'dispensed' => 'Sudah Ditebus',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('draft')
                            ->native(false),

                        Toggle::make('verified_by_pharmacist')
                            ->label('Terverifikasi Farmasi')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Informasi Klinis')
                    ->schema([
                        Textarea::make('clinical_indication')
                            ->label('Indikasi Klinis')
                            ->placeholder('Indikasi penggunaan obat...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('allergies')
                            ->label('Alergi Obat')
                            ->placeholder('Catatan alergi obat pasien...')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('prescription_number')
            ->columns([
                TextColumn::make('prescription_number')
                    ->label('No. Resep')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('prescription_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('prescription_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'regular' => 'Reguler',
                        'compound' => 'Racikan',
                        'external' => 'Luar',
                        'psychotropic' => 'Psikotropika',
                        'narcotic' => 'Narkotika',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'regular' => 'primary',
                        'compound' => 'info',
                        'external' => 'warning',
                        'psychotropic' => 'purple',
                        'narcotic' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('priority')
                    ->label('Prioritas')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'urgent' => 'Segera',
                        'stat' => 'STAT',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'normal' => 'gray',
                        'urgent' => 'warning',
                        'stat' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending' => 'Menunggu',
                        'verified' => 'Terverifikasi',
                        'dispensed' => 'Sudah Ditebus',
                        'cancelled' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'verified' => 'info',
                        'dispensed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('prescribedBy.name')
                    ->label('Dokter')
                    ->placeholder('-'),

                IconColumn::make('verified_by_pharmacist')
                    ->label('Verifikasi Farmasi')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('total_items')
                    ->label('Jumlah Item')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('prescription_date', 'desc')
            ->filters([
                SelectFilter::make('prescription_type')
                    ->label('Tipe Resep')
                    ->options([
                        'regular' => 'Reguler',
                        'compound' => 'Racikan',
                        'external' => 'Luar',
                        'psychotropic' => 'Psikotropika',
                        'narcotic' => 'Narkotika',
                    ])
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Menunggu',
                        'verified' => 'Terverifikasi',
                        'dispensed' => 'Sudah Ditebus',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->native(false),

                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options([
                        'normal' => 'Normal',
                        'urgent' => 'Segera',
                        'stat' => 'STAT',
                    ])
                    ->native(false),

                TernaryFilter::make('verified_by_pharmacist')
                    ->label('Verifikasi Farmasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Verifikasi'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Resep')
                    ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (?Model $record): bool => !$this->getOwnerRecord()->is_finalized && $record?->status !== 'dispensed'),

                DeleteAction::make()
                    ->visible(fn (?Model $record): bool => !$this->getOwnerRecord()->is_finalized && $record?->status === 'draft'),

                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Model $record): string => route('prescriptions.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (?Model $record): bool => $record?->status !== 'draft'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),
                ]),
            ])
            ->emptyStateHeading('Belum ada resep')
            ->emptyStateDescription('Tambahkan resep pertama untuk rekam medis ini.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
