<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\RelationManagers;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
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
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class AssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assessments';

    protected static ?string $title = 'Penilaian/Pemeriksaan';

    protected static ?string $recordTitleAttribute = 'assessment_date';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Penilaian')
                    ->schema([
                        Select::make('assessment_type')
                            ->label('Tipe Penilaian')
                            ->required()
                            ->options([
                                'initial' => 'Pemeriksaan Awal',
                                'follow_up' => 'Follow-up',
                                'pre_op' => 'Pre-Operasi',
                                'post_op' => 'Post-Operasi',
                                'discharge' => 'Pemeriksaan Pulang',
                                'emergency' => 'Gawat Darurat',
                                'specialist' => 'Konsul Spesialis',
                            ])
                            ->native(false),

                        DatePicker::make('assessment_date')
                            ->label('Tanggal Penilaian')
                            ->required()
                            ->default(now()),

                        Select::make('assessed_by')
                            ->label('Dilakukan Oleh')
                            ->relationship('assessedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->columns(3),

                Section::make('Keluhan & Riwayat')
                    ->schema([
                        Textarea::make('chief_complaint')
                            ->label('Keluhan Utama')
                            ->placeholder('Keluhan utama pasien...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('history_of_illness')
                            ->label('Riwayat Penyakit')
                            ->placeholder('Riwayat penyakit sekarang dan dahulu...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Tanda Vital')
                    ->schema([
                        TextInput::make('vital_signs.blood_pressure')
                            ->label('Tekanan Darah (mmHg)')
                            ->placeholder('120/80'),

                        TextInput::make('vital_signs.pulse')
                            ->label('Nadi (x/menit)')
                            ->numeric()
                            ->placeholder('80'),

                        TextInput::make('vital_signs.respiration')
                            ->label('Pernapasan (x/menit)')
                            ->numeric()
                            ->placeholder('20'),

                        TextInput::make('vital_signs.temperature')
                            ->label('Suhu (°C)')
                            ->numeric()
                            ->placeholder('36.5'),

                        TextInput::make('vital_signs.weight_kg')
                            ->label('Berat Badan (kg)')
                            ->numeric()
                            ->placeholder('70'),

                        TextInput::make('vital_signs.height_cm')
                            ->label('Tinggi Badan (cm)')
                            ->numeric()
                            ->placeholder('170'),

                        TextInput::make('vital_signs.spo2')
                            ->label('SpO2 (%)')
                            ->numeric()
                            ->placeholder('98'),

                        TextInput::make('vital_signs.pain_scale')
                            ->label('Skala Nyeri (0-10)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->placeholder('0'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Section::make('Pemeriksaan Fisik')
                    ->schema([
                        Textarea::make('physical_examination.general')
                            ->label('Keadaan Umum')
                            ->placeholder('Keadaan umum pasien...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('physical_examination.head')
                            ->label('Kepala')
                            ->placeholder('Pemeriksaan kepala...')
                            ->rows(2),

                        Textarea::make('physical_examination.thorax')
                            ->label('Thorax')
                            ->placeholder('Pemeriksaan thorax...')
                            ->rows(2),

                        Textarea::make('physical_examination.abdomen')
                            ->label('Abdomen')
                            ->placeholder('Pemeriksaan abdomen...')
                            ->rows(2),

                        Textarea::make('physical_examination.extremities')
                            ->label('Ekstremitas')
                            ->placeholder('Pemeriksaan ekstremitas...')
                            ->rows(2),

                        Textarea::make('physical_examination.neurological')
                            ->label('Neurologis')
                            ->placeholder('Pemeriksaan neurologis...')
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Hasil Pemeriksaan Penunjang')
                    ->schema([
                        Textarea::make('laboratory_results')
                            ->label('Hasil Laboratorium')
                            ->placeholder('Hasil pemeriksaan laboratorium...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('radiology_results')
                            ->label('Hasil Radiologi')
                            ->placeholder('Hasil pemeriksaan radiologi...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Kesimpulan & Rencana')
                    ->schema([
                        Textarea::make('assessment_summary')
                            ->label('Kesimpulan Penilaian')
                            ->placeholder('Kesimpulan dari pemeriksaan...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('plan_of_care')
                            ->label('Rencana Asuhan')
                            ->placeholder('Rencana asuhan/tindakan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
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
            ->recordTitleAttribute('assessment_date')
            ->columns([
                TextColumn::make('assessment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('assessment_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'initial' => 'Pemeriksaan Awal',
                        'follow_up' => 'Follow-up',
                        'pre_op' => 'Pre-Operasi',
                        'post_op' => 'Post-Operasi',
                        'discharge' => 'Pulang',
                        'emergency' => 'Gawat Darurat',
                        'specialist' => 'Konsul Spesialis',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'initial' => 'primary',
                        'follow_up' => 'info',
                        'pre_op' => 'warning',
                        'post_op' => 'success',
                        'discharge' => 'gray',
                        'emergency' => 'danger',
                        'specialist' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('chief_complaint')
                    ->label('Keluhan Utama')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                TextColumn::make('assessedBy.name')
                    ->label('Dilakukan Oleh')
                    ->placeholder('-'),

                TextColumn::make('assessment_summary')
                    ->label('Kesimpulan')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('assessment_date', 'desc')
            ->filters([
                SelectFilter::make('assessment_type')
                    ->label('Tipe Penilaian')
                    ->options([
                        'initial' => 'Pemeriksaan Awal',
                        'follow_up' => 'Follow-up',
                        'pre_op' => 'Pre-Operasi',
                        'post_op' => 'Post-Operasi',
                        'discharge' => 'Pemeriksaan Pulang',
                        'emergency' => 'Gawat Darurat',
                        'specialist' => 'Konsul Spesialis',
                    ])
                    ->native(false),

                Filter::make('assessment_date')
                    ->label('Tanggal Penilaian')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('assessment_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('assessment_date', '<=', $data['to']));
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Penilaian')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => !$this->getOwnerRecord()->is_finalized),
                ]),
            ])
            ->emptyStateHeading('Belum ada penilaian')
            ->emptyStateDescription('Tambahkan penilaian/pemeriksaan pertama.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
