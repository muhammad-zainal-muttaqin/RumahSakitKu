<?php

declare(strict_types=1);

namespace App\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emergency Alert Event
 * 
 * Broadcasts critical emergency alerts to authorized personnel.
 * High priority event for emergency department notifications.
 */
class EmergencyAlert implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Priority levels for emergency alerts
     */
    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    /**
     * Triage categories
     */
    public const TRIAGE_RESUSCITATION = 'resusitasi';
    public const TRIAGE_EMERGENCY = 'gawat';
    public const TRIAGE_URGENT = 'urgent';
    public const TRIAGE_LESS_URGENT = 'kurang_urgent';
    public const TRIAGE_NOT_URGENT = 'tidak_urgent';

    /**
     * Create a new event instance.
     *
     * @param string $alertType Type of emergency alert
     * @param string $patientIdentifier Patient identifier (MRN or masked name)
     * @param string $triageCategory Triage category
     * @param string $priority Alert priority level
     * @param string $message Alert message/description
     * @param array<string, mixed> $additionalData Additional alert data
     */
    public function __construct(
        public string $alertType,
        public string $patientIdentifier,
        public string $triageCategory,
        public string $priority,
        public string $message,
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
            new Channel('emergency'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'emergency.alert';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'alert_id' => uniqid('emg_', true),
            'alert_type' => $this->alertType,
            'patient_identifier' => $this->patientIdentifier,
            'triage_category' => $this->triageCategory,
            'priority' => $this->priority,
            'message' => $this->message,
            'additional_data' => $this->additionalData,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the priority level for broadcasting.
     * Higher priority events are processed first.
     */
    public function broadcastQueue(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'broadcasts-critical',
            self::PRIORITY_HIGH => 'broadcasts-high',
            default => 'broadcasts',
        };
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return config('broadcasting.default') !== 'null';
    }

    /**
     * Get the color associated with the triage category.
     */
    public function getTriageColor(): string
    {
        return match ($this->triageCategory) {
            self::TRIAGE_RESUSCITATION => 'red',
            self::TRIAGE_EMERGENCY => 'orange',
            self::TRIAGE_URGENT => 'yellow',
            self::TRIAGE_LESS_URGENT => 'green',
            self::TRIAGE_NOT_URGENT => 'blue',
            default => 'gray',
        };
    }
}
