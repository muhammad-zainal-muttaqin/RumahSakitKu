<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Exception;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasAuditLogs
 *
 * Provides automatic audit logging functionality for Eloquent models.
 * Automatically tracks CREATE, UPDATE, DELETE events with old/new values.
 * Masks sensitive fields (passwords, NIK, BPJS numbers) for security.
 * Stores audit logs to database (AuditLog model) and log channel.
 *
 * Features:
 * - Automatic event logging on model changes
 * - Sensitive data masking
 * - User attribution
 * - IP address and user agent tracking
 * - Configurable field exclusion
 * - Retention policy support
 *
 * Usage:
 * ```php
 * class MyModel extends Model
 * {
 *     use HasAuditLogs;
 *
 *     // Optional: Additional sensitive fields to mask
 *     protected array $sensitiveFields = ['custom_secret'];
 *
 *     // Optional: Additional fields to exclude from audit
 *     protected array $auditExclude = ['last_synced_at'];
 * }
 * ```
 *
 * @package App\Models\Concerns
 *
 * @method Collection auditLogs()
 * @method AuditLog|null latestAuditLog()
 * @method Collection auditLogsByEvent(string $event)
 */
trait HasAuditLogs
{
    /**
     * Sensitive fields that should be masked.
     *
     * @var array<string>
     */
    protected array $defaultSensitiveFields = [
        'nik',
        'bpjs_number',
        'bpjs_kartu',
        'no_bpjs',
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'card_number',
    ];

    /**
     * Fields to exclude from audit logging.
     *
     * @var array<string>
     */
    protected array $defaultAuditExclude = [
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Boot the audit logs trait.
     *
     * Registers event listeners for created, updated, deleted, restored, and forceDeleted events.
     *
     * @return void
     */
    public static function bootHasAuditLogs(): void
    {
        static::created(function (Model $model) {
            $model->logAudit('created', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();

            // Only log changed fields
            $oldFiltered = array_intersect_key($oldValues, $newValues);

            $model->logAudit('updated', $oldFiltered, $newValues);
        });

        static::deleted(function (Model $model) {
            $model->logAudit('deleted', $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $model->logAudit('restored', null, $model->getAttributes());
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model) {
                $model->logAudit('force_deleted', $model->getAttributes(), null);
            });
        }
    }

    /**
     * Log an audit event.
     *
     * Creates an audit log entry with user info, old/new values, and request metadata.
     *
     * @param string $event The event type (CREATE, UPDATE, DELETE, RESTORE, FORCE_DELETE)
     * @param array|null $oldValues The previous values (null for CREATE)
     * @param array|null $newValues The new values (null for DELETE)
     * @return void
     */
    public function logAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();

        // Skip implicit background/factory writes to keep audit logs user-attributed.
        if (!$user) {
            return;
        }

        $auditData = [
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'old_values' => $oldValues ? $this->maskSensitiveFields($oldValues) : null,
            'new_values' => $newValues ? $this->maskSensitiveFields($newValues) : null,
            'patient_id' => $this->getPatientId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'created_at' => now(),
        ];

        // Store to database if AuditLog model exists
        $this->storeToDatabase($auditData);

        // Log to audit channel
        Log::channel('audit')->info("Model audit: {$event}", $auditData);
    }

    /**
     * Mask sensitive fields in values.
     *
     * Replaces sensitive field values with masked versions.
     *
     * @param array $values The values to mask
     * @return array The masked values
     */
    protected function maskSensitiveFields(array $values): array
    {
        $masked = [];

        foreach ($values as $key => $value) {
            if ($this->shouldExcludeField($key)) {
                continue;
            }

            if ($this->isSensitiveField($key)) {
                $masked[$key] = $this->maskValue($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Check if field should be excluded from audit.
     *
     * @param string $field The field name to check
     * @return bool True if the field should be excluded
     */
    protected function shouldExcludeField(string $field): bool
    {
        $excludeFields = array_merge(
            $this->defaultAuditExclude,
            $this->getAuditExclude()
        );

        return in_array($field, $excludeFields, true);
    }

    /**
     * Get additional fields to exclude (can be overridden in model).
     *
     * Models can define an $auditExclude property to add custom excluded fields.
     *
     * @return array<string>
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') && is_array($this->auditExclude)
            ? $this->auditExclude
            : [];
    }

    /**
     * Check if field is sensitive.
     *
     * @param string $field The field name to check
     * @return bool True if the field is sensitive
     */
    protected function isSensitiveField(string $field): bool
    {
        $sensitiveFields = array_merge(
            $this->defaultSensitiveFields,
            $this->getSensitiveFields()
        );

        return in_array($field, $sensitiveFields, true);
    }

    /**
     * Get additional sensitive fields (can be overridden in model).
     *
     * Models can define a $sensitiveFields property to add custom sensitive fields.
     *
     * @return array<string>
     */
    protected function getSensitiveFields(): array
    {
        return property_exists($this, 'sensitiveFields') && is_array($this->sensitiveFields)
            ? $this->sensitiveFields
            : [];
    }

    /**
     * Mask a sensitive value.
     *
     * Shows first 2 and last 2 characters, masks the rest with asterisks.
     *
     * @param mixed $value The value to mask
     * @return string The masked value
     */
    protected function maskValue(mixed $value): string
    {
        if (is_null($value) || $value === '') {
            return '';
        }

        $str = (string) $value;
        $length = strlen($str);

        if ($length <= 4) {
            return '****';
        }

        // Show first 2 and last 2 characters
        return substr($str, 0, 2) . str_repeat('*', $length - 4) . substr($str, -2);
    }

    /**
     * Get patient ID if model has patient relation.
     *
     * Attempts to extract patient_id from various sources.
     *
     * @return int|string|null The patient ID or null
     */
    protected function getPatientId(): int|string|null
    {
        // Direct patient_id column
        if ($this->hasAttribute('patient_id')) {
            return $this->getAttribute('patient_id');
        }

        // Patient relation
        if ($this->relationLoaded('patient')) {
            return $this->patient?->id;
        }

        // Check if this is a Patient model
        if (str_contains(static::class, 'Patient') && !str_contains(static::class, 'PatientVisit')) {
            return $this->getKey();
        }

        // Try to load patient relation if exists
        if (method_exists($this, 'patient')) {
            try {
                return $this->patient()?->first()?->id;
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check if model has an attribute.
     *
     * @param string $key The attribute name
     * @return bool True if the attribute exists
     */
    public function hasAttribute($key): bool
    {
        return array_key_exists($key, $this->getAttributes()) ||
            array_key_exists($key, $this->casts) ||
            $this->hasGetMutator($key) ||
            $this->hasAttributeMutator($key) ||
            $this->isClassCastable($key);
    }

    /**
     * Store audit log to database.
     *
     * Creates an AuditLog record and applies retention policy cleanup.
     *
     * @param array $data The audit data to store
     * @return void
     */
    protected function storeToDatabase(array $data): void
    {
        try {
            // Check if AuditLog model exists
            if (!class_exists('App\Models\AuditLog')) {
                return;
            }

            // Apply retention policy
            $retentionYears = config('audit.retention_years', 7);

            // Create the audit log entry
            AuditLog::create([
                'event' => $data['event'],
                'auditable_type' => $data['auditable_type'],
                'auditable_id' => $data['auditable_id'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'old_values' => $data['old_values'],
                'new_values' => $data['new_values'],
                'patient_id' => $data['patient_id'],
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'url' => $data['url'],
                'created_at' => $data['created_at'],
            ]);

            // Clean up old audit logs based on retention policy
            $this->cleanupOldAuditLogs($retentionYears);

        } catch (Exception $e) {
            // Silently fail - don't disrupt the operation
            Log::channel('audit')->error('Failed to store audit log to database', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Clean up old audit logs based on retention policy.
     *
     * Runs with 1% probability to avoid performance impact.
     *
     * @param int $retentionYears Number of years to retain logs
     * @return void
     */
    protected function cleanupOldAuditLogs(int $retentionYears): void
    {
        try {
            $cutoffDate = now()->subYears($retentionYears);

            // Only run cleanup 1% of the time to avoid performance impact
            if (random_int(1, 100) !== 1) {
                return;
            }

            if (class_exists('App\Models\AuditLog')) {
                AuditLog::where('created_at', '<', $cutoffDate)
                    ->delete();
            }
        } catch (Exception $e) {
            Log::channel('audit')->error('Failed to cleanup old audit logs', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get audit logs for this model.
     *
     * @return Collection Collection of AuditLog records
     */
    public function auditLogs()
    {
        if (!class_exists('App\Models\AuditLog')) {
            return collect();
        }

        return AuditLog::where('auditable_type', static::class)
            ->where('auditable_id', $this->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get latest audit log for this model.
     *
     * @return AuditLog|null The latest audit log or null
     */
    public function latestAuditLog(): ?AuditLog
    {
        if (!class_exists('App\Models\AuditLog')) {
            return null;
        }

        return AuditLog::where('auditable_type', static::class)
            ->where('auditable_id', $this->getKey())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get audit logs by event type.
     *
     * @param string $event The event type to filter by (CREATE, UPDATE, DELETE, etc.)
     * @return Collection Collection of matching audit logs
     */
    public function auditLogsByEvent(string $event)
    {
        if (!class_exists('App\Models\AuditLog')) {
            return collect();
        }

        return AuditLog::where('auditable_type', static::class)
            ->where('auditable_id', $this->getKey())
            ->where('event', $event)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
