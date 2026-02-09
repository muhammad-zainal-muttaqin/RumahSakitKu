<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\MedicalRecordResource\RelationManagers\CpptsRelationManager;
use App\Filament\Resources\MedicalRecordResource\RelationManagers\AssessmentsRelationManager;
use App\Filament\Resources\MedicalRecordResource\RelationManagers\PrescriptionsRelationManager;
use App\Filament\Resources\MedicalRecordResource\Pages\ListMedicalRecords;
use App\Filament\Resources\MedicalRecordResource\Pages\CreateMedicalRecord;
use App\Filament\Resources\MedicalRecordResource\Pages\ViewMedicalRecord;
use App\Filament\Resources\MedicalRecordResource\Pages\EditMedicalRecord;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\MedicalRecordResource\Pages;

/**
 * Medical Record Resource
 * 
 * Filament resource for managing patient medical records.
 * 
 * @package App\Filament\Resources
 */

use App\Filament\Resources\MedicalRecordResource\RelationManagers;
use App\Models\Clinical\MedicalRecord;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'EMR Pasien';

    protected static ?string $modelLabel = 'Rekam Medis';

    protected static ?string $pluralModelLabel = 'Rekam Medis';

    protected static ?int $navigationSort = 20;

    protected static UnitEnum|string|null $navigationGroup = 'Rekam Medis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('EMR')
                    ->tabs([
                        // Tab 1: SOAP
                        Tab::make('SOAP')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Informasi Kunjungan')
                                    ->schema([
                                        TextInput::make('record_number')
                                            ->label('No. Rekam Medis')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(50)
                                            ->prefixIcon('heroicon-m-identification'),

                                        Select::make('patient_id')
                                            ->label('Pasien')
                                            ->required()
                                            ->relationship('patient', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false),

                                        Select::make('visit_id')
                                            ->label('Kunjungan')
                                            ->relationship('visit', 'visit_number')
                                            ->searchable()
                                            ->preload()
                                            ->native(false),

                                        DatePicker::make('visit_date')
                                            ->label('Tanggal Kunjungan')
                                            ->required()
                                            ->default(now()),
                                    ])
                                    ->columns(2),

                                Section::make('Subjective (S) - Keluhan Pasien')
                                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                    ->schema([
                                        Textarea::make('subjective')
                                            ->label('Keluhan')
                                            ->placeholder('Keluhan utama pasien...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Objective (O) - Hasil Pemeriksaan')
                                    ->icon('heroicon-o-eye')
                                    ->schema([
                                        Textarea::make('objective')
                                            ->label('Pemeriksaan Fisik')
                                            ->placeholder('Hasil pemeriksaan fisik...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Assessment (A) - Diagnosa')
                                    ->icon('heroicon-o-clipboard-document-check')
                                    ->schema([
                                        Textarea::make('assessment')
                                            ->label('Penilaian/Diagnosa Kerja')
                                            ->placeholder('Diagnosa atau penilaian klinis...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Plan (P) - Rencana Tindakan')
                                    ->icon('heroicon-o-clipboard')
                                    ->schema([
                                        Textarea::make('plan')
                                            ->label('Rencana Tindakan')
                                            ->placeholder('Rencana pengobatan dan tindak lanjut...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Tab 2: Diagnosis
                        Tab::make('Diagnosis')
                            ->icon('heroicon-o-heart')
                            ->schema([
                                Section::make('Diagnosis Utama')
                                    ->icon('heroicon-o-exclamation-circle')
                                    ->schema([
                                        TextInput::make('diagnosis_primary')
                                            ->label('Diagnosis Utama')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Diagnosis utama pasien...'),
                                    ]),

                                Section::make('Diagnosis Sekunder')
                                    ->icon('heroicon-o-list-bullet')
                                    ->schema([
                                        TextInput::make('diagnosis_secondary')
                                            ->label('Diagnosis Sekunder')
                                            ->maxLength(255)
                                            ->placeholder('Diagnosis sekunder (jika ada)...'),
                                    ]),

                                Section::make('Kode ICD-10')
                                    ->icon('heroicon-o-code-bracket')
                                    ->schema([
                                        TextInput::make('icd10_code')
                                            ->label('Kode ICD-10')
                                            ->maxLength(20)
                                            ->placeholder('Contoh: A00.0')
                                            ->prefixIcon('heroicon-m-hashtag'),

                                        TextInput::make('icd10_description')
                                            ->label('Deskripsi ICD-10')
                                            ->maxLength(255)
                                            ->placeholder('Deskripsi kode ICD-10...'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Tab 3: Tindakan/Prosedur
                        Tab::make('Tindakan')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Informasi Prosedur')
                                    ->schema([
                                        TextInput::make('procedure_code')
                                            ->label('Kode Prosedur')
                                            ->maxLength(50)
                                            ->placeholder('Kode tindakan/prosedur...')
                                            ->prefixIcon('heroicon-m-hashtag'),

                                        Textarea::make('procedure_description')
                                            ->label('Deskripsi Prosedur')
                                            ->placeholder('Deskripsi tindakan yang dilakukan...')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Tab 4: Status & Finalisasi
                        Tab::make('Status')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Finalisasi')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Toggle::make('is_finalized')
                                            ->label('Sudah Difinalisasi')
                                            ->default(false)
                                            ->live()
                                            ->disabled(fn (?Model $record): bool => $record?->is_finalized ?? false),

                                        DateTimePicker::make('finalized_at')
                                            ->label('Waktu Finalisasi')
                                            ->disabled()
                                            ->hidden(fn (Get $get): bool => !$get('is_finalized')),

                                        Select::make('finalized_by')
                                            ->label('Difinalisasi Oleh')
                                            ->relationship('finalizedBy', 'name')
                                            ->disabled()
                                            ->hidden(fn (Get $get): bool => !$get('is_finalized'))
                                            ->native(false),
                                    ])
                                    ->collapsible(),

                                Section::make('Catatan Tambahan')
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Catatan')
                                            ->placeholder('Catatan tambahan...')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('record_number')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('font-medium'),

                TextColumn::make('patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('patient.medical_record_number')
                    ->label('No. MR Pasien')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('visit.visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('diagnosis_primary')
                    ->label('Diagnosis Utama')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('-'),

                TextColumn::make('icd10_code')
                    ->label('ICD-10')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                IconColumn::make('is_finalized')
                    ->label('Finalized?')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                BadgeColumn::make('status_display')
                    ->label('Status')
                    ->getStateUsing(fn (Model $record): string => $record->is_finalized ? 'Final' : 'Draft')
                    ->color(fn (Model $record): string => $record->is_finalized ? 'success' : 'warning'),

                TextColumn::make('finalized_at')
                    ->label('Finalized At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('finalizedBy.name')
                    ->label('Finalized By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('visit_date', 'desc')
            ->filters([
                Filter::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('to')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    }),

                TernaryFilter::make('is_finalized')
                    ->label('Status Finalisasi')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Final')
                    ->falseLabel('Draft'),

                SelectFilter::make('icd10_code')
                    ->label('Kode ICD-10')
                    ->options(function (): array {
                        return MedicalRecord::query()
                            ->whereNotNull('icd10_code')
                            ->distinct()
                            ->pluck('icd10_code', 'icd10_code')
                            ->toArray();
                    })
                    ->searchable()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->visible(fn (Model $record): bool => !$record->is_finalized),

                Action::make('finalize')
                    ->label('Finalisasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Finalisasi Rekam Medis')
                    ->modalDescription('Apakah Anda yakin ingin memfinalisasi rekam medis ini? Setelah difinalisasi, data tidak dapat diubah.')
                    ->modalSubmitActionLabel('Ya, Finalisasi')
                    ->visible(fn (Model $record): bool => !$record->is_finalized)
                    ->action(function (Model $record): void {
                        $record->update([
                            'is_finalized' => true,
                            'finalized_at' => now(),
                            'finalized_by' => Auth::id(),
                        ]);
                    })
                    ->after(function () {
                        // Notification handled by Filament
                    }),

                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Model $record): string => route('medical-records.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Model $record): bool => $record->is_finalized),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->can('delete', MedicalRecord::class) ?? false),

                    BulkAction::make('export')
                        ->label('Export PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->action(function (Collection $records): void {
                            // Export logic will be handled by a dedicated export service
                            // For now, we'll dispatch an event or job
                            // TODO: Implement PDF export
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Export ke PDF')
                        ->modalDescription('Export rekam medis terpilih ke format PDF?'),
                ]),
            ])
            ->emptyStateHeading('Belum ada rekam medis')
            ->emptyStateDescription('Buat rekam medis pertama untuk memulai.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [
            CpptsRelationManager::class,
            AssessmentsRelationManager::class,
            PrescriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedicalRecords::route('/'),
            'create' => CreateMedicalRecord::route('/create'),
            'view' => ViewMedicalRecord::route('/{record}'),
            'edit' => EditMedicalRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'visit', 'finalizedBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_finalized', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
