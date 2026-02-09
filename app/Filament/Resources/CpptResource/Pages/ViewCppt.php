<?php

declare(strict_types=1);

namespace App\Filament\Resources\CpptResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\CpptResource;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewCppt extends ViewRecord
{
    protected static string $resource = CpptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-m-pencil-square'),

            Action::make('print')
                ->label('Cetak CPPT')
                ->icon('heroicon-m-printer')
                ->color('secondary')
                ->url(fn ($record) => route('cppt.print', $record))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->is_verified),

            Action::make('verify')
                ->label('Verifikasi')
                ->icon('heroicon-m-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verifikasi CPPT')
                ->modalDescription('Apakah Anda yakin ingin memverifikasi CPPT ini?')
                ->modalSubmitActionLabel('Ya, Verifikasi')
                ->visible(fn ($record): bool => ! $record->is_verified && Auth::user()?->hasRole(['dokter', 'admin']))
                ->action(function ($record): void {
                    $record->update([
                        'is_verified' => true,
                        'verified_by' => Auth::id(),
                        'verified_at' => now(),
                    ]);
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Informasi Dasar
                \Filament\Schemas\Components\Section::make('Informasi CPPT')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                TextEntry::make('cppt_date')
                                    ->label('Tanggal & Waktu')
                                    ->dateTime('d M Y H:i')
                                    ->icon('heroicon-m-calendar'),

                                TextEntry::make('cppt_type')
                                    ->label('Tipe CPPT')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'progress_note' => 'Catatan Perkembangan',
                                        'procedure_note' => 'Catatan Prosedur',
                                        'discharge_note' => 'Catatan Pulang',
                                        default => $state,
                                    })
                                    ->icon('heroicon-m-document-text')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'progress_note' => 'primary',
                                        'procedure_note' => 'warning',
                                        'discharge_note' => 'success',
                                        default => 'gray',
                                    }),

                                \Filament\Schemas\Components\Group::make([
                                    IconEntry::make('is_verified')
                                        ->label('Status Verifikasi')
                                        ->boolean(),
                                ]),
                            ]),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('medicalRecord.record_number')
                                    ->label('Nomor Rekam Medis')
                                    ->icon('heroicon-m-clipboard-document')
                                    ->url(fn ($record) => $record->medicalRecord ? '#' : null)
                                    ->placeholder('-'),

                                TextEntry::make('medicalRecord.patient.name')
                                    ->label('Nama Pasien')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                            ]),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('documentedBy.name')
                                    ->label('Dokumen Oleh')
                                    ->icon('heroicon-m-pencil')
                                    ->placeholder('-'),

                                TextEntry::make('verifiedBy.name')
                                    ->label('Diverifikasi Oleh')
                                    ->icon('heroicon-m-shield-check')
                                    ->placeholder('-'),
                            ])
                            ->visible(fn ($record) => $record->is_verified),
                    ]),

                // SOAP Format Display
                \Filament\Schemas\Components\Section::make('SOAP - Subjective, Objective, Assessment, Plan')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        // Subjective
                        \Filament\Schemas\Components\Section::make('S - Subjective (Keluhan & Riwayat)')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                TextEntry::make('subjective')
                                    ->label('')
                                    ->placeholder('Tidak ada data subjective')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),

                        // Objective
                        \Filament\Schemas\Components\Section::make('O - Objective (Hasil Pemeriksaan)')
                            ->icon('heroicon-o-eye')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                TextEntry::make('objective')
                                    ->label('')
                                    ->placeholder('Tidak ada data objective')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),

                        // Assessment
                        \Filament\Schemas\Components\Section::make('A - Assessment (Diagnosis & Masalah)')
                            ->icon('heroicon-o-academic-cap')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                TextEntry::make('assessment')
                                    ->label('')
                                    ->placeholder('Tidak ada data assessment')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),

                        // Plan
                        \Filament\Schemas\Components\Section::make('P - Plan (Rencana & Tindakan)')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->collapsible()
                            ->collapsed(false)
                            ->schema([
                                TextEntry::make('plan')
                                    ->label('')
                                    ->placeholder('Tidak ada data plan')
                                    ->markdown()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // Instruksi & Evaluasi
                \Filament\Schemas\Components\Section::make('Instruksi & Evaluasi')
                    ->icon('heroicon-o-clipboard')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        TextEntry::make('instruction')
                            ->label('Instruksi/Implementasi')
                            ->placeholder('Tidak ada instruksi')
                            ->markdown()
                            ->columnSpanFull(),

                        TextEntry::make('evaluation')
                            ->label('Evaluasi')
                            ->placeholder('Tidak ada evaluasi')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->instruction || $record->evaluation),

                // ICD-10
                \Filament\Schemas\Components\Section::make('Diagnosis (ICD-10)')
                    ->icon('heroicon-o-tag')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('icd10_code')
                                    ->label('Kode ICD-10')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-hashtag'),

                                TextEntry::make('icd10_description')
                                    ->label('Deskripsi')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-document-text'),
                            ]),
                    ])
                    ->visible(fn ($record) => $record->icd10_code),

                // Timeline Section
                \Filament\Schemas\Components\Section::make('Riwayat & Timeline')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed(true)
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-m-plus-circle'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-m-arrow-path'),
                            ]),

                        TextEntry::make('verified_at')
                            ->label('Waktu Verifikasi')
                            ->dateTime('d M Y H:i:s')
                            ->icon('heroicon-m-shield-check')
                            ->placeholder('Belum diverifikasi')
                            ->visible(fn ($record) => $record->is_verified),
                    ])
                    ->visible(fn ($record) => true),
            ]);
    }
}
