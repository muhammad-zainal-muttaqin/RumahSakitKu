<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $roomClasses = ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU'];
        $roomClass = $this->faker->randomElement($roomClasses);
        $floor = $this->faker->numberBetween(1, 5);
        $roomNumber = $this->faker->unique()->numberBetween(101, 599);

        return [
            'code' => 'R' . $roomNumber,
            'name' => $roomClass . ' - ' . $roomNumber,
            'room_class' => $roomClass,
            'floor' => $floor,
            'building' => $this->faker->randomElement(['A', 'B', 'C']),
            'gender_preference' => $this->faker->randomElement(['male', 'female', 'any']),
            'total_beds' => $this->faker->numberBetween(1, 6),
            'available_beds' => $this->faker->numberBetween(0, 6),
            'base_price' => $this->faker->randomFloat(2, 100000, 5000000),
            'bpjs_price' => $this->faker->randomFloat(2, 50000, 2500000),
            'facilities' => $this->faker->randomElements(
                ['AC', 'TV', 'Kamar Mandi', 'Lemari', 'Sofa', 'Kulkas', 'WiFi'],
                $this->faker->numberBetween(2, 7)
            ),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function vvip(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_class' => 'VVIP',
            'base_price' => $this->faker->randomFloat(2, 3000000, 5000000),
            'total_beds' => 1,
            'available_beds' => 1,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_class' => 'VIP',
            'base_price' => $this->faker->randomFloat(2, 1500000, 3000000),
            'total_beds' => 1,
            'available_beds' => 1,
        ]);
    }

    public function kelas3(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_class' => 'Kelas III',
            'base_price' => $this->faker->randomFloat(2, 100000, 300000),
            'total_beds' => 6,
            'available_beds' => $this->faker->numberBetween(0, 6),
        ]);
    }

    public function icu(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_class' => 'ICU',
            'base_price' => $this->faker->randomFloat(2, 2500000, 4000000),
            'total_beds' => 2,
            'available_beds' => $this->faker->numberBetween(0, 2),
        ]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_beds' => 0,
        ]);
    }

    public function available(): static
    {
        return $this->state([
            'available_beds' => fn (array $attributes) => $attributes['total_beds'] ?? 0,
        ]);
    }
}
