<?php

declare(strict_types=1);

namespace App\Filament\Resources\VisitQueueResource\Pages;

use BackedEnum;
use Filament\Actions\Action;
use App\Filament\Resources\VisitQueueResource\Widgets\QueueStats;
use App\Filament\Resources\VisitQueueResource;
use App\Filament\Resources\VisitQueueResource\Widgets;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\VisitQueue;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ManageVisitQueue extends Page
{
    protected static string $resource = VisitQueueResource::class;

    protected string $view = 'filament.resources.visit-queue-resource.pages.manage-visit-queue';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Kelola Antrian';

    protected static ?string $slug = 'manage';

    public Collection $polyclinics;

    public ?int $selectedPolyclinicId = null;

    public function mount(): void
    {
        $this->polyclinics = Polyclinic::active()->orderBy('name')->get();
        $this->selectedPolyclinicId = $this->polyclinics->first()?->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('callNext')
                ->label('Panggil Berikutnya')
                ->icon('heroicon-o-speaker-wave')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Panggil Antrian Berikutnya')
                ->modalDescription('Panggil antrian pertama yang sedang menunggu?')
                ->action(function () {
                    $this->callNextQueue();
                })
                ->visible(fn (): bool => $this->selectedPolyclinicId !== null),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->dispatch('refreshQueues')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QueueStats::class,
        ];
    }

    public function selectPolyclinic(int $polyclinicId): void
    {
        $this->selectedPolyclinicId = $polyclinicId;
        $this->dispatch('polyclinicChanged', $polyclinicId);
    }

    public function callQueue(int $queueId): void
    {
        $queue = VisitQueue::find($queueId);

        if (!$queue || !$queue->can_be_called) {
            Notification::make()
                ->title('Gagal memanggil')
                ->body('Antrian tidak dapat dipanggil')
                ->danger()
                ->send();
            return;
        }

        $queue->markAsCalled('Loket 1');

        Notification::make()
            ->title('Antrian dipanggil')
            ->body("Nomor antrian {$queue->display_number} telah dipanggil ke Loket 1")
            ->success()
            ->send();

        $this->dispatch('refreshQueues');
    }

    public function skipQueue(int $queueId): void
    {
        $queue = VisitQueue::find($queueId);

        if (!$queue || !$queue->can_be_skipped) {
            Notification::make()
                ->title('Gagal melewati')
                ->body('Antrian tidak dapat dilewati')
                ->danger()
                ->send();
            return;
        }

        $queue->markAsSkipped();

        Notification::make()
            ->title('Antrian dilewati')
            ->body("Nomor antrian {$queue->display_number} telah dilewati")
            ->warning()
            ->send();

        $this->dispatch('refreshQueues');
    }

    public function completeQueue(int $queueId): void
    {
        $queue = VisitQueue::find($queueId);

        if (!$queue || !$queue->can_be_completed) {
            Notification::make()
                ->title('Gagal menyelesaikan')
                ->body('Antrian tidak dapat diselesaikan')
                ->danger()
                ->send();
            return;
        }

        $queue->markAsCompleted();

        if ($queue->visit) {
            $queue->visit->update([
                'status' => 'completed',
                'is_completed' => true,
                'check_out_at' => now(),
            ]);
        }

        Notification::make()
            ->title('Antrian selesai')
            ->body("Nomor antrian {$queue->display_number} telah selesai dilayani")
            ->success()
            ->send();

        $this->dispatch('refreshQueues');
    }

    public function markInProgress(int $queueId): void
    {
        $queue = VisitQueue::find($queueId);

        if (!$queue || $queue->status !== 'called') {
            Notification::make()
                ->title('Gagal')
                ->body('Antrian tidak dapat ditandai sedang dilayani')
                ->danger()
                ->send();
            return;
        }

        $queue->markAsInProgress();

        Notification::make()
            ->title('Status diperbarui')
            ->body("Nomor antrian {$queue->display_number} sedang dilayani")
            ->success()
            ->send();

        $this->dispatch('refreshQueues');
    }

    private function callNextQueue(): void
    {
        if (!$this->selectedPolyclinicId) {
            Notification::make()
                ->title('Pilih Poliklinik')
                ->body('Silakan pilih poliklinik terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        $nextQueue = VisitQueue::today()
            ->where('polyclinic_id', $this->selectedPolyclinicId)
            ->whereIn('status', ['waiting', 'skipped'])
            ->orderBy('queue_number')
            ->first();

        if (!$nextQueue) {
            Notification::make()
                ->title('Tidak ada antrian')
                ->body('Tidak ada antrian yang menunggu di poliklinik ini')
                ->info()
                ->send();
            return;
        }

        $nextQueue->markAsCalled('Loket 1');

        Notification::make()
            ->title('Antrian dipanggil')
            ->body("Nomor antrian {$nextQueue->display_number} telah dipanggil ke Loket 1")
            ->success()
            ->send();

        $this->dispatch('refreshQueues');
    }

    public function getCurrentQueue(): ?VisitQueue
    {
        if (!$this->selectedPolyclinicId) {
            return null;
        }

        return VisitQueue::today()
            ->where('polyclinic_id', $this->selectedPolyclinicId)
            ->whereIn('status', ['called', 'in_progress'])
            ->latest('called_at')
            ->first();
    }

    public function getWaitingQueues(): Collection
    {
        if (!$this->selectedPolyclinicId) {
            return collect();
        }

        return VisitQueue::today()
            ->with(['patient'])
            ->where('polyclinic_id', $this->selectedPolyclinicId)
            ->waiting()
            ->orderBy('queue_number')
            ->get();
    }

    public function getSkippedQueues(): Collection
    {
        if (!$this->selectedPolyclinicId) {
            return collect();
        }

        return VisitQueue::today()
            ->with(['patient'])
            ->where('polyclinic_id', $this->selectedPolyclinicId)
            ->where('status', 'skipped')
            ->orderBy('queue_number')
            ->get();
    }

    public function getSelectedPolyclinic(): ?Polyclinic
    {
        if (!$this->selectedPolyclinicId) {
            return null;
        }

        return $this->polyclinics->firstWhere('id', $this->selectedPolyclinicId);
    }
}
