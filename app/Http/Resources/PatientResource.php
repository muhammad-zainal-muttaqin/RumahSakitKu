<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property string|null $medical_record_number
 * @property string|null $nik
 * @property string|null $bpjs_number
 * @property string|null $birth_date
 * @property string|null $gender
 * @property string|null $blood_type
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PatientResource extends JsonResource
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
            'name' => $this->name,
            'medical_record_number' => $this->medical_record_number,
            'nik' => $this->when($request->user()?->can('patient.view_sensitive'), $this->nik),
            'bpjs_number' => $this->when($request->user()?->can('patient.view_sensitive'), $this->bpjs_number),
            'birth_date' => $this->birth_date,
            'age' => $this->birth_date ? now()->diffInYears($this->birth_date) : null,
            'gender' => $this->gender,
            'gender_label' => $this->getGenderLabel(),
            'blood_type' => $this->blood_type,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'region' => $this->whenLoaded('region', fn() => [
                'id' => $this->region->id,
                'name' => $this->region->name,
            ]),
            'insurance' => $this->whenLoaded('insurance', fn() => [
                'id' => $this->insurance->id,
                'name' => $this->insurance->name,
                'number' => $this->insurance->pivot?->number,
            ]),
            'is_active' => $this->is_active,
            'visits_count' => $this->whenCounted('visits'),
            'last_visit' => $this->whenLoaded('visits', fn() => $this->visits->first()?->visit_date),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get gender label.
     *
     * @return string|null
     */
    private function getGenderLabel(): ?string
    {
        return match ($this->gender) {
            'male' => 'Laki-laki',
            'female' => 'Perempuan',
            default => null,
        };
    }
}
