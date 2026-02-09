<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SatuSehatLog;
use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatuSehatLogFactory extends Factory
{
    protected $model = SatuSehatLog::class;

    public function definition(): array
    {
        return [
            'resource_type' => $this->faker->randomElement(['Patient', 'Encounter', 'Observation', 'Condition', 'MedicationRequest']),
            'fhir_id' => $this->faker->optional()->uuid(),
            'local_type' => Patient::class,
            'local_id' => Patient::factory(),
            'action' => $this->faker->randomElement(['CREATE', 'UPDATE', 'DELETE', 'SEARCH']),
            'request_data' => ['resourceType' => 'Patient', 'name' => [['text' => $this->faker->name()]]],
            'response_data' => ['id' => $this->faker->uuid(), 'resourceType' => 'Patient'],
            'status' => 'success',
            'error_message' => null,
            'response_time_ms' => $this->faker->numberBetween(100, 5000),
            'retry_count' => 0,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    public function forResourceType(string $resourceType): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => $resourceType,
        ]);
    }

    public function withFhirId(): static
    {
        return $this->state(fn (array $attributes) => [
            'fhir_id' => $this->faker->uuid(),
        ]);
    }
}
