<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\BpjsLog;
use App\Models\MasterData\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $user = new User();

        $expectedFillable = [
            'name',
            'email',
            'password',
            'employee_id',
            'is_active',
            'last_login_at',
            'last_login_ip',
        ];

        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    #[Test]
    public function it_has_hidden_attributes(): void
    {
        $user = new User();

        $expectedHidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($expectedHidden, $user->getHidden());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('last_login_at', $casts);
        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertEquals('hashed', $casts['password']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('datetime', $casts['last_login_at']);
        $this->assertEquals('datetime', $casts['email_verified_at']);
    }

    #[Test]
    public function it_belongs_to_employee(): void
    {
        $user = new User();
        $relation = $user->employee();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Employee::class, $relation->getRelated());
        $this->assertEquals('employee_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function it_has_many_audit_logs(): void
    {
        $user = new User();
        $relation = $user->auditLogs();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(AuditLog::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_bpjs_logs(): void
    {
        $user = new User();
        $relation = $user->bpjsLogs();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(BpjsLog::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_active_scope(): void
    {
        // Create active users through database since User factory doesn't exist
        User::create([
            'name' => 'Active User 1',
            'email' => 'active1@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        User::create([
            'name' => 'Active User 2',
            'email' => 'active2@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
        $this->assertTrue($activeUsers->every(fn ($user) => $user->is_active === true));
    }

    #[Test]
    public function it_checks_is_active(): void
    {
        $activeUser = new User(['is_active' => true]);
        $inactiveUser = new User(['is_active' => false]);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    #[Test]
    public function it_records_login(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $ip = '192.168.1.1';
        $user->recordLogin($ip);

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->last_login_at);
        $this->assertEquals($ip, $freshUser->last_login_ip);
    }

    #[Test]
    public function it_password_is_hashed(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintextpassword',
        ]);

        $this->assertNotEquals('plaintextpassword', $user->password);
    }

    #[Test]
    public function it_can_be_created_with_employee(): void
    {
        $employee = Employee::factory()->create();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'employee_id' => $employee->id,
        ]);

        $this->assertEquals($employee->id, $user->employee_id);
        $this->assertInstanceOf(Employee::class, $user->employee);
    }

    #[Test]
    public function it_has_roles_trait(): void
    {
        $user = new User();

        $this->assertTrue(method_exists($user, 'roles'));
        $this->assertTrue(method_exists($user, 'permissions'));
    }

    #[Test]
    public function it_can_check_has_role(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create and assign role
        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('non-existent-role'));
    }

    #[Test]
    public function it_casts_is_active_to_boolean(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => 1,
        ]);

        $this->assertIsBool($user->is_active);
        $this->assertTrue($user->is_active);
    }

    #[Test]
    public function it_casts_last_login_at_to_datetime(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'last_login_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->last_login_at);
    }

    #[Test]
    public function it_casts_email_verified_at_to_datetime(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }
}
