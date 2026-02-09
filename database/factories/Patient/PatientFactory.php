<?php

declare(strict_types=1);

namespace Database\Factories\Patient;

use App\Models\Patient\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $birthDate = $this->faker->dateTimeBetween('-80 years', '-1 day');

        return [
            'medical_record_number' => 'RM' . $this->faker->unique()->numerify('########'),
            'nik' => $this->faker->unique()->numerify('################'),
            'bpjs_number' => $this->faker->optional()->numerify('000#############'),
            'bpjs_ppk_code' => null,
            'bpjs_class' => $this->faker->randomElement(['Kelas I', 'Kelas II', 'Kelas III', 'Non-BPJS']),
            'name' => $this->faker->name($gender === 'L' ? 'male' : 'female'),
            'gender' => $gender,
            'birth_place' => $this->faker->city(),
            'birth_date' => $birthDate->format('Y-m-d'),
            'blood_type' => $this->faker->randomElement(['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Tidak Tahu']),
            'marital_status' => $this->faker->randomElement(['Belum Menikah', 'Menikah', 'Cerai', 'Duda/Janda']),
            'education' => $this->faker->optional()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3']),
            'occupation' => $this->faker->optional()->jobTitle(),
            'nationality' => 'Indonesia',
            'religion' => $this->faker->optional()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'address' => $this->faker->streetAddress() . ', ' . $this->faker->city(),
            'rt' => $this->faker->optional()->numerify('###'),
            'rw' => $this->faker->optional()->numerify('###'),
            'village' => $this->faker->optional()->streetName(),
            'district' => $this->faker->optional()->city(),
            'city' => $this->faker->optional()->city(),
            'province' => $this->faker->optional()->state(),
            'postal_code' => $this->faker->optional()->postcode(),
            'phone_primary' => $this->faker->phoneNumber(),
            'phone_secondary' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
            'emergency_name' => $this->faker->optional()->name(),
            'emergency_relation' => $this->faker->optional()->randomElement(['Suami', 'Istri', 'Ayah', 'Ibu', 'Saudara']),
            'emergency_phone' => $this->faker->optional()->phoneNumber(),
            'emergency_address' => $this->faker->optional()->address(),
            'insurance_name' => $this->faker->optional()->company(),
            'insurance_number' => $this->faker->optional()->numerify('INS########'),
            'insurance_card_path' => null,
            'mother_patient_id' => null,
            'first_visit_at' => null,
            'last_visit_at' => null,
            'total_visits' => 0,
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBPJS(): static
    {
        return $this->state(fn (array $attributes) => [
            'insurance_name' => 'BPJS Kesehatan',
            'bpjs_number' => '000' . $this->faker->numerify('#############'),
            'bpjs_class' => $this->faker->randomElement(['Kelas I', 'Kelas II', 'Kelas III']),
        ]);
    }

    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => $this->faker->dateTimeBetween('-12 years', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function elderly(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => $this->faker->dateTimeBetween('-90 years', '-60 years')->format('Y-m-d'),
        ]);
    }
}
