<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        $classifications = ['obat_bebas', 'obat_bebas_terbatas', 'obat_keras', 'narkotika', 'psikotropik'];
        $dosageForms = ['tablet', 'kapsul', 'sirup', 'injeksi', 'salep', 'krim', 'gel', 'tetes', 'inhaler', 'supositoria'];

        $genericName = ucfirst($this->faker->word());

        return [
            'code' => 'MED' . $this->faker->unique()->numerify('######'),
            'name' => $genericName . ' ' . $this->faker->randomElement(['Acid', 'Sodium', 'Hydrochloride', 'Sulfate']),
            'generic_name' => $genericName,
            'classification' => $this->faker->randomElement($classifications),
            'dosage_form' => $this->faker->randomElement($dosageForms),
            'unit' => $this->faker->randomElement(['tablet', 'kapsul', 'botol', 'tube', 'ampul']),
            'manufacturer' => $this->faker->company(),
            'registration_number' => $this->faker->unique()->numerify('BPOM-########'),
            'is_generic' => $this->faker->boolean(30),
            'stock' => $this->faker->randomFloat(2, 10, 1000),
            'min_stock' => $this->faker->randomFloat(2, 5, 50),
            'selling_price' => $this->faker->randomFloat(2, 1000, 100000),
            'purchase_price' => $this->faker->randomFloat(2, 500, 80000),
            'expired_date' => $this->faker->dateTimeBetween('+1 month', '+3 years')->format('Y-m-d'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 5,
            'min_stock' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
            'min_stock' => 10,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired_date' => $this->faker->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }

    public function generic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_generic' => true,
            'classification' => 'obat_bebas',
        ]);
    }

    public function branded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_generic' => false,
        ]);
    }

    public function narcotic(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'narkotika',
        ]);
    }

    public function psychotropic(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'psikotropik',
        ]);
    }

    public function prescriptionOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'classification' => 'obat_keras',
        ]);
    }

    public function withStock(float $stock, float $minStock): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
            'min_stock' => $minStock,
        ]);
    }
}
