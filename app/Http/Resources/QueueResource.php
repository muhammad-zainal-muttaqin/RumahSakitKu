<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string|null $queue_number
 * @property int $patient_id
 * @property int|null $clinic_id
 * @property int|null $doctor_id
 * @property string|null $status
 * @property bool $is_priority
 * @property Carbon|null $called_at
 * @property Carbon|null $completed_at
 */
class QueueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_number' => $this->queue_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'clinic' => $this->whenLoaded('clinic', fn() => [
                'id' => $this->clinic->id,
                'name' => $this->clinic->name,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'is_priority' => $this->is_priority,
            'room_number' => $this->room_number,
            'called_at' => $this->called_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'skipped_at' => $this->skipped_at?->toIso8601String(),
            'waiting_time' => $this->getWaitingTime(),
            'service_time' => $this->getServiceTime(),
        ];
    }

    /**
     * Get status label.
     *
     * @return string
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'skipped' => 'Dilewati',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    /**
     * Get waiting time in minutes.
     *
     * @return int|null
     */
    private function getWaitingTime(): ?int
    {
        if (!$this->called_at) {
            return $this->created_at ? $this->created_at->diffInMinutes(now()) : null;
        }
        return $this->created_at ? $this->created_at->diffInMinutes($this->called_at) : null;
    }

    /**
     * Get service time in minutes.
     *
     * @return int|null
     */
    private function getServiceTime(): ?int
    {
        if (!$this->called_at) {
            return null;
        }
        
        $endTime = $this->completed_at ?? now();
        return $this->called_at->diffInMinutes($endTime);
    }
}
