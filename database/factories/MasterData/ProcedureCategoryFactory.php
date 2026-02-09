<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\ProcedureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcedureCategoryFactory extends Factory
{
    protected $model = ProcedureCategory::class;

    public function definition(): array
    {
        return [
            'code' => 'CAT' . $this->faker->unique()->numerify('###'),
            'name' => $this->faker->randomElement([
                'Consultation',
                'Laboratory',
                'Radiology',
                'Pharmacy',
                'Surgery',
                'Emergency',
                'Dental',
                'Maternity',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'color' => $this->faker->optional()->hexColor(),
            'icon' => $this->faker->optional()->randomElement(['heroicon-o-user', 'heroicon-o-beaker', 'heroicon-o-heart']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
