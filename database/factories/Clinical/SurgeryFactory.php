<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\Surgery;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurgeryFactory extends Factory
{
    protected $model = Surgery::class;

    public function definition(): array
    {
        return [
            'surgery_number' => 'SRG' . $this->faker->unique()->numerify('########'),
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'scheduled_date' => now()->addDays($this->faker->numberBetween(1, 30)),
            'start_time' => null,
            'estimated_end_time' => null,
            'actual_start' => null,
            'actual_end' => null,
            'operating_room' => $this->faker->randomElement(['OK1', 'OK2', 'OK3', 'OK_CITO']),
            'surgeon_id' => Employee::factory(),
            'assistant_surgeon_id' => null,
            'anesthesiologist_id' => null,
            'anesthesia_type' => $this->faker->randomElement(['umum', 'spinal', 'lokal', 'sedasi']),
            'nurse_id' => null,
            'circulating_nurse_id' => null,
            'pre_diagnosis' => $this->faker->optional()->sentence(),
            'post_diagnosis' => null,
            'procedure_name' => $this->faker->optional()->randomElement(['Appendectomy', 'Cholecystectomy', 'Hernia Repair', 'Cesarean Section']),
            'procedure_code' => $this->faker->optional()->numerify('PROC###'),
            'total_price' => $this->faker->randomFloat(2, 5000000, 50000000),
            'surgery_type' => 'elektif',
            'status' => 'scheduled',
            'safety_checklist_sign_in' => false,
            'safety_checklist_sign_in_at' => null,
            'safety_checklist_time_out' => false,
            'safety_checklist_time_out_at' => null,
            'safety_checklist_sign_out' => false,
            'safety_checklist_sign_out_at' => null,
            'procedure_notes' => null,
            'findings' => null,
            'complications' => null,
            'specimens' => null,
            'is_postponed' => false,
            'postponed_reason' => null,
            'postponed_at' => null,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
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
            'start_time' => now(),
            'actual_start' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_time' => now()->subHours(2),
            'estimated_end_time' => now(),
            'actual_start' => now()->subHours(2),
            'actual_end' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => 1,
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'surgery_type' => 'emergency',
            'operating_room' => 'OK_CITO',
        ]);
    }

    public function cito(): static
    {
        return $this->state(fn (array $attributes) => [
            'surgery_type' => 'cito',
            'operating_room' => 'OK_CITO',
        ]);
    }

    public function withTeam(): static
    {
        return $this->state(fn (array $attributes) => [
            'assistant_surgeon_id' => Employee::factory(),
            'anesthesiologist_id' => Employee::factory(),
            'nurse_id' => Employee::factory(),
            'circulating_nurse_id' => Employee::factory(),
        ]);
    }
}
