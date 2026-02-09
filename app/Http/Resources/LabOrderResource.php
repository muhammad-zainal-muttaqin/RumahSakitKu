<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $order_number
 * @property int $patient_id
 * @property int $doctor_id
 * @property string|null $priority
 * @property string|null $status
 * @property string|null $clinical_diagnosis
 * @property Carbon $order_date
 * @property Carbon|null $completed_at
 * @property Carbon|null $validated_at
 */
class LabOrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'visit' => $this->whenLoaded('visit', fn() => [
                'id' => $this->visit->id,
                'visit_number' => $this->visit->visit_number,
            ]),
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'clinical_diagnosis' => $this->clinical_diagnosis,
            'specimen_type' => $this->specimen_type,
            'order_date' => $this->order_date,
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'test' => $item->test ? [
                    'id' => $item->test->id,
                    'name' => $item->test->name,
                    'code' => $item->test->code,
                    'normal_range' => $item->test->normal_range,
                    'unit' => $item->test->unit,
                ] : null,
                'result_value' => $item->result_value,
                'result_flag' => $item->result_flag,
                'status' => $item->status,
                'results' => $item->whenLoaded('results', fn() => $item->results->map(fn($r) => [
                    'value' => $r->value,
                    'unit' => $r->unit,
                    'flag' => $r->flag,
                    'tested_at' => $r->tested_at?->toIso8601String(),
                ])),
            ])),
            'validator' => $this->whenLoaded('validator', fn() => [
                'id' => $this->validator->id,
                'name' => $this->validator->name,
            ]),
            'validated_at' => $this->validated_at?->toIso8601String(),
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
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'cito' => 'CITO',
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
            'pending' => 'Menunggu',
            'in_progress' => 'Sedang Dikerjakan',
            'completed' => 'Selesai',
            'validated' => 'Tervalidasi',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
