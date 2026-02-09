<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Procedure;
use App\Models\MasterData\ProcedureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcedureFactory extends Factory
{
    protected $model = Procedure::class;

    public function definition(): array
    {
        return [
            'procedure_code' => 'PROC' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->randomElement([
                'Consultation',
                'General Checkup',
                'Vaccination',
                'Wound Dressing',
                'Injection',
                'ECG',
                'X-Ray',
                'Ultrasound',
                'Laboratory Test',
            ]),
            'category_id' => ProcedureCategory::factory(),
            'base_price' => $this->faker->randomFloat(2, 50000, 2000000),
            'bpjs_tariff' => $this->faker->optional()->randomFloat(2, 50000, 1500000),
            'material_cost' => $this->faker->optional()->randomFloat(2, 10000, 500000),
            'is_bpjs_covered' => $this->faker->boolean(70),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function bpjsCovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bpjs_covered' => true,
        ]);
    }

    public function notBpjsCovered(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bpjs_covered' => false,
            'bpjs_tariff' => null,
        ]);
    }

    public function withCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
