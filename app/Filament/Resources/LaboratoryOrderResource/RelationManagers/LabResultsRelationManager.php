<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOrderResource\RelationManagers;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\MasterData\LabTest;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class LabResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    protected static ?string $title = 'Hasil Pemeriksaan';

    protected static ?string $recordTitleAttribute = 'labTest.name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pemeriksaan')
                    ->schema([
                        Select::make('lab_test_id')
                            ->label('Jenis Pemeriksaan')
                            ->relationship('labTest', 'name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (LabTest $record): string => "{$record->test_code} - {$record->name}")
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),

                        TextInput::make('unit')
                            ->label('Satuan')
                            ->maxLength(50)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('reference_range')
                            ->label('Nilai Rujukan')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Hasil')
                    ->schema([
                        TextInput::make('result_value')
                            ->label('Nilai Hasil (Numerik)')
                            ->numeric()
                            ->placeholder('Masukkan nilai numerik jika ada'),

                        Textarea::make('result_text')
                            ->label('Hasil (Teks)')
                            ->placeholder('Masukkan hasil dalam bentuk teks jika tidak ada nilai numerik')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('flag')
                            ->label('Flag')
                            ->options([
                                'normal' => 'Normal',
                                'low' => 'Rendah',
                                'high' => 'Tinggi',
                                'abnormal' => 'Abnormal',
                                'critical' => 'Kritis',
                            ])
                            ->native(false)
                            ->live(),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan untuk hasil ini')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Teknis')
                    ->schema([
                        TextInput::make('test_method')
                            ->label('Metode Pemeriksaan')
                            ->maxLength(100)
                            ->placeholder('Contoh: Spectrophotometry'),

                        TextInput::make('analyzer_machine')
                            ->label('Mesin Analyzer')
                            ->maxLength(100)
                            ->placeholder('Contoh: Cobas 6000'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Validasi')
                    ->schema([
                        Toggle::make('is_validated')
                            ->label('Sudah Divalidasi')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('validated_by_name')
                            ->label('Divalidasi Oleh')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('validated_at')
                            ->label('Waktu Validasi')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('labTest.name')
            ->columns([
                TextColumn::make('labTest.name')
                    ->label('Pemeriksaan')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('labTest.category_label')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($record) => $record->labTest?->category_color ?? 'gray'),

                TextColumn::make('display_value')
                    ->label('Hasil')
                    ->weight(fn ($record) => $record->is_abnormal ? 'font-bold' : 'font-normal')
                    ->color(fn ($record) => $record->is_abnormal ? 'danger' : 'success'),

                BadgeColumn::make('flag')
                    ->label('Flag')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'normal' => 'Normal',
                        'low' => 'Rendah',
                        'high' => 'Tinggi',
                        'abnormal' => 'Abnormal',
                        'critical' => 'Kritis',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'normal' => 'success',
                        'low' => 'warning',
                        'high' => 'warning',
                        'abnormal' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reference_range')
                    ->label('Nilai Rujukan')
                    ->placeholder('-'),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->placeholder('-'),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-'),

                IconColumn::make('is_validated')
                    ->label('Tervalidasi')
                    ->alignCenter()
                    ->boolean(),

                TextColumn::make('validatedBy.name')
                    ->label('Validator')
                    ->placeholder('-'),

                TextColumn::make('validated_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('flag')
                    ->label('Flag')
                    ->options([
                        'normal' => 'Normal',
                        'low' => 'Rendah',
                        'high' => 'Tinggi',
                        'abnormal' => 'Abnormal',
                        'critical' => 'Kritis',
                    ])
                    ->native(false),

                TernaryFilter::make('is_validated')
                    ->label('Status Validasi')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Divalidasi')
                    ->falseLabel('Belum Divalidasi'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pemeriksaan')
                    ->mutateDataUsing(function (array $data): array {
                        $labTest = LabTest::find($data['lab_test_id']);
                        if ($labTest) {
                            $data['reference_range'] = $labTest->reference_value;
                            $data['unit'] = $labTest->unit;
                        }
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

                Action::make('validate')
                    ->label('Validasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => !$record->is_validated && $record->result_value !== null)
                    ->action(function ($record): void {
                        $record->update([
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Hasil berhasil divalidasi')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pemeriksaan')
            ->emptyStateDescription('Tambahkan pemeriksaan untuk order ini.')
            ->emptyStateIcon('heroicon-o-beaker');
    }

    public function isReadOnly(): bool
    {
        $order = $this->getOwnerRecord();
        return in_array($order->status, ['validated', 'cancelled']);
    }
}
