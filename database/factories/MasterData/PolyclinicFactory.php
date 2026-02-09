<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Polyclinic;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolyclinicFactory extends Factory
{
    protected $model = Polyclinic::class;

    public function definition(): array
    {
        $categories = ['umum', 'spesialis', 'gigi', 'anak', 'bedah', 'penyakit_dalam', 'syaraf', 'jiwa', 'rehabilitasi', 'radiologi', 'laboratorium'];
        $category = $this->faker->randomElement($categories);

        return [
            'code' => strtoupper(substr($category, 0, 3)) . $this->faker->unique()->numerify('###'),
            'name' => ucfirst($category) . ' ' . $this->faker->lastName(),
            'category' => $category,
            'queue_prefix' => strtoupper(substr($category, 0, 1)),
            'bpjs_poli_code' => $this->faker->optional()->numerify('###'),
            'bpjs_poli_name' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'max_queue_per_day' => $this->faker->numberBetween(10, 100),
            'open_time' => '08:00',
            'close_time' => '16:00',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function umum(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'umum',
            'name' => 'Poliklinik Umum',
            'queue_prefix' => 'U',
        ]);
    }

    public function spesialis(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'spesialis',
            'name' => 'Poliklinik Spesialis ' . $this->faker->lastName(),
            'queue_prefix' => 'S',
        ]);
    }

    public function gigi(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'gigi',
            'name' => 'Poliklinik Gigi',
            'queue_prefix' => 'G',
        ]);
    }

    public function laboratorium(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'laboratorium',
            'name' => 'Laboratorium',
            'queue_prefix' => 'L',
        ]);
    }

    public function withQuota(int $quota): static
    {
        return $this->state(fn (array $attributes) => [
            'max_queue_per_day' => $quota,
        ]);
    }
}
