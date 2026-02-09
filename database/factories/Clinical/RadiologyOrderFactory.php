<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\RadiologyOrder;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\MasterData\Employee;
use App\Models\Clinical\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class RadiologyOrderFactory extends Factory
{
    protected $model = RadiologyOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'RAD' . $this->faker->unique()->numerify('########'),
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => Employee::factory(),
            'medical_record_id' => null,
            'examination_type' => $this->faker->randomElement(['xray', 'ct_scan', 'mri', 'usg', 'mammografi']),
            'body_area' => $this->faker->optional()->randomElement(['Chest', 'Abdomen', 'Head', 'Spine', 'Extremities']),
            'position' => $this->faker->optional()->randomElement(['AP', 'PA', 'Lateral', 'Oblique']),
            'contrast' => false,
            'contrast_type' => null,
            'clinical_indication' => $this->faker->optional()->sentence(),
            'scheduled_date' => now()->addHours($this->faker->numberBetween(1, 48)),
            'priority' => 'normal',
            'status' => 'pending',
            'total_price' => $this->faker->randomFloat(2, 500000, 5000000),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reported',
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'emergency',
        ]);
    }

    public function withContrast(): static
    {
        return $this->state(fn (array $attributes) => [
            'contrast' => true,
            'contrast_type' => $this->faker->randomElement(['Iodine', 'Gadolinium', 'Barium']),
        ]);
    }

    public function forExaminationType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'examination_type' => $type,
        ]);
    }

    public function ctScan(): static
    {
        return $this->state(fn (array $attributes) => [
            'examination_type' => 'ct_scan',
        ]);
    }
}
