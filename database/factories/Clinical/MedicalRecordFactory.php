<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\MedicalRecord;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'record_number' => 'MR' . $this->faker->unique()->numerify('########'),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'visit_date' => now()->toDateString(),
            'subjective' => null,
            'objective' => null,
            'assessment' => null,
            'plan' => null,
            'diagnosis_primary' => null,
            'diagnosis_secondary' => null,
            'icd10_code' => null,
            'icd10_description' => null,
            'procedure_code' => null,
            'procedure_description' => null,
            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,
            'record_type' => $this->faker->randomElement(['rawat_jalan', 'rawat_inap', 'igd', 'mcu']),
            'status' => $this->faker->randomElement(['draft', 'completed', 'locked']),
            'created_by' => User::factory(),
            'completed_by' => null,
            'completed_at' => null,
            'updated_by' => null,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function finalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => true,
            'finalized_at' => now(),
            'finalized_by' => User::factory(),
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => User::factory(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_finalized' => false,
            'finalized_at' => null,
            'finalized_by' => null,
            'status' => 'draft',
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
        ]);
    }

    public function withDiagnosis(?string $primary = null, ?string $secondary = null): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnosis_primary' => $primary ?? 'General diagnosis',
            'diagnosis_secondary' => $secondary,
        ]);
    }

    public function withICD10(?string $code = null, ?string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'icd10_code' => $code ?? 'Z00',
            'icd10_description' => $description ?? 'General examination',
        ]);
    }
}
