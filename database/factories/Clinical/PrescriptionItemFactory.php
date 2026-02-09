<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\Prescription;
use App\Models\Clinical\PrescriptionItem;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionItemFactory extends Factory
{
    protected $model = PrescriptionItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 30);
        $unitPrice = $this->faker->randomFloat(2, 1000, 50000);

        return [
            'prescription_id' => Prescription::factory(),
            'medicine_id' => Medicine::factory(),
            'generic_name' => $this->faker->word() . ' ' . $this->faker->randomElement(['Acid', 'Sodium', 'Hydrochloride']),
            'brand_name' => $this->faker->optional()->company(),
            'dosage_form' => $this->faker->randomElement(['tablet', 'kapsul', 'sirup', 'injeksi']),
            'strength' => $this->faker->randomElement(['500mg', '250mg', '10mg', '5ml']),
            'quantity' => $quantity,
            'unit' => $this->faker->randomElement(['tablet', 'kapsul', 'botol', 'ampul']),
            'dosage_instructions' => $this->faker->randomElement(['3x1', '2x1', '1x1']),
            'frequency' => $this->faker->randomElement(['Sehari 3 kali', 'Sehari 2 kali', 'Sehari 1 kali']),
            'duration_days' => $this->faker->numberBetween(3, 14),
            'route_of_administration' => $this->faker->randomElement(['oral', 'injeksi', 'topikal']),
            'instructions' => $this->faker->optional()->sentence(),
            'is_substitutable' => $this->faker->boolean(30),
            'substitution_notes' => $this->faker->optional()->sentence(),
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'is_dispensed' => false,
            'dispensed_quantity' => null,
            'dispensed_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function dispensed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dispensed' => true,
            'dispensed_quantity' => $attributes['quantity'],
            'dispensed_at' => now(),
        ]);
    }

    public function partiallyDispensed(float $dispensedQty): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dispensed' => true,
            'dispensed_quantity' => $dispensedQty,
            'dispensed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dispensed' => false,
            'dispensed_quantity' => null,
            'dispensed_at' => null,
        ]);
    }

    public function substitutable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_substitutable' => true,
        ]);
    }

    public function notSubstitutable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_substitutable' => false,
        ]);
    }

    public function withPrice(float $unitPrice, int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
        ]);
    }
}
