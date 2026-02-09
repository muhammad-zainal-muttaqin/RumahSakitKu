<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Urgent notification for emergency situations.
 * Channels: database, broadcast, mail
 * High priority notification.
 */
class EmergencyAlertNotification extends Notification
{
    use Queueable;

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public function __construct(
        public string $title,
        public string $message,
        public string $priority = self::PRIORITY_HIGH,
        public ?string $location = null,
        public ?string $actionUrl = null,
        public ?array $metadata = null
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast', 'mail'];

        // Add SMS for critical emergencies
        if ($this->priority === self::PRIORITY_CRITICAL && method_exists($notifiable, 'routeNotificationForSms')) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage);

        // Set urgency based on priority
        if ($this->priority === self::PRIORITY_CRITICAL) {
            $mailMessage->error();
        } elseif ($this->priority === self::PRIORITY_HIGH) {
            $mailMessage->warning();
        }

        $mailMessage->subject('🚨 EMERGENCY ALERT: ' . $this->title)
            ->greeting('EMERGENCY ALERT')
            ->line($this->message);

        if ($this->location) {
            $mailMessage->line('**Location:** ' . $this->location);
        }

        if ($this->metadata && !empty($this->metadata)) {
            $mailMessage->line('');
            foreach ($this->metadata as $key => $value) {
                $mailMessage->line('**' . ucfirst($key) . ':** ' . $value);
            }
        }

        if ($this->actionUrl) {
            $mailMessage->action('Respond Now', $this->actionUrl);
        }

        return $mailMessage
            ->line('')
            ->line('This is an automated emergency alert. Please respond immediately.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'emergency_alert',
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'location' => $this->location,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata,
            'created_at' => now()->toDateTimeString(),
            'is_critical' => $this->priority === self::PRIORITY_CRITICAL,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'emergency_alert',
            'notification_id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'location' => $this->location,
            'action_url' => $this->actionUrl,
            'metadata' => $this->metadata,
            'sound' => $this->priority === self::PRIORITY_CRITICAL ? 'emergency' : 'alert',
            'vibrate' => true,
            'require_interaction' => $this->priority === self::PRIORITY_CRITICAL,
        ]);
    }

    /**
     * Send SMS notification for critical emergencies.
     */
    public function toSms(object $notifiable): string
    {
        $location = $this->location ? " at {$this->location}" : '';
        return "🚨 EMERGENCY: {$this->title}{$location}. {$this->message}. Respond immediately.";
    }

    /**
     * Get the priority level for queueing.
     */
    public function priority(): int
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 100,
            self::PRIORITY_HIGH => 75,
            self::PRIORITY_MEDIUM => 50,
            default => 25,
        };
    }
}
