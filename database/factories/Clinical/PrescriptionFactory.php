<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    protected $model = Prescription::class;

    public function definition(): array
    {
        return [
            'prescription_number' => 'RX' . $this->faker->unique()->numerify('########'),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'medical_record_id' => MedicalRecord::factory(),
            'prescription_date' => $this->faker->dateTimeBetween('-30 days', 'today')->format('Y-m-d'),
            'prescription_type' => $this->faker->randomElement(['regular', 'emergency', 'compound']),
            'priority' => $this->faker->randomElement(['normal', 'urgent']),
            'status' => $this->faker->randomElement(['pending', 'verified', 'dispensed', 'cancelled']),
            'clinical_indication' => $this->faker->optional()->sentence(),
            'allergies' => $this->faker->optional()->sentence(),
            'prescribed_by' => Employee::factory()->doctor(),
            'verified_by_pharmacist' => false,
            'verified_at' => null,
            'dispensed_at' => null,
            'dispensed_by' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_by_pharmacist' => false,
            'dispensed_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dispensed',
            'verified_by_pharmacist' => true,
            'verified_at' => now()->subMinutes(30),
            'dispensed_at' => now(),
            'dispensed_by' => Employee::factory()->pharmacist(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'prescription_type' => 'emergency',
            'priority' => 'urgent',
        ]);
    }

    public function readyForDispensing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_by_pharmacist' => true,
            'verified_at' => now(),
        ]);
    }
}
