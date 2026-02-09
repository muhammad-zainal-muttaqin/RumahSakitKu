<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $visit_number
 * @property int $patient_id
 * @property int|null $doctor_id
 * @property int|null $clinic_id
 * @property string|null $visit_date
 * @property string|null $status
 * @property bool $is_emergency
 * @property bool $is_bpjs
 * @property string|null $complaint
 * @property Carbon $created_at
 */
class VisitResource extends JsonResource
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
            'visit_number' => $this->visit_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
                'gender' => $this->patient->gender,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
                'nip' => $this->doctor->nip,
            ]),
            'clinic' => $this->whenLoaded('clinic', fn() => [
                'id' => $this->clinic->id,
                'name' => $this->clinic->name,
                'code' => $this->clinic->code,
            ]),
            'visit_type' => $this->whenLoaded('visitType', fn() => [
                'id' => $this->visitType->id,
                'name' => $this->visitType->name,
                'code' => $this->visitType->code,
            ]),
            'visit_date' => $this->visit_date,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'is_emergency' => $this->is_emergency,
            'is_bpjs' => $this->is_bpjs,
            'bpjs_number' => $this->bpjs_number,
            'complaint' => $this->complaint,
            'is_new_patient' => $this->is_new_patient,
            'queue_number' => $this->queue_number,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'checked_out_at' => $this->checked_out_at?->toIso8601String(),
            'medical_record' => $this->whenLoaded('medicalRecord', fn() => [
                'id' => $this->medicalRecord->id,
                'is_finalized' => $this->medicalRecord->is_finalized,
            ]),
            'has_prescription' => $this->whenLoaded('prescriptions', fn() => $this->prescriptions->isNotEmpty()),
            'has_lab_orders' => $this->whenLoaded('labOrders', fn() => $this->labOrders->isNotEmpty()),
            'has_radiology_orders' => $this->whenLoaded('radiologyOrders', fn() => $this->radiologyOrders->isNotEmpty()),
            'created_at' => $this->created_at?->toIso8601String(),
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
            'registered' => 'Terdaftar',
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }
}
