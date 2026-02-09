<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\LabTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabTestFactory extends Factory
{
    protected $model = LabTest::class;

    public function definition(): array
    {
        $categories = ['hematologi', 'kimia_darah', 'urinalisa', 'mikrobiologi', 'imunologi', 'serologi'];
        $category = $this->faker->randomElement($categories);

        return [
            'test_code' => 'LAB' . $this->faker->unique()->numerify('###'),
            'name' => $this->faker->randomElement(['Hemoglobin', 'Leukosit', 'Trombosit', 'Glukosa', 'Kolesterol', 'Trigliserida', 'Asam Urat', 'Ureum', 'Kreatinin']),
            'category' => $category,
            'specimen_type' => $this->faker->randomElement(['darah', 'urine', 'feses']),
            'reference_value' => $this->faker->randomElement(['12-16 g/dL', '4.5-11.0 x10^9/L', '150-400 x10^9/L', '< 200 mg/dL']),
            'unit' => $this->faker->randomElement(['g/dL', 'mg/dL', 'mmol/L', 'x10^9/L']),
            'base_price' => $this->faker->randomFloat(2, 50000, 500000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    public function withSpecimenType(string $specimenType): static
    {
        return $this->state(fn (array $attributes) => [
            'specimen_type' => $specimenType,
        ]);
    }
}
