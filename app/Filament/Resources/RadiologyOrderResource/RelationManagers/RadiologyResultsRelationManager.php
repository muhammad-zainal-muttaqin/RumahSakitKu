<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadiologyOrderResource\RelationManagers;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\MasterData\Employee;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class RadiologyResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'result';

    protected static ?string $title = 'Hasil Pemeriksaan';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Upload Hasil')
                    ->schema([
                        FileUpload::make('result_images')
                            ->label('Gambar/File Hasil')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('radiology-results')
                            ->downloadable()
                            ->previewable(true)
                            ->columnSpanFull(),

                        Textarea::make('technician_notes')
                            ->label('Catatan Teknisi')
                            ->placeholder('Catatan dari teknisi radiologi')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('exposure_parameters')
                            ->label('Parameter Exposure')
                            ->placeholder('Contoh: kVp: 80, mAs: 200')
                            ->maxLength(255),

                        TextInput::make('dose_info')
                            ->label('Informasi Dosis')
                            ->placeholder('Informasi dosis radiasi jika ada')
                            ->maxLength(255),
                    ])
                    ->collapsible(),

                Section::make('Bacaan Radiologis')
                    ->schema([
                        RichEditor::make('report_text')
                            ->label('Deskripsi Temuan')
                            ->placeholder('Deskripsikan temuan radiologis secara detail')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('radiology-reports')
                            ->columnSpanFull(),

                        Textarea::make('conclusion')
                            ->label('Kesimpulan')
                            ->placeholder('Kesimpulan akhir dari pemeriksaan')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('recommendation')
                            ->label('Saran/Tindak Lanjut')
                            ->placeholder('Saran atau rekomendasi tindak lanjut')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Quality Assurance')
                    ->schema([
                        Select::make('quality_assurance')
                            ->label('Kualitas Gambar')
                            ->options([
                                'excellent' => 'Sangat Baik',
                                'good' => 'Baik',
                                'adequate' => 'Cukup',
                                'poor' => 'Kurang',
                                'non_diagnostic' => 'Non-Diagnostic',
                            ])
                            ->native(false),
                    ])
                    ->collapsible(),

                Section::make('Validasi')
                    ->schema([
                        Select::make('radiologist_id')
                            ->label('Radiologis')
                            ->relationship('radiologist', 'name')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Employee::where('employee_type', 'radiologist')->pluck('name', 'id'))
                            ->required(),

                        DateTimePicker::make('reported_at')
                            ->label('Waktu Pelaporan')
                            ->required()
                            ->default(now())
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('result_images')
                    ->label('Gambar')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText()
                    ->placeholder('Tidak ada gambar'),

                TextColumn::make('report_text')
                    ->label('Deskripsi')
                    ->html()
                    ->limit(100)
                    ->placeholder('-'),

                TextColumn::make('conclusion')
                    ->label('Kesimpulan')
                    ->limit(100)
                    ->placeholder('-'),

                TextColumn::make('recommendation')
                    ->label('Saran')
                    ->limit(50)
                    ->placeholder('-'),

                TextColumn::make('radiologist.name')
                    ->label('Radiologis')
                    ->placeholder('-'),

                TextColumn::make('reported_at')
                    ->label('Waktu Pelaporan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),

                BadgeColumn::make('quality_assurance')
                    ->label('Kualitas')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'excellent' => 'Sangat Baik',
                        'good' => 'Baik',
                        'adequate' => 'Cukup',
                        'poor' => 'Kurang',
                        'non_diagnostic' => 'Non-Diagnostic',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'primary',
                        'adequate' => 'warning',
                        'poor' => 'danger',
                        'non_diagnostic' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('quality_assurance')
                    ->label('Kualitas Gambar')
                    ->options([
                        'excellent' => 'Sangat Baik',
                        'good' => 'Baik',
                        'adequate' => 'Cukup',
                        'poor' => 'Kurang',
                        'non_diagnostic' => 'Non-Diagnostic',
                    ])
                    ->native(false),

                SelectFilter::make('radiologist_id')
                    ->label('Radiologis')
                    ->relationship('radiologist', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Entry Hasil')
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    })
                    ->after(function ($record): void {
                        // Update order status to reported
                        $order = $record->radiologyOrder;
                        if ($order && $order->status === 'completed') {
                            $order->update(['status' => 'reported']);
                        }
                        Notification::make()
                            ->title('Hasil radiologi berhasil disimpan')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($livewire): bool => $livewire->getOwnerRecord()->result === null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

                Action::make('viewImages')
                    ->label('Lihat Gambar')
                    ->icon('heroicon-o-photo')
                    ->color('primary')
                    ->modalContent(fn ($record): string => view('components.radiology-image-preview', ['images' => $record->result_images ?? []]))
                    ->modalHeading('Preview Gambar Radiologi')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn ($record): bool => !empty($record->result_images)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada hasil pemeriksaan')
            ->emptyStateDescription('Entry hasil radiologi untuk order ini.')
            ->emptyStateIcon('heroicon-o-x-circle');
    }

    public function isReadOnly(): bool
    {
        $order = $this->getOwnerRecord();
        return $order->status === 'cancelled';
    }
}
