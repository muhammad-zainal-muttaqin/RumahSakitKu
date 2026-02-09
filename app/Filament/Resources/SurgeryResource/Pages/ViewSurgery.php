<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\SurgeryResource;
use App\Services\SurgeryService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSurgery extends ViewRecord
{
    protected static string $resource = SurgeryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // Action: Start Surgery
            Action::make('start')
                ->label('Mulai Operasi')
                ->icon('heroicon-m-play')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['scheduled', 'preparation']))
                ->action(function (SurgeryService $service) {
                    $service->startSurgery($this->record->id);
                    $this->refreshFormData(['status', 'actual_start']);
                }),

            // Action: Complete Surgery
            Action::make('complete')
                ->label('Selesai')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Tandai operasi sebagai selesai?')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->action(function (SurgeryService $service) {
                    $service->completeSurgery($this->record->id, [
                        'safety_checklist_sign_out' => true,
                    ]);
                    $this->refreshFormData(['status', 'actual_end']);
                }),

            // Action: Cancel Surgery
            Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => !in_array($this->record->status, ['completed', 'cancelled']))
                ->schema([
                    Textarea::make('cancellation_reason')
                        ->label('Alasan Pembatalan')
                        ->required(),
                ])
                ->action(function (array $data, SurgeryService $service) {
                    $service->cancelSurgery(
                        $this->record->id,
                        $data['cancellation_reason'] ?? null,
                        Auth::id()
                    );
                    $this->refreshFormData(['status', 'cancelled_at']);
                }),

            // Action: Postpone Surgery
            Action::make('postpone')
                ->label('Tunda')
                ->icon('heroicon-m-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => !in_array($this->record->status, ['completed', 'cancelled', 'in_progress']))
                ->schema([
                    Textarea::make('postponed_reason')
                        ->label('Alasan Penundaan')
                        ->required(),
                ])
                ->action(function (array $data, SurgeryService $service) {
                    $service->postponeSurgery($this->record->id, $data['postponed_reason'] ?? null);
                    $this->refreshFormData(['is_postponed', 'postponed_reason']);
                }),

            // Action: Print
            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->url(fn () => route('surgeries.print', ['id' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn () => false), // Disabled until route is defined
        ];
    }
}
