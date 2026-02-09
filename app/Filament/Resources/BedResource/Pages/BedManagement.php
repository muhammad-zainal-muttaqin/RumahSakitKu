<?php

declare(strict_types=1);

namespace App\Filament\Resources\BedResource\Pages;

use App\Filament\Resources\BedResource;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;

class BedManagement extends Page
{
    protected static string $resource = BedResource::class;

    protected string $view = 'filament.resources.bed-resource.pages.bed-management';

    protected static ?string $title = 'Manajemen Tempat Tidur';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Peta Tempat Tidur';

    protected static ?int $navigationSort = 5;

    #[Url]
    public ?string $selectedRoom = null;

    #[Url]
    public ?string $selectedFloor = null;

    public function getRoomsProperty()
    {
        $query = Room::where('is_active', true)
            ->with(['beds' => fn ($q) => $q->where('is_active', true)]);

        if ($this->selectedFloor) {
            $query->where('floor', $this->selectedFloor);
        }

        return $query->get();
    }

    public function getFloorsProperty()
    {
        return Room::distinct()
            ->whereNotNull('floor')
            ->pluck('floor', 'floor')
            ->toArray();
    }

    public function getBedStatsProperty(): array
    {
        $beds = Bed::where('is_active', true);

        if ($this->selectedRoom) {
            $beds->where('room_id', $this->selectedRoom);
        }

        return [
            'total' => $beds->count(),
            'kosong' => (clone $beds)->where('status', 'kosong')->count(),
            'terisi' => (clone $beds)->where('status', 'terisi')->count(),
            'reserved' => (clone $beds)->where('status', 'reserved')->count(),
            'maintenance' => (clone $beds)->where('status', 'maintenance')->count(),
            'cleaning' => (clone $beds)->where('status', 'cleaning')->count(),
        ];
    }

    public function occupyBed(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || $bed->status !== 'kosong') {
            Notification::make()
                ->title('Tempat tidur tidak tersedia')
                ->danger()
                ->send();
            return;
        }

        // Redirect to create inpatient form with pre-selected bed
        $this->redirect(route('filament.admin.resources.inpatients.create', ['bed_id' => $bedId]));
    }

    public function vacateBed(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || $bed->status !== 'terisi') {
            Notification::make()
                ->title('Tempat tidur tidak terisi')
                ->danger()
                ->send();
            return;
        }

        $bed->vacate();
        
        Notification::make()
            ->title('Tempat tidur berhasil dikosongkan')
            ->success()
            ->send();
    }

    public function setCleaning(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || $bed->status === 'terisi') {
            Notification::make()
                ->title('Tidak dapat mengubah status')
                ->danger()
                ->send();
            return;
        }

        $bed->setCleaning();
        
        Notification::make()
            ->title('Status berhasil diubah ke Cleaning')
            ->success()
            ->send();
    }

    public function setMaintenance(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || $bed->status === 'terisi') {
            Notification::make()
                ->title('Tidak dapat mengubah status')
                ->danger()
                ->send();
            return;
        }

        $bed->setMaintenance('Maintenance oleh admin');
        
        Notification::make()
            ->title('Status berhasil diubah ke Maintenance')
            ->success()
            ->send();
    }

    public function setAvailable(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || in_array($bed->status, ['terisi', 'kosong'])) {
            Notification::make()
                ->title('Tidak dapat mengubah status')
                ->danger()
                ->send();
            return;
        }

        $bed->status = 'kosong';
        $bed->save();
        
        Notification::make()
            ->title('Tempat tidur sekarang tersedia')
            ->success()
            ->send();
    }

    public function setReserved(int $bedId): void
    {
        $bed = Bed::find($bedId);
        
        if (!$bed || $bed->status !== 'kosong') {
            Notification::make()
                ->title('Tempat tidur tidak tersedia')
                ->danger()
                ->send();
            return;
        }

        $bed->setReserved();
        
        Notification::make()
            ->title('Tempat tidur berhasil dipesan')
            ->success()
            ->send();
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'kosong' => 'success',
            'terisi' => 'danger',
            'reserved' => 'warning',
            'maintenance' => 'gray',
            'cleaning' => 'info',
            default => 'gray',
        };
    }

    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'kosong' => 'heroicon-o-check-circle',
            'terisi' => 'heroicon-o-user',
            'reserved' => 'heroicon-o-clock',
            'maintenance' => 'heroicon-o-wrench',
            'cleaning' => 'heroicon-o-sparkles',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            'kosong' => 'Kosong',
            'terisi' => 'Terisi',
            'reserved' => 'Dipesan',
            'maintenance' => 'Maintenance',
            'cleaning' => 'Cleaning',
            default => ucfirst($status),
        };
    }

    public function getRoomClassColor(string $class): string
    {
        return match ($class) {
            'VVIP' => 'danger',
            'VIP' => 'warning',
            'Kelas I' => 'primary',
            'Kelas II' => 'info',
            'Kelas III' => 'success',
            'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
            default => 'gray',
        };
    }
}
