<?php

declare(strict_types=1);

namespace Database\Factories\Patient;

use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        $visitDate = $this->faker->dateTimeBetween('-30 days', 'today');

        return [
            'visit_number' => 'VIS' . $this->faker->unique()->numerify('########'),
            'patient_id' => Patient::factory(),
            'polyclinic_id' => Polyclinic::factory(),
            'doctor_id' => Employee::factory()->doctor(),
            'registration_date' => $visitDate,
            'visit_type' => $this->faker->randomElement(['rawat_jalan', 'rawat_inap', 'igd', 'mcu']),
            'visit_status' => $this->faker->randomElement(['pendaftaran', 'menunggu', 'proses', 'selesai', 'batal']),
            'payment_type' => $this->faker->randomElement(['bpjs', 'umum', 'asuransi', 'perusahaan', 'gratis']),
            'priority' => $this->faker->randomElement(['normal', 'darurat', 'prioritas']),
            'registered_by' => User::factory(),
            'bpjs_sep_number' => $this->faker->optional()->numerify('SEP#############'),
            'queue_number' => $this->faker->optional()->numerify('Q###'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_status' => 'selesai',
            'completed_at' => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_status' => 'proses',
            'examination_at' => now()->subMinutes(30),
        ]);
    }

    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_type' => 'igd',
            'priority' => 'darurat',
        ]);
    }

    public function inpatient(): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_type' => 'rawat_inap',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'visit_status' => 'batal',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_date' => now(),
        ]);
    }

    public function withPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'patient_id' => $patient->id,
        ]);
    }
}
