<?php

declare(strict_types=1);

namespace Database\Factories\MasterData;

use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['L', 'P']);

        return [
            'employee_code' => 'EMP' . $this->faker->unique()->numerify('######'),
            'nip' => $this->faker->unique()->numerify('##################'),
            'name' => $this->faker->name($gender === 'L' ? 'male' : 'female'),
            'gender' => $gender,
            'birth_date' => $this->faker->dateTimeBetween('-60 years', '-25 years')->format('Y-m-d'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'employee_type' => $this->faker->randomElement(['tetap', 'kontrak', 'honorer']),
            'is_doctor' => false,
            'doctor_title' => null,
            'sip_number' => null,
            'sip_expiry_date' => null,
            'str_number' => null,
            'str_expiry_date' => null,
            'specialist_polyclinic_id' => null,
            'is_nurse' => false,
            'sip_nurse_number' => null,
            'profession' => $this->faker->jobTitle(),
            'certification_number' => $this->faker->optional()->numerify('CERT########'),
            'join_date' => $this->faker->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'resign_date' => null,
            'status' => 'aktif',
            'photo_path' => null,
        ];
    }

    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_doctor' => true,
            'doctor_title' => $this->faker->randomElement(['dr.', 'drg.', 'Dr. dr.']),
            'sip_number' => 'SIP' . $this->faker->unique()->numerify('########'),
            'sip_expiry_date' => now()->addMonths(3)->format('Y-m-d'),
            'str_number' => 'STR' . $this->faker->unique()->numerify('########'),
            'str_expiry_date' => now()->addMonths(3)->format('Y-m-d'),
            'specialist_polyclinic_id' => Polyclinic::factory(),
            'profession' => 'Dokter',
        ]);
    }

    public function nurse(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_nurse' => true,
            'sip_nurse_number' => 'SIP' . $this->faker->unique()->numerify('########'),
            'profession' => 'Perawat',
        ]);
    }

    public function pharmacist(): static
    {
        return $this->state(fn (array $attributes) => [
            'profession' => 'Tenaga Teknis Kefarmasian',
        ]);
    }

    public function withExpiredSIP(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_doctor' => true,
            'sip_number' => 'SIP' . $this->faker->unique()->numerify('########'),
            'sip_expiry_date' => $this->faker->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function withExpiringSIP(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_doctor' => true,
            'sip_number' => 'SIP' . $this->faker->unique()->numerify('########'),
            'sip_expiry_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'nonaktif',
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cuti',
        ]);
    }

    public function resigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'nonaktif',
            'resign_date' => $this->faker->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'tetap',
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_type' => 'kontrak',
        ]);
    }

    public function withValidLicenses(): static
    {
        return $this->state(fn (array $attributes) => [
            'sip_expiry_date' => now()->addMonths(3)->format('Y-m-d'),
            'str_expiry_date' => now()->addMonths(3)->format('Y-m-d'),
        ]);
    }
}
