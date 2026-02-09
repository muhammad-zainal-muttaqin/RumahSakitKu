<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        Role::create(['name' => 'nurse', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);
        Role::create(['name' => 'pharmacy', 'guard_name' => 'web']);
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);
    }

    /**
     * Test login page can be accessed.
     */
    public function test_login_page_can_be_accessed(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test user can login with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test user cannot login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test inactive user cannot login.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test login validation requires email.
     */
    public function test_login_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test login validation requires valid email format.
     */
    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test login validation requires password.
     */
    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test logout clears session.
     */
    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $this->actingAs($user);
        session(['custom_data' => 'test']);

        $this->post('/logout');

        $this->assertNull(session('custom_data'));
    }

    /**
     * Test password reset link can be requested.
     */
    public function test_password_reset_link_can_be_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHas('status');
    }

    /**
     * Test password reset requires valid email.
     */
    public function test_password_reset_requires_valid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test password reset validation requires email field.
     */
    public function test_password_reset_requires_email_field(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test admin can access admin dashboard.
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    /**
     * Test doctor can access medical records.
     */
    public function test_doctor_can_access_medical_records(): void
    {
        $doctor = User::factory()->create(['is_active' => true]);
        $doctor->assignRole('doctor');

        $response = $this->actingAs($doctor)->get('/admin/medical-records');

        $response->assertStatus(200);
    }

    /**
     * Test nurse cannot access admin-only areas.
     */
    public function test_nurse_cannot_access_admin_only_areas(): void
    {
        $nurse = User::factory()->create(['is_active' => true]);
        $nurse->assignRole('nurse');

        $response = $this->actingAs($nurse)->get('/admin/users');

        $response->assertStatus(403);
    }

    /**
     * Test registration staff can access patient registration.
     */
    public function test_registration_staff_can_access_patient_registration(): void
    {
        $registration = User::factory()->create(['is_active' => true]);
        $registration->assignRole('registration');

        $response = $this->actingAs($registration)->get('/admin/patients');

        $response->assertStatus(200);
    }

    /**
     * Test pharmacy staff can access prescription management.
     */
    public function test_pharmacy_staff_can_access_prescription_management(): void
    {
        $pharmacy = User::factory()->create(['is_active' => true]);
        $pharmacy->assignRole('pharmacy');

        $response = $this->actingAs($pharmacy)->get('/admin/pharmacy/prescriptions');

        $response->assertStatus(200);
    }

    /**
     * Test cashier can access billing.
     */
    public function test_cashier_can_access_billing(): void
    {
        $cashier = User::factory()->create(['is_active' => true]);
        $cashier->assignRole('cashier');

        $response = $this->actingAs($cashier)->get('/admin/billing');

        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /**
     * Test user with multiple roles can access all assigned areas.
     */
    public function test_user_with_multiple_roles_can_access_all_assigned_areas(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(['doctor', 'admin']);

        $adminResponse = $this->actingAs($user)->get('/admin');
        $medicalRecordsResponse = $this->actingAs($user)->get('/admin/medical-records');

        $adminResponse->assertStatus(200);
        $medicalRecordsResponse->assertStatus(200);
    }

    /**
     * Test login records last login timestamp.
     */
    public function test_login_records_last_login_timestamp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'last_login_at' => null,
        ]);
        $user->assignRole('admin');

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    /**
     * Test login records IP address.
     */
    public function test_login_records_ip_address(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'last_login_ip' => null,
        ]);
        $user->assignRole('admin');

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_ip);
    }

    /**
     * Test remember me functionality.
     */
    public function test_remember_me_functionality(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/admin');
        $response->assertCookie('remember_web');
    }

    /**
     * Test password reset page can be accessed.
     */
    public function test_password_reset_page_can_be_accessed(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    /**
     * Test guest cannot access authenticated routes.
     */
    public function test_guest_cannot_access_authenticated_routes(): void
    {
        $routes = [
            '/admin',
            '/admin/patients',
            '/admin/medical-records',
            '/admin/pharmacy/prescriptions',
            '/admin/billing',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }
}
