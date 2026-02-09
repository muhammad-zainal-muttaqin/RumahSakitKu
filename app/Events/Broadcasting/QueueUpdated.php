<?php

declare(strict_types=1);

namespace App\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Queue Updated Event
 * 
 * Broadcasts queue display updates to specific polyclinic channels.
 * Used for real-time queue display screens.
 */
class QueueUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param int $polyclinicId The polyclinic ID
     * @param string $queueNumber The queue number being updated
     * @param string $status The new status (waiting, called, completed, cancelled)
     * @param string|null $counter The counter/room number (e.g., "A1", "B2")
     * @param string|null $patientName The patient name (optional, masked for privacy)
     * @param int|null $waitingCount Number of patients still waiting
     */
    public function __construct(
        public int $polyclinicId,
        public string $queueNumber,
        public string $status,
        public ?string $counter = null,
        public ?string $patientName = null,
        public ?int $waitingCount = null
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
            new Channel('polyclinic.' . $this->polyclinicId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'polyclinic_id' => $this->polyclinicId,
            'queue_number' => $this->queueNumber,
            'status' => $this->status,
            'counter' => $this->counter,
            'patient_name' => $this->patientName,
            'waiting_count' => $this->waitingCount,
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
}
