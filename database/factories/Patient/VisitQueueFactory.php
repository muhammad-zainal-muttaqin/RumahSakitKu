<?php

declare(strict_types=1);

namespace Database\Factories\Patient;

use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\Patient\VisitQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitQueueFactory extends Factory
{
    protected $model = VisitQueue::class;

    public function definition(): array
    {
        $queueNumber = $this->faker->numberBetween(1, 999);

        return [
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'polyclinic_id' => Polyclinic::factory(),
            'queue_number' => $queueNumber,
            'display_number' => fn (array $attributes) => str_pad((string) ($attributes['queue_number'] ?? $queueNumber), 3, '0', STR_PAD_LEFT),
            'status' => 'waiting',
            'called_at' => null,
            'completed_at' => null,
            'counter_number' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
            'called_at' => null,
            'completed_at' => null,
        ]);
    }

    public function called(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'called',
            'called_at' => now(),
            'counter_number' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'called_at' => now()->subMinutes(5),
            'counter_number' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'called_at' => now()->subMinutes(30),
            'completed_at' => now(),
            'counter_number' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'skipped',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
        ]);
    }
}
