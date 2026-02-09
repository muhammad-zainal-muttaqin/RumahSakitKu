<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\SurgeryImplant;
use App\Models\Clinical\Surgery;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurgeryImplantFactory extends Factory
{
    protected $model = SurgeryImplant::class;

    public function definition(): array
    {
        return [
            'surgery_id' => Surgery::factory(),
            'implant_name' => $this->faker->randomElement(['Hip Implant', 'Knee Implant', 'Bone Plate', 'Surgical Screw', 'Cardiac Stent']),
            'implant_type' => $this->faker->randomElement(['prosthetic', 'orthopedic', 'cardiac', 'vascular']),
            'serial_number' => $this->faker->optional()->uuid(),
            'batch_number' => $this->faker->optional()->numerify('BATCH####'),
            'manufacturer' => $this->faker->optional()->company(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'unit' => $this->faker->randomElement(['pcs', 'set', 'pair']),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+1 year', '+3 years'),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function prosthetic(): static
    {
        return $this->state(fn (array $attributes) => [
            'implant_type' => 'prosthetic',
            'implant_name' => $this->faker->randomElement(['Hip Prosthesis', 'Knee Prosthesis', 'Shoulder Prosthesis']),
        ]);
    }

    public function orthopedic(): static
    {
        return $this->state(fn (array $attributes) => [
            'implant_type' => 'orthopedic',
            'implant_name' => $this->faker->randomElement(['Bone Plate', 'Surgical Screw', 'Intramedullary Nail']),
        ]);
    }

    public function withExpiryDate(string|DateTimeInterface|null $expiryDate = null): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $expiryDate ?? $this->faker->dateTimeBetween('+1 year', '+3 years'),
        ]);
    }

    public function withSerialNumber(?string $serialNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'serial_number' => $serialNumber ?? $this->faker->uuid(),
        ]);
    }
}
