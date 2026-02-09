<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogResource\Pages;

use Filament\Actions\Action;
use Exception;
use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Audit')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                TextEntry::make('event')
                                    ->label('Aksi')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        'restored' => 'info',
                                        'force_deleted' => 'gray',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'created' => 'CREATE',
                                        'updated' => 'UPDATE',
                                        'deleted' => 'DELETE',
                                        'restored' => 'RESTORE',
                                        'force_deleted' => 'FORCE DELETE',
                                        default => strtoupper($state),
                                    })
                                    ->iconPosition('before')
                                    ->icon(fn (string $state): string => match ($state) {
                                        'created' => 'heroicon-o-plus-circle',
                                        'updated' => 'heroicon-o-pencil-square',
                                        'deleted' => 'heroicon-o-trash',
                                        'restored' => 'heroicon-o-arrow-uturn-left',
                                        'force_deleted' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    }),

                                TextEntry::make('created_at')
                                    ->label('Waktu')
                                    ->dateTime('d M Y H:i:s'),

                                TextEntry::make('user.name')
                                    ->label('User')
                                    ->placeholder('System')
                                    ->url(fn (AuditLog $record): ?string => $record->user_id ? route('filament.admin.resources.users.view', ['record' => $record->user_id]) : null)
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-user'),
                            ]),

                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                TextEntry::make('model_type_label')
                                    ->label('Model Type')
                                    ->icon('heroicon-o-cube'),

                                TextEntry::make('auditable_id')
                                    ->label('Model ID')
                                    ->copyable()
                                    ->icon('heroicon-o-hashtag'),

                                TextEntry::make('ip_address')
                                    ->label('IP Address')
                                    ->icon('heroicon-o-globe-alt'),
                            ]),

                        TextEntry::make('url')
                            ->label('URL')
                            ->copyable()
                            ->columnSpanFull()
                            ->icon('heroicon-o-link'),

                        TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->icon('heroicon-o-computer-desktop'),
                    ]),

                \Filament\Schemas\Components\Section::make('Perbandingan Data')
                    ->icon('heroicon-o-arrows-right-left')
                    ->collapsible()
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                KeyValueEntry::make('old_values')
                                    ->label('Nilai Lama (Sebelum)')
                                    ->placeholder('Tidak ada data (record baru)')
                                    ->keyLabel('Field')
                                    ->valueLabel('Nilai'),

                                KeyValueEntry::make('new_values')
                                    ->label('Nilai Baru (Sesudah)')
                                    ->placeholder('Tidak ada data (record dihapus)')
                                    ->keyLabel('Field')
                                    ->valueLabel('Nilai'),
                            ]),
                    ])
                    ->visible(fn (AuditLog $record): bool => $record->old_values !== null || $record->new_values !== null),

                \Filament\Schemas\Components\Section::make('Detail Perubahan')
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('changes_detail')
                            ->label('')
                            ->default(fn (AuditLog $record): string => $this->generateChangesDetail($record))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (AuditLog $record): bool => $record->event === 'updated' && $record->old_values && $record->new_values),
            ]);
    }

    /**
     * Generate HTML detail of changes for display.
     */
    protected function generateChangesDetail(AuditLog $record): string
    {
        if (!$record->old_values || !$record->new_values) {
            return '<p class="text-gray-500">Tidak ada detail perubahan.</p>';
        }

        $html = '<div class="space-y-2">';
        
        foreach ($record->new_values as $key => $newValue) {
            $oldValue = $record->old_values[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $html .= '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                $html .= '<p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' . htmlspecialchars($key) . '</p>';
                $html .= '<div class="grid grid-cols-2 gap-4">';
                $html .= '<div class="text-sm">';
                $html .= '<span class="text-red-600 dark:text-red-400 font-medium">Lama:</span> ';
                $html .= '<span class="line-through">' . htmlspecialchars($this->formatValue($oldValue)) . '</span>';
                $html .= '</div>';
                $html .= '<div class="text-sm">';
                $html .= '<span class="text-green-600 dark:text-green-400 font-medium">Baru:</span> ';
                $html .= '<span>' . htmlspecialchars($this->formatValue($newValue)) . '</span>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Format a value for display.
     */
    protected function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '(kosong)';
        }
        
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        
        if (is_array($value)) {
            return json_encode($value);
        }
        
        return (string) $value;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_model')
                ->label('Lihat Model')
                ->icon('heroicon-o-eye')
                ->url(function (AuditLog $record): ?string {
                    $resource = $this->getResourceFromModel($record->auditable_type);
                    if ($resource && method_exists($resource, 'getUrl')) {
                        try {
                            return $resource::getUrl('view', ['record' => $record->auditable_id]);
                        } catch (Exception $e) {
                            return null;
                        }
                    }
                    return null;
                })
                ->visible(function (AuditLog $record): bool {
                    return $record->event !== 'deleted' && $record->event !== 'force_deleted';
                })
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get Filament resource from model class.
     */
    protected function getResourceFromModel(string $modelClass): ?string
    {
        $mapping = [
            'App\Models\MasterData\Employee' => EmployeeResource::class,
            'App\Models\Patient\Patient' => PatientResource::class,
            'App\Models\Patient\Visit' => VisitResource::class,
            'App\Models\Clinical\MedicalRecord' => MedicalRecordResource::class,
            'App\Models\Clinical\Prescription' => PrescriptionResource::class,
            'App\Models\MasterData\Medicine' => MedicineResource::class,
            'App\Models\MasterData\Room' => RoomResource::class,
            'App\Models\MasterData\Bed' => BedResource::class,
            'App\Models\MasterData\Polyclinic' => PolyclinicResource::class,
            'App\Models\User' => UserResource::class,
        ];

        return $mapping[$modelClass] ?? null;
    }
}
