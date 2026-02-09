<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $surgery_number
 * @property int $patient_id
 * @property int $surgeon_id
 * @property int|null $anesthetist_id
 * @property string|null $priority
 * @property string|null $status
 * @property string|null $scheduled_date
 * @property string|null $scheduled_start_time
 * @property string|null $preoperative_diagnosis
 * @property string|null $postoperative_diagnosis
 */
class SurgeryResource extends JsonResource
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
            'surgery_number' => $this->surgery_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'surgeon' => $this->whenLoaded('surgeon', fn() => [
                'id' => $this->surgeon->id,
                'name' => $this->surgeon->name,
            ]),
            'anesthetist' => $this->whenLoaded('anesthetist', fn() => [
                'id' => $this->anesthetist->id,
                'name' => $this->anesthetist->name,
            ]),
            'room' => $this->whenLoaded('room', fn() => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ]),
            'surgery_type' => $this->whenLoaded('surgeryType', fn() => [
                'id' => $this->surgeryType->id,
                'name' => $this->surgeryType->name,
                'code' => $this->surgeryType->code,
            ]),
            'assistants' => $this->whenLoaded('assistants', fn() => $this->assistants->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name,
            ])),
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'anesthesia_type' => $this->anesthesia_type,
            'scheduled_date' => $this->scheduled_date,
            'scheduled_start_time' => $this->scheduled_start_time,
            'estimated_duration' => $this->estimated_duration,
            'actual_start_time' => $this->actual_start_time,
            'actual_end_time' => $this->actual_end_time,
            'actual_duration' => $this->when($this->actual_start_time && $this->actual_end_time, function () {
                // Calculate actual duration
                return null; // Implement calculation
            }),
            'preoperative_diagnosis' => $this->preoperative_diagnosis,
            'planned_procedure' => $this->planned_procedure,
            'postoperative_diagnosis' => $this->when($this->status !== 'scheduled', $this->postoperative_diagnosis),
            'actual_procedure' => $this->when($this->status !== 'scheduled', $this->actual_procedure),
            'findings' => $this->when($this->status === 'completed', $this->findings),
            'complications' => $this->complications,
            'patient_condition' => $this->patient_condition,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get priority label.
     *
     * @return string
     */
    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            'elective' => 'Elektif',
            'urgent' => 'Urgent',
            'emergency' => 'Emergency',
            default => $this->priority,
        };
    }

    /**
     * Get status label.
     *
     * @return string
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            'scheduled' => 'Dijadwalkan',
            'confirmed' => 'Dikonfirmasi',
            'in_progress' => 'Sedang Berlangsung',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
