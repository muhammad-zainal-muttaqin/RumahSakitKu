<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_type' => 'user',
            'patient_id' => null,
            'auditable_type' => Patient::class,
            'auditable_id' => Patient::factory(),
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted', 'restored']),
            'old_values' => null,
            'new_values' => ['name' => $this->faker->name()],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'created_at' => now(),
        ];
    }

    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'created',
            'old_values' => null,
            'new_values' => ['name' => $this->faker->name()],
        ]);
    }

    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'updated',
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'deleted',
            'old_values' => ['name' => $this->faker->name()],
            'new_values' => null,
        ]);
    }

    public function restored(): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => 'restored',
            'old_values' => null,
            'new_values' => null,
        ]);
    }

    public function forPatient(int $patientId): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $patientId,
        ]);
    }

    public function forModel(object $model): static
    {
        return $this->state(fn (array $attributes) => [
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
        ]);
    }
}
