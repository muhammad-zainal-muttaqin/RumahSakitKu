<?php

declare(strict_types=1);

namespace App\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Inpatient Bed Updated Event
 * 
 * Broadcasts real-time bed occupancy updates for inpatient management.
 * Tracks bed status changes across all rooms.
 */
class InpatientBedUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Bed status change types
     */
    public const CHANGE_ADMISSION = 'admission';
    public const CHANGE_DISCHARGE = 'discharge';
    public const CHANGE_TRANSFER = 'transfer';
    public const CHANGE_RESERVATION = 'reservation';
    public const CHANGE_MAINTENANCE = 'maintenance';
    public const CHANGE_CLEANING = 'cleaning';
    public const CHANGE_AVAILABLE = 'available';

    /**
     * Create a new event instance.
     *
     * @param int $roomId The room ID
     * @param int $bedId The bed ID
     * @param string $changeType Type of bed status change
     * @param string $previousStatus Previous bed status
     * @param string $newStatus New bed status
     * @param int|null $visitId Associated visit ID (if occupied)
     * @param array<string, mixed> $roomSummary Summary of room occupancy
     */
    public function __construct(
        public int $roomId,
        public int $bedId,
        public string $changeType,
        public string $previousStatus,
        public string $newStatus,
        public ?int $visitId = null,
        public array $roomSummary = []
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('inpatient'),
            new Channel('rooms.occupancy'),
            new Channel('room.' . $this->roomId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'bed.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'bed_id' => $this->bedId,
            'change_type' => $this->changeType,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'visit_id' => $this->visitId,
            'room_summary' => $this->roomSummary,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }

    /**
     * Get the color associated with the bed status.
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'kosong', 'available' => 'success',
            'terisi', 'occupied' => 'danger',
            'reserved' => 'warning',
            'maintenance' => 'gray',
            'cleaning' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get the display label for the bed status.
     */
    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'kosong', 'available' => 'Kosong',
            'terisi', 'occupied' => 'Terisi',
            'reserved' => 'Dipesan',
            'maintenance' => 'Maintenance',
            'cleaning' => 'Dibersihkan',
            default => ucfirst($status),
        };
    }

    /**
     * Create a summary event for room occupancy.
     *
     * @param int $roomId
     * @param array<string, mixed> $summary
     * @return static
     */
    public static function roomSummary(int $roomId, array $summary): static
    {
        return new static(
            roomId: $roomId,
            bedId: 0,
            changeType: 'summary_update',
            previousStatus: '',
            newStatus: '',
            roomSummary: $summary
        );
    }
}
