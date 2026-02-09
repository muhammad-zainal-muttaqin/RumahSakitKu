<?php

declare(strict_types=1);

namespace App\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Surgery Status Updated Event
 * 
 * Broadcasts real-time surgery status updates to the surgery team.
 * Tracks surgery progress from scheduling to completion.
 */
class SurgeryStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Surgery status constants
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EMERGENCY = 'emergency';

    /**
     * Create a new event instance.
     *
     * @param int $surgeryId The surgery ID
     * @param string $status The new surgery status
     * @param string|null $room Operating room identifier
     * @param string|null $previousStatus Previous status for tracking transitions
     * @param array<string, mixed> $additionalData Additional surgery data
     */
    public function __construct(
        public int $surgeryId,
        public string $status,
        public ?string $room = null,
        public ?string $previousStatus = null,
        public array $additionalData = []
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
            new Channel('surgery.' . $this->surgeryId),
            new Channel('surgery.updates'), // General surgery updates channel
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'surgery.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'surgery_id' => $this->surgeryId,
            'status' => $this->status,
            'previous_status' => $this->previousStatus,
            'room' => $this->room,
            'timestamp' => now()->toIso8601String(),
            'duration_minutes' => $this->additionalData['duration_minutes'] ?? null,
            'surgeon_name' => $this->additionalData['surgeon_name'] ?? null,
            'patient_identifier' => $this->additionalData['patient_identifier'] ?? null,
            'surgery_type' => $this->additionalData['surgery_type'] ?? null,
            'notes' => $this->additionalData['notes'] ?? null,
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
     * Get the color associated with the surgery status.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_EMERGENCY => 'danger',
            self::STATUS_ON_HOLD => 'warning',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_PREPARING => 'info',
            self::STATUS_SCHEDULED => 'secondary',
            default => 'gray',
        };
    }

    /**
     * Get the display label for the surgery status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'Sedang Berlangsung',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_EMERGENCY => 'Darurat',
            self::STATUS_ON_HOLD => 'Ditunda',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_PREPARING => 'Persiapan',
            self::STATUS_SCHEDULED => 'Terjadwal',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
