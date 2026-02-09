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
 * @property string|null $modality
 * @property string|null $priority
 * @property string|null $status
 * @property string|null $clinical_diagnosis
 * @property Carbon $order_date
 * @property Carbon|null $examination_completed_at
 */
class RadiologyOrderResource extends JsonResource
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
            'examination' => $this->whenLoaded('examination', fn() => [
                'id' => $this->examination->id,
                'name' => $this->examination->name,
                'code' => $this->examination->code,
            ]),
            'modality' => $this->modality,
            'modality_label' => $this->getModalityLabel(),
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'clinical_diagnosis' => $this->clinical_diagnosis,
            'examination_indication' => $this->examination_indication,
            'examination_result' => $this->when($this->status !== 'pending', $this->examination_result),
            'conclusion' => $this->when($this->status !== 'pending', $this->conclusion),
            'suggestion' => $this->suggestion,
            'images_count' => $this->whenCounted('images'),
            'images' => $this->whenLoaded('images', fn() => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->file_path,
                'caption' => $img->caption,
                'uploaded_at' => $img->created_at?->toIso8601String(),
            ])),
            'radiologist' => $this->whenLoaded('radiologist', fn() => [
                'id' => $this->radiologist->id,
                'name' => $this->radiologist->name,
            ]),
            'examination_completed_at' => $this->examination_completed_at?->toIso8601String(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'order_date' => $this->order_date,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get modality label.
     *
     * @return string
     */
    private function getModalityLabel(): string
    {
        return match ($this->modality) {
            'xray' => 'X-Ray',
            'ct' => 'CT Scan',
            'mri' => 'MRI',
            'usg' => 'USG',
            'mammography' => 'Mammografi',
            'dexa' => 'DEXA',
            'fluoroscopy' => 'Fluoroskopi',
            default => $this->modality,
        };
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
