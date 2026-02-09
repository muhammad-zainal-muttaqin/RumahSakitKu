<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $prescription_number
 * @property int $patient_id
 * @property int $doctor_id
 * @property int|null $medical_record_id
 * @property string|null $type
 * @property string|null $status
 * @property string|null $notes
 * @property Carbon $created_at
 */
class PrescriptionResource extends JsonResource
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
            'prescription_number' => $this->prescription_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'medical_record' => $this->whenLoaded('medicalRecord', fn() => [
                'id' => $this->medicalRecord->id,
            ]),
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'medicine' => $item->medicine ? [
                    'id' => $item->medicine->id,
                    'name' => $item->medicine->name,
                    'code' => $item->medicine->code,
                    'unit' => $item->medicine->unit,
                ] : null,
                'quantity' => $item->quantity,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'duration' => $item->duration,
                'instructions' => $item->instructions,
                'status' => $item->status,
                'processed_quantity' => $item->processed_quantity,
            ])),
            'total_items' => $this->whenCounted('items'),
            'verifier' => $this->whenLoaded('verifier', fn() => [
                'id' => $this->verifier->id,
                'name' => $this->verifier->name,
            ]),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'processor' => $this->whenLoaded('processor', fn() => [
                'id' => $this->processor->id,
                'name' => $this->processor->name,
            ]),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'dispenser' => $this->whenLoaded('dispenser', fn() => [
                'id' => $this->dispenser->id,
                'name' => $this->dispenser->name,
            ]),
            'dispensed_at' => $this->dispensed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get type label.
     *
     * @return string
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'regular' => 'Reguler',
            'narcotic' => 'Narkotika',
            'psychotropic' => 'Psikotropika',
            'compound' => 'Racikan',
            default => $this->type,
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
            'verified' => 'Diverifikasi',
            'processed' => 'Diproses',
            'dispensed' => 'Ditebus',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
