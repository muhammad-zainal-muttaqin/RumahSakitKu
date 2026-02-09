<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'bed_number' => $this->faker->unique()->numerify('B###'),
            'bed_name' => 'Bed ' . $this->faker->numberBetween(1, 10),
            'bed_type' => $this->faker->randomElement(['standard', 'electric', 'manual', 'baby', 'icu']),
            'status' => 'kosong',
            'current_visit_id' => null,
            'occupied_at' => null,
            'vacated_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'kosong',
            'current_visit_id' => null,
            'occupied_at' => null,
        ]);
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terisi',
            'current_visit_id' => Visit::factory(),
            'occupied_at' => now()->subDays($this->faker->numberBetween(1, 10)),
            'vacated_at' => null,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
            'current_visit_id' => null,
            'occupied_at' => null,
            'notes' => 'Under maintenance',
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
            'current_visit_id' => null,
            'occupied_at' => null,
        ]);
    }

    public function cleaning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cleaning',
            'current_visit_id' => null,
            'occupied_at' => null,
            'vacated_at' => now()->subMinutes(30),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function electric(): static
    {
        return $this->state(fn (array $attributes) => [
            'bed_type' => 'electric',
        ]);
    }

    public function icu(): static
    {
        return $this->state(fn (array $attributes) => [
            'bed_type' => 'icu',
        ]);
    }
}
