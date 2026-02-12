<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use App\Filament\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewMedicalRecord extends ViewRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil')
                ->visible(fn (?Model $record): bool => !($record?->is_finalized ?? false)),

            Action::make('finalize')
                ->label('Finalisasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi Rekam Medis')
                ->modalDescription('Apakah Anda yakin ingin memfinalisasi rekam medis ini? Setelah difinalisasi, data tidak dapat diubah.')
                ->modalSubmitActionLabel('Ya, Finalisasi')
                ->visible(fn (?Model $record): bool => !($record?->is_finalized ?? false))
                ->action(function (Model $record): void {
                    $record->update([
                        'is_finalized' => true,
                        'finalized_at' => now(),
                        'finalized_by' => auth()->id(),
                    ]);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),

            Action::make('print')
                ->label('Cetak EMR')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Model $record): string => route('medical-records.print', $record))
                ->openUrlInNewTab()
                ->visible(fn (?Model $record): bool => $record?->is_finalized === true),

            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pasien')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('record_number')
                                    ->label('No. Rekam Medis')
                                    ->icon('heroicon-m-identification'),

                                TextEntry::make('patient.name')
                                    ->label('Nama Pasien')
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('patient.medical_record_number')
                                    ->label('No. MR Pasien')
                                    ->icon('heroicon-m-identification'),

                                TextEntry::make('visit_date')
                                    ->label('Tanggal Kunjungan')
                                    ->date('d M Y')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('visit.visit_number')
                                    ->label('No. Kunjungan')
                                    ->icon('heroicon-m-hashtag'),

                                IconEntry::make('is_finalized')
                                    ->label('Status Finalisasi')
                                    ->boolean(),
                            ]),
                    ]),

                Section::make('SOAP')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('subjective')
                                    ->label('Subjective (S) - Keluhan Pasien')
                                    ->placeholder('Tidak ada data')
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('objective')
                                    ->label('Objective (O) - Hasil Pemeriksaan')
                                    ->placeholder('Tidak ada data')
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('assessment')
                                    ->label('Assessment (A) - Diagnosa')
                                    ->placeholder('Tidak ada data')
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('plan')
                                    ->label('Plan (P) - Rencana Tindakan')
                                    ->placeholder('Tidak ada data')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Diagnosis')
                    ->icon('heroicon-o-heart')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('diagnosis_primary')
                                    ->label('Diagnosis Utama')
                                    ->placeholder('-')
                                    ->weight('font-semibold')
                                    ->color('danger'),

                                TextEntry::make('diagnosis_secondary')
                                    ->label('Diagnosis Sekunder')
                                    ->placeholder('-'),

                                TextEntry::make('icd10_code')
                                    ->label('Kode ICD-10')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('icd10_description')
                                    ->label('Deskripsi ICD-10')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Tindakan/Prosedur')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('procedure_code')
                                    ->label('Kode Prosedur')
                                    ->placeholder('-'),

                                TextEntry::make('procedure_description')
                                    ->label('Deskripsi Prosedur')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Informasi Finalisasi')
                    ->icon('heroicon-o-lock-closed')
                    ->collapsible()
                    ->visible(fn (?Model $record): bool => $record?->is_finalized === true)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('finalized_at')
                                    ->label('Difinalisasi Pada')
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('finalizedBy.name')
                                    ->label('Difinalisasi Oleh')
                                    ->icon('heroicon-m-user'),
                            ]),
                    ]),

                Section::make('Catatan')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan Tambahan')
                            ->placeholder('Tidak ada catatan')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
