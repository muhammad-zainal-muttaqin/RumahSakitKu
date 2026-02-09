<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property int $visit_id
 * @property int $patient_id
 * @property int $doctor_id
 * @property string|null $chief_complaint
 * @property string|null $present_illness_history
 * @property string|null $physical_examination
 * @property string|null $assessment
 * @property string|null $plan
 * @property string|null $icd10_code
 * @property string|null $icd9_code
 * @property bool $is_finalized
 * @property Carbon|null $finalized_at
 */
class MedicalRecordResource extends JsonResource
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
            'visit' => $this->whenLoaded('visit', fn() => [
                'id' => $this->visit->id,
                'visit_number' => $this->visit->visit_number,
                'visit_date' => $this->visit->visit_date,
            ]),
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn() => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
                'nip' => $this->doctor->nip,
            ]),
            'chief_complaint' => $this->chief_complaint,
            'present_illness_history' => $this->present_illness_history,
            'past_medical_history' => $this->past_medical_history,
            'family_history' => $this->family_history,
            'allergy_history' => $this->allergy_history,
            'vital_signs' => $this->vital_signs,
            'physical_examination' => $this->physical_examination,
            'supporting_examination' => $this->supporting_examination,
            'assessment' => $this->assessment,
            'plan' => $this->plan,
            'instructions' => $this->instructions,
            'icd10' => $this->whenLoaded('icd10', fn() => [
                'code' => $this->icd10->code,
                'name' => $this->icd10->name,
            ]),
            'icd9' => $this->whenLoaded('icd9', fn() => [
                'code' => $this->icd9->code,
                'name' => $this->icd9->name,
            ]),
            'is_finalized' => $this->is_finalized,
            'finalized_at' => $this->finalized_at?->toIso8601String(),
            'cppts_count' => $this->whenCounted('cppts'),
            'prescriptions_count' => $this->whenCounted('prescriptions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
