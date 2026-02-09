<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\RadiologyResult;
use App\Models\Clinical\RadiologyOrder;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class RadiologyResultFactory extends Factory
{
    protected $model = RadiologyResult::class;

    public function definition(): array
    {
        return [
            'radiology_order_id' => RadiologyOrder::factory(),
            'result_images' => null,
            'report_text' => $this->faker->optional()->paragraph(),
            'conclusion' => $this->faker->optional()->sentence(),
            'recommendation' => $this->faker->optional()->sentence(),
            'radiologist_id' => null,
            'reported_at' => null,
            'technician_notes' => $this->faker->optional()->sentence(),
            'exposure_parameters' => null,
            'dose_info' => $this->faker->optional()->randomElement(['2 mSv', '5 mSv', '10 mSv']),
            'quality_assurance' => $this->faker->optional()->randomElement(['Good', 'Acceptable', 'Suboptimal']),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function reported(?int $radiologistId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'radiologist_id' => $radiologistId ?? Employee::factory(),
            'reported_at' => now(),
            'report_text' => $this->faker->paragraph(),
            'conclusion' => $this->faker->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'radiologist_id' => null,
            'reported_at' => null,
            'report_text' => null,
            'conclusion' => null,
        ]);
    }

    public function withImages(?array $images = null): static
    {
        return $this->state(fn (array $attributes) => [
            'result_images' => $images ?? [
                'radiology/' . $this->faker->uuid . '.jpg',
                'radiology/' . $this->faker->uuid . '.jpg',
            ],
        ]);
    }

    public function withRadiologist(): static
    {
        return $this->state(fn (array $attributes) => [
            'radiologist_id' => Employee::factory(),
        ]);
    }

    public function withReport(
        ?string $reportText = null,
        ?string $conclusion = null,
        ?string $recommendation = null
    ): static
    {
        return $this->state(fn (array $attributes) => [
            'report_text' => $reportText ?? $this->faker->paragraph(),
            'conclusion' => $conclusion ?? $this->faker->sentence(),
            'recommendation' => $recommendation ?? $this->faker->sentence(),
        ]);
    }
}
