<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\LaboratoryResult;
use App\Models\Clinical\LaboratoryOrder;
use App\Models\MasterData\LabTest;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaboratoryResultFactory extends Factory
{
    protected $model = LaboratoryResult::class;

    public function definition(): array
    {
        return [
            'laboratory_order_id' => LaboratoryOrder::factory(),
            'lab_test_id' => null,
            'result_value' => $this->faker->randomFloat(2, 1, 100),
            'result_text' => null,
            'flag' => 'normal',
            'reference_range' => '10-100',
            'unit' => 'mg/dL',
            'notes' => $this->faker->optional()->sentence(),
            'test_method' => $this->faker->optional()->randomElement(['Chemistry', 'Immunology', 'Hematology']),
            'analyzer_machine' => $this->faker->optional()->randomElement(['Roche', 'Siemens', 'Abbott']),
            'validated_by' => null,
            'validated_at' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function withLabTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'lab_test_id' => LabTest::factory(),
        ]);
    }

    public function withFlag(string $flag): static
    {
        return $this->state(fn (array $attributes) => [
            'flag' => $flag,
        ]);
    }

    public function abnormal(): static
    {
        return $this->state(fn (array $attributes) => [
            'flag' => $this->faker->randomElement(['low', 'high', 'abnormal']),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'flag' => 'critical',
        ]);
    }

    public function validated(?int $validatorId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'validated_by' => $validatorId ?? Employee::factory(),
            'validated_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'result_value' => null,
            'result_text' => null,
            'flag' => null,
        ]);
    }

    public function withNumericResult(
        ?float $value = null,
        string $unit = 'mg/dL',
        string $referenceRange = '10-100'
    ): static
    {
        return $this->state(fn (array $attributes) => [
            'result_value' => $value ?? $this->faker->randomFloat(2, 1, 100),
            'result_text' => null,
            'unit' => $unit,
            'reference_range' => $referenceRange,
        ]);
    }

    public function withTextResult(?string $text = null): static
    {
        return $this->state(fn (array $attributes) => [
            'result_value' => null,
            'result_text' => $text ?? $this->faker->randomElement(['Positive', 'Negative', 'Detected', 'Not Detected']),
        ]);
    }
}
