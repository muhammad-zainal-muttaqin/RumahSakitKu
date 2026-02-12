<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CpptResource\Pages\ListCppts;
use App\Filament\Resources\CpptResource\Pages\CreateCppt;
use App\Filament\Resources\CpptResource\Pages\ViewCppt;
use App\Filament\Resources\CpptResource\Pages\EditCppt;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\CpptResource\Pages;
use App\Models\Clinical\Cppt;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CpptResource extends Resource
{
    protected static ?string $model = Cppt::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'CPPT (SOAP)';

    protected static ?string $modelLabel = 'CPPT';

    protected static ?string $pluralModelLabel = 'CPPT';

    protected static ?int $navigationSort = 22;

    protected static UnitEnum|string|null $navigationGroup = 'Rekam Medis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section: Informasi Dasar
                Section::make('Informasi CPPT')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('medical_record_id')
                            ->label('Rekam Medis')
                            ->relationship('medicalRecord', 'record_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih rekam medis')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $medicalRecord = MedicalRecord::find($state);
                                    if ($medicalRecord) {
                                        $set('patient_id', $medicalRecord->patient_id);
                                        $set('visit_id', $medicalRecord->visit_id);
                                    }
                                }
                            }),

                        DateTimePicker::make('cppt_date')
                            ->label('Tanggal & Waktu')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->prefixIcon('heroicon-m-calendar'),

                        Select::make('cppt_type')
                            ->label('Tipe CPPT')
                            ->required()
                            ->options([
                                'progress_note' => 'Catatan Perkembangan',
                                'procedure_note' => 'Catatan Prosedur',
                                'discharge_note' => 'Catatan Pulang',
                            ])
                            ->native(false)
                            ->default('progress_note'),

                        Select::make('documented_by')
                            ->label('Dokumen Oleh')
                            ->relationship('documentedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::user()?->employee_id)
                            ->native(false)
                            ->prefixIcon('heroicon-m-user'),
                    ])
                    ->columns(2),

                // Section: SOAP Format
                Section::make('SOAP - Subjective, Objective, Assessment, Plan')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        // Subjective (S)
                        Section::make('S - Subjective (Keluhan & Riwayat)')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                Textarea::make('subjective')
                                    ->label('')
                                    ->placeholder('Keluhan utama pasien, riwayat penyakit, riwayat pengobatan...')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->hintAction(
                                        Action::make('templateS1')
                                            ->label('Keluhan Umum')
                                            ->icon('heroicon-m-document-text')
                                            ->color('gray')
                                            ->action(fn (Set $set) => $set('subjective', "Keluhan Utama:\n- \n\nRiwayat Penyakit Sekarang:\n- \n\nRiwayat Penyakit Dahulu:\n- "))
                                    )
                                    ->hintAction(
                                        Action::make('templateS2')
                                            ->label('Pasien Tanpa Keluhan')
                                            ->icon('heroicon-m-check-circle')
                                            ->color('success')
                                            ->action(fn (Set $set) => $set('subjective', "Pasien dalam kondisi stabil.\nTidak ada keluhan utama.\nTidak ada mual, muntah, atau nyeri."))
                                    ),
                            ]),

                        // Objective (O)
                        Section::make('O - Objective (Hasil Pemeriksaan)')
                            ->icon('heroicon-o-eye')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                Textarea::make('objective')
                                    ->label('')
                                    ->placeholder('Hasil pemeriksaan fisik, vital signs, hasil lab, radiologi...')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->hintAction(
                                        Action::make('templateO1')
                                            ->label('Template Vital Signs')
                                            ->icon('heroicon-m-heart')
                                            ->color('danger')
                                            ->action(fn (Set $set) => $set('objective', "Keadaan Umum: Baik/Moderate/Buruk\nKesadaran: Compos Mentis/Apatis/Somnolen/Koma\n\nTanda Vital:\nTD: / mmHg\nNadi: x/menit\nRR: x/menit\nSuhu: °C\nSpO2: %\n\nPemeriksaan Fisik:\n- Kepala: Normal/Abnormal\n- Thorax: Normal/Abnormal\n- Abdomen: Normal/Abnormal\n- Ekstremitas: Normal/Abnormal"))
                                    ),
                            ]),

                        // Assessment (A)
                        Section::make('A - Assessment (Diagnosis & Masalah)')
                            ->icon('heroicon-o-academic-cap')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                Textarea::make('assessment')
                                    ->label('')
                                    ->placeholder('Diagnosis kerja, diagnosis banding, masalah medis...')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->hintAction(
                                        Action::make('templateA1')
                                            ->label('Template Diagnosis')
                                            ->icon('heroicon-m-clipboard-document-check')
                                            ->color('info')
                                            ->action(fn (Set $set) => $set('assessment', "Diagnosis Primer:\n- \n\nDiagnosis Sekunder:\n- \n\nDiagnosis Banding:\n- \n\nKomplikasi:\n- "))
                                    ),
                            ]),

                        // Plan (P)
                        Section::make('P - Plan (Rencana & Tindakan)')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                Textarea::make('plan')
                                    ->label('')
                                    ->placeholder('Rencana tindakan, terapi, observasi, rujukan...')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->hintAction(
                                        Action::make('templateP1')
                                            ->label('Template Rencana')
                                            ->icon('heroicon-m-list-bullet')
                                            ->color('warning')
                                            ->action(fn (Set $set) => $set('plan', "Terapi Farmakologi:\n- \n\nTerapi Non-Farmakologi:\n- \n\nRencana Tindakan:\n- \n\nEdukasi Pasien:\n- \n\nFollow Up:\n- "))
                                    ),
                            ]),
                    ]),

                // Section: Instruksi & Evaluasi
                Section::make('Instruksi & Evaluasi')
                    ->icon('heroicon-o-clipboard')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Textarea::make('instruction')
                            ->label('Instruksi/Implementasi')
                            ->placeholder('Instruksi kepada perawat, diet, aktivitas, monitoring...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('evaluation')
                            ->label('Evaluasi')
                            ->placeholder('Evaluasi hasil tindakan, respon pasien...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // Section: ICD10 & Verifikasi
                Section::make('ICD-10 & Verifikasi')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('icd10_code')
                                    ->label('Kode ICD-10')
                                    ->maxLength(20)
                                    ->placeholder('Contoh: J06.9')
                                    ->prefixIcon('heroicon-m-hashtag'),

                                TextInput::make('icd10_description')
                                    ->label('Deskripsi ICD-10')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Acute upper respiratory infection, unspecified')
                                    ->prefixIcon('heroicon-m-document-text'),
                            ]),

                        Toggle::make('is_verified')
                            ->label('Sudah Diverifikasi')
                            ->default(false)
                            ->live()
                            ->hint('Hanya dokter senior yang dapat memverifikasi CPPT'),

                        Grid::make(2)
                            ->schema([
                                Select::make('verified_by')
                                    ->label('Diverifikasi Oleh')
                                    ->relationship('verifiedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (Get $get) => ! $get('is_verified'))
                                    ->native(false),

                                DateTimePicker::make('verified_at')
                                    ->label('Tanggal Verifikasi')
                                    ->disabled(fn (Get $get) => ! $get('is_verified'))
                                    ->native(false)
                                    ->default(fn (Get $get) => $get('is_verified') ? now() : null),
                            ])
                            ->hidden(fn (Get $get) => ! $get('is_verified')),
                    ]),

                Hidden::make('patient_id'),
                Hidden::make('visit_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cppt_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('medicalRecord.patient.name')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->description(fn (Model $record): string => $record->medicalRecord?->record_number ?? '-'),

                BadgeColumn::make('cppt_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'progress_note' => 'Catatan Perkembangan',
                        'procedure_note' => 'Catatan Prosedur',
                        'discharge_note' => 'Catatan Pulang',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'progress_note' => 'primary',
                        'procedure_note' => 'warning',
                        'discharge_note' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'progress_note' => 'heroicon-m-arrow-path',
                        'procedure_note' => 'heroicon-m-scissors',
                        'discharge_note' => 'heroicon-m-arrow-right-on-rectangle',
                        default => 'heroicon-m-document',
                    })
                    ->sortable(),

                TextColumn::make('subjective')
                    ->label('S - Ringkas')
                    ->limit(50)
                    ->tooltip(fn (Model $record): string => $record->subjective ?? '-')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('documentedBy.name')
                    ->label('Dokumen Oleh')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                IconColumn::make('is_verified')
                    ->label('Terverifikasi')
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('verifiedBy.name')
                    ->label('Verifier')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('icd10_code')
                    ->label('ICD-10')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('cppt_date', 'desc')
            ->filters([
                SelectFilter::make('cppt_type')
                    ->label('Tipe CPPT')
                    ->options([
                        'progress_note' => 'Catatan Perkembangan',
                        'procedure_note' => 'Catatan Prosedur',
                        'discharge_note' => 'Catatan Pulang',
                    ])
                    ->native(false),

                TernaryFilter::make('is_verified')
                    ->label('Status Verifikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Terverifikasi'),

                Filter::make('cppt_date')
                    ->label('Tanggal CPPT')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('cppt_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('cppt_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->icon('heroicon-m-eye'),

                EditAction::make()
                    ->icon('heroicon-m-pencil-square'),

                // Verify action for senior doctors
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi CPPT')
                    ->modalDescription('Apakah Anda yakin ingin memverifikasi CPPT ini?')
                    ->modalSubmitActionLabel('Ya, Verifikasi')
                    ->visible(fn (?Model $record): bool => ! ($record?->is_verified ?? false) && Auth::user()?->hasRole(['dokter', 'admin']))
                    ->action(function (Model $record): void {
                        $record->update([
                            'is_verified' => true,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);
                    })
                    ->after(function () {
                        // Notification handled by model
                    }),

                DeleteAction::make()
                    ->icon('heroicon-m-trash'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada CPPT')
            ->emptyStateDescription('Buat CPPT pertama untuk memulai dokumentasi pasien.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
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
            'index' => ListCppts::route('/'),
            'create' => CreateCppt::route('/create'),
            'view' => ViewCppt::route('/{record}'),
            'edit' => EditCppt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['medicalRecord.patient', 'documentedBy', 'verifiedBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereDate('cppt_date', today())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}

