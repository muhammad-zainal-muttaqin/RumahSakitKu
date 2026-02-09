<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Builder;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 *
 * Represents a system user with authentication capabilities.
 * Links to Employee for staff users and handles role-based access.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $employee_id
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Employee|null $employee
 * @property-read Collection|AuditLog[] $auditLogs
 * @property-read Collection|BpjsLog[] $bpjsLogs
 * @property-read Collection|Role[] $roles
 * @property-read Collection|Permission[] $permissions
 *
 * @method static Builder|User active()
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Support legacy mass-assignment for email verification timestamp.
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes)
    {
        $emailVerifiedAt = $attributes['email_verified_at'] ?? null;
        unset($attributes['email_verified_at']);

        parent::fill($attributes);

        if ($emailVerifiedAt !== null) {
            $this->setAttribute('email_verified_at', $emailVerifiedAt);
        }

        return $this;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Get the employee associated with this user.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get all audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get all BPJS logs for this user.
     */
    public function bpjsLogs(): HasMany
    {
        return $this->hasMany(BpjsLog::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Record user login information.
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Return remember token safely even when the column was not selected.
     */
    public function getRememberToken()
    {
        $tokenName = $this->getRememberTokenName();

        return $this->getAttributeFromArray($tokenName) ?? null;
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
