<?php

declare(strict_types=1);

namespace App\Jobs\Queue;

use InvalidArgumentException;
use Exception;
use App\Models\Patient\VisitQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Send Queue Notification Job
 *
 * Job to send notifications to patients when their queue is called.
 * Supports SMS, WhatsApp, and Email channels.
 *
 * @package App\Jobs\Queue
 */
class SendQueueNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     *
     * @param int $queueId The queue ID
     * @param array<string> $channels Notification channels (sms, whatsapp, email)
     */
    public function __construct(
        public int $queueId,
        public array $channels = ['whatsapp']
    ) {
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $queue = VisitQueue::with(['patient', 'polyclinic', 'visit'])->findOrFail($this->queueId);

            Log::info('Starting queue notification', [
                'queue_id' => $this->queueId,
                'queue_number' => $queue->display_number,
                'patient_id' => $queue->patient_id,
                'channels' => $this->channels,
            ]);

            $patient = $queue->patient;
            $notificationData = $this->prepareNotificationData($queue);

            $sentChannels = [];
            $failedChannels = [];

            foreach ($this->channels as $channel) {
                try {
                    $success = match ($channel) {
                        'sms' => $this->sendSms($patient, $notificationData),
                        'whatsapp' => $this->sendWhatsApp($patient, $notificationData),
                        'email' => $this->sendEmail($patient, $notificationData),
                        default => throw new InvalidArgumentException("Unknown channel: {$channel}"),
                    };

                    if ($success) {
                        $sentChannels[] = $channel;
                    } else {
                        $failedChannels[] = $channel;
                    }
                } catch (Exception $e) {
                    $failedChannels[] = $channel;

                    Log::error("Failed to send {$channel} notification", [
                        'queue_id' => $this->queueId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('Queue notification completed', [
                'queue_id' => $this->queueId,
                'sent_channels' => $sentChannels,
                'failed_channels' => $failedChannels,
                'execution_time_seconds' => $executionTime,
            ]);

            // Update queue with notification status
            $queue->update([
                'notification_sent_at' => now(),
                'notification_channels' => $sentChannels,
            ]);
        } catch (Throwable $e) {
            Log::error('Queue notification failed', [
                'queue_id' => $this->queueId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception The exception that caused the failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Queue notification job failed after retries', [
            'queue_id' => $this->queueId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Prepare notification data.
     *
     * @param VisitQueue $queue The queue
     * @return array<string, mixed> The notification data
     */
    private function prepareNotificationData(VisitQueue $queue): array
    {
        return [
            'queue_number' => $queue->display_number,
            'patient_name' => $queue->patient->name,
            'polyclinic_name' => $queue->polyclinic->name,
            'counter_number' => $queue->counter_number,
            'called_at' => $queue->called_at?->format('H:i'),
            'message' => $this->buildNotificationMessage($queue),
        ];
    }

    /**
     * Build notification message.
     *
     * @param VisitQueue $queue The queue
     * @return string The message
     */
    private function buildNotificationMessage(VisitQueue $queue): string
    {
        $polyclinicName = $queue->polyclinic?->name ?? 'Poliklinik';
        $counterNumber = $queue->counter_number ? "Loket {$queue->counter_number}" : 'Loket';

        return "Nomor antrian {$queue->display_number} atas nama {$queue->patient->name} " .
               "silahkan menuju {$polyclinicName} {$counterNumber}. Terima kasih.";
    }

    /**
     * Send SMS notification.
     *
     * @param mixed $patient The patient
     * @param array<string, mixed> $data The notification data
     * @return bool Success status
     */
    private function sendSms($patient, array $data): bool
    {
        if (empty($patient->phone)) {
            Log::warning('Cannot send SMS: Patient has no phone number', [
                'patient_id' => $patient->id,
            ]);
            return false;
        }

        // Implement SMS gateway integration
        // Example using a generic SMS gateway
        $smsGateway = config('services.sms.gateway');
        $apiKey = config('services.sms.api_key');

        if (empty($smsGateway) || empty($apiKey)) {
            Log::warning('SMS gateway not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post($smsGateway, [
                'to' => $patient->phone,
                'message' => $data['message'],
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('SMS sending failed', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send WhatsApp notification.
     *
     * @param mixed $patient The patient
     * @param array<string, mixed> $data The notification data
     * @return bool Success status
     */
    private function sendWhatsApp($patient, array $data): bool
    {
        if (empty($patient->phone)) {
            Log::warning('Cannot send WhatsApp: Patient has no phone number', [
                'patient_id' => $patient->id,
            ]);
            return false;
        }

        // Implement WhatsApp gateway integration (e.g., Twilio, Wablas, etc.)
        $waGateway = config('services.whatsapp.gateway');
        $apiKey = config('services.whatsapp.api_key');

        if (empty($waGateway) || empty($apiKey)) {
            Log::warning('WhatsApp gateway not configured');
            return false;
        }

        try {
            $message = $data['message'];

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
            ])->post($waGateway, [
                'phone' => $this->formatPhoneNumber($patient->phone),
                'message' => $message,
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('WhatsApp sending failed', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email notification.
     *
     * @param mixed $patient The patient
     * @param array<string, mixed> $data The notification data
     * @return bool Success status
     */
    private function sendEmail($patient, array $data): bool
    {
        if (empty($patient->email)) {
            Log::warning('Cannot send email: Patient has no email', [
                'patient_id' => $patient->id,
            ]);
            return false;
        }

        try {
            Mail::raw($data['message'], function ($message) use ($patient, $data) {
                $message->to($patient->email)
                    ->subject("Antrian Dipanggil - {$data['queue_number']}");
            });

            return true;
        } catch (Exception $e) {
            Log::error('Email sending failed', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number for WhatsApp.
     *
     * @param string $phone The phone number
     * @return string The formatted phone number
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if missing
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
