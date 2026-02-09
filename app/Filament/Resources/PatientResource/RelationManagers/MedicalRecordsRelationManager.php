<?php

declare(strict_types=1);

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Clinical\MedicalRecord;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class MedicalRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'medicalRecords';

    protected static ?string $title = 'Rekam Medis';

    protected static ?string $recordTitleAttribute = 'record_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('record_number')
                    ->label('No. Rekam Medis')
                    ->required()
                    ->maxLength(20),

                DatePicker::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->required()
                    ->native(false),

                Textarea::make('subjective')
                    ->label('Subjective (S)')
                    ->maxLength(65535)
                    ->rows(2)
                    ->placeholder('Keluhan pasien'),

                Textarea::make('objective')
                    ->label('Objective (O)')
                    ->maxLength(65535)
                    ->rows(2)
                    ->placeholder('Hasil pemeriksaan'),

                Textarea::make('assessment')
                    ->label('Assessment (A)')
                    ->maxLength(65535)
                    ->rows(2)
                    ->placeholder('Diagnosis'),

                Textarea::make('plan')
                    ->label('Plan (P)')
                    ->maxLength(65535)
                    ->rows(2)
                    ->placeholder('Rencana tindakan'),

                TextInput::make('diagnosis_primary')
                    ->label('Diagnosis Utama')
                    ->maxLength(255)
                    ->placeholder('Diagnosis utama'),

                TextInput::make('diagnosis_secondary')
                    ->label('Diagnosis Sekunder')
                    ->maxLength(255)
                    ->placeholder('Diagnosis sekunder'),

                TextInput::make('icd10_code')
                    ->label('Kode ICD-10')
                    ->maxLength(10)
                    ->placeholder('A00'),

                TextInput::make('icd10_description')
                    ->label('Deskripsi ICD-10')
                    ->maxLength(255)
                    ->placeholder('Deskripsi kode ICD-10'),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->rows(2),

                Toggle::make('is_finalized')
                    ->label('Finalisasi')
                    ->required()
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('record_number')
            ->columns([
                TextColumn::make('record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('visit.visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('diagnosis_primary')
                    ->label('Diagnosis Utama')
                    ->limit(40)
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('icd10_code')
                    ->label('ICD-10')
                    ->searchable()
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record): string => $record->is_finalized ? 'Final' : 'Draft')
                    ->color(fn ($record): string => $record->is_finalized ? 'success' : 'warning'),

                IconColumn::make('is_finalized')
                    ->label('Final')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('finalized_at')
                    ->label('Tgl Finalisasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_finalized')
                    ->label('Status Finalisasi')
                    ->placeholder('Semua')
                    ->trueLabel('Final')
                    ->falseLabel('Draft'),

                Filter::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->schema([
                        DatePicker::make('visit_date_from')
                            ->label('Dari'),
                        DatePicker::make('visit_date_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['visit_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['visit_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    }),

                SelectFilter::make('icd10_code')
                    ->label('Kode ICD-10')
                    ->options(fn (): array => MedicalRecord::distinct()->pluck('icd10_code', 'icd10_code')->filter()->toArray())
                    ->searchable()
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Rekam Medis'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('visit_date', 'desc');
    }
}
