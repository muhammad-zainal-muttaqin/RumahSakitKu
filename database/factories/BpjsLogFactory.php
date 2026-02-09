<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BpjsLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BpjsLogFactory extends Factory
{
    protected $model = BpjsLog::class;

    public function definition(): array
    {
        return [
            'service_type' => $this->faker->randomElement(['vclaim', 'pcare', 'apotek', 'jkn']),
            'endpoint' => '/api/' . $this->faker->randomElement(['peserta', 'sep', 'rujukan', 'monitoring']),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_data' => json_encode(['no_kartu' => $this->faker->numerize('000#############')]),
            'response_data' => json_encode(['metaData' => ['code' => '200', 'message' => 'OK']]),
            'http_status' => 200,
            'error_message' => null,
            'execution_time_ms' => $this->faker->randomFloat(2, 100, 5000),
            'user_id' => User::factory(),
            'executed_at' => now(),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status' => 200,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status' => $this->faker->randomElement([400, 401, 403, 404, 500, 503]),
            'error_message' => $this->faker->sentence(),
        ]);
    }

    public function withServiceType(string $serviceType): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => $serviceType,
        ]);
    }

    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_time_ms' => $this->faker->randomFloat(2, 5000, 10000),
        ]);
    }
}
