<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    /**
     * Setup before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional test setup if needed
    }

    /**
     * Acting as a user with specific role.
     */
    protected function actingAsRole(string $role, ?string $email = null): static
    {
        $user = \App\Models\User::factory()->create([
            'email' => $email ?? fake()->unique()->safeEmail(),
        ]);
        $user->assignRole($role);
        
        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Create a patient with visits.
     */
    protected function createPatientWithVisits(int $visitCount = 1): \App\Models\Patient\Patient
    {
        $patient = \App\Models\Patient\Patient::factory()->create();
        
        \App\Models\Patient\Visit::factory()
            ->count($visitCount)
            ->create(['patient_id' => $patient->id]);
        
        return $patient;
    }
}
