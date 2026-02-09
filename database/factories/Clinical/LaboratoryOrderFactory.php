<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\LaboratoryOrder;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\MasterData\Employee;
use App\Models\Clinical\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaboratoryOrderFactory extends Factory
{
    protected $model = LaboratoryOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'LAB' . $this->faker->unique()->numerify('########'),
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'doctor_id' => Employee::factory(),
            'medical_record_id' => null,
            'order_date' => now(),
            'priority' => 'normal',
            'status' => 'pending',
            'diagnosis_notes' => $this->faker->optional()->sentence(),
            'clinical_notes' => $this->faker->optional()->sentence(),
            'total_price' => $this->faker->randomFloat(2, 100000, 1000000),
            'is_cito' => false,
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

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
        ]);
    }

    public function cito(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cito' => true,
            'priority' => 'cito',
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }
}
