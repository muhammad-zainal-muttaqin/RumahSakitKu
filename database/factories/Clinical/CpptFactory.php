<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\Cppt;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CpptFactory extends Factory
{
    protected $model = Cppt::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'cppt_date' => $this->faker->dateTimeBetween('-30 days', 'today')->format('Y-m-d'),
            'cppt_time' => $this->faker->dateTimeBetween('-30 days', 'today'),
            'subjective' => $this->faker->paragraph(),
            'objective' => $this->faker->paragraph(),
            'assessment' => $this->faker->paragraph(),
            'plan' => $this->faker->paragraph(),
            'instruction' => $this->faker->optional()->paragraph(),
            'progress_notes' => $this->faker->optional()->paragraph(),
            'verified_by' => null,
            'verified_at' => null,
            'is_verified' => false,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => Employee::factory(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function withSOAP(string $subjective, string $objective, string $assessment, string $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'subjective' => $subjective,
            'objective' => $objective,
            'assessment' => $assessment,
            'plan' => $plan,
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'cppt_date' => today()->format('Y-m-d'),
        ]);
    }
}
