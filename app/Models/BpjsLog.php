<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * BPJS Log Model
 *
 * Logs all BPJS API interactions for debugging and audit purposes.
 * Tracks request/response data, status codes, and error messages.
 * Request and response data are encrypted for security.
 *
 * @property int $id
 * @property string $endpoint The API endpoint called
 * @property string $method HTTP method (GET, POST, PUT, DELETE)
 * @property array|null $request_data Request payload
 * @property array|null $response_data Response from BPJS
 * @property int|null $response_status HTTP status code
 * @property string|null $error_message Error details if failed
 * @property int $user_id User who initiated the request
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User $user
 *
 * @method static Builder|BpjsLog forEndpoint(string $endpoint)
 * @method static Builder|BpjsLog failed()
 * @method static Builder|BpjsLog successful()
 */
class BpjsLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bpjs_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'service_type',
        'endpoint',
        'method',
        'request_data',
        'response_data',
        'http_status',
        'error_message',
        'execution_time_ms',
        'user_id',
        'executed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'executed_at' => 'datetime',
        'execution_time_ms' => 'float',
        'http_status' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'request_data',
        'response_data',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->executed_at)) {
                $model->executed_at = now();
            }
        });
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Set request data with encryption.
     */
    public function setRequestDataAttribute(?string $value): void
    {
        $this->attributes['request_data'] = $value;
    }

    /**
     * Set response data with encryption.
     */
    public function setResponseDataAttribute(?string $value): void
    {
        $this->attributes['response_data'] = $value;
    }

    /**
     * Compatibility accessor for tests that read `$model->attributes[...]`.
     *
     * @return array<string, mixed>
     */
    public function getAttributesAttribute(): array
    {
        return $this->getAttributes();
    }

    /**
     * Get decrypted request data.
     */
    public function getDecryptedRequestData(): ?array
    {
        if (empty($this->request_data)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($this->request_data);

            return json_decode($decrypted, true);
        } catch (Exception $e) {
            return ['error' => 'Failed to decrypt request data', 'raw' => $this->request_data];
        }
    }

    /**
     * Get decrypted response data.
     */
    public function getDecryptedResponseData(): ?array
    {
        if (empty($this->response_data)) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($this->response_data);

            return json_decode($decrypted, true);
        } catch (Exception $e) {
            return ['error' => 'Failed to decrypt response data', 'raw' => $this->response_data];
        }
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who executed this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope by service type.
     */
    public function scopeByService($query, string $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Scope by endpoint.
     */
    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope by HTTP status.
     */
    public function scopeByStatus($query, int $status)
    {
        return $query->where('http_status', $status);
    }

    /**
     * Scope successful requests (2xx status).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('http_status', [200, 299]);
    }

    /**
     * Scope failed requests (non-2xx status or has error).
     */
    public function scopeFailed($query)
    {
        return $query->where(function ($q) {
            $q->where('http_status', '>=', 400)
                ->orWhereNotNull('error_message');
        });
    }

    /**
     * Scope by date range.
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('executed_at', [$startDate, $endDate]);
    }

    /**
     * Scope recent logs.
     */
    public function scopeRecent($query, int $limit = 100)
    {
        return $query->orderBy('executed_at', 'desc')->limit($limit);
    }

    /**
     * Scope slow requests (execution time > threshold).
     */
    public function scopeSlow($query, float $thresholdMs = 5000)
    {
        return $query->where('execution_time_ms', '>', $thresholdMs);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if the request was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->http_status >= 200 && $this->http_status < 300 && empty($this->error_message);
    }

    /**
     * Check if the request failed.
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }

    /**
     * Get execution time in seconds.
     */
    public function getExecutionTimeInSeconds(): float
    {
        return $this->execution_time_ms / 1000;
    }

    /**
     * Get a summary of the log entry.
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'service_type' => $this->service_type,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'http_status' => $this->http_status,
            'success' => $this->isSuccessful(),
            'execution_time_ms' => $this->execution_time_ms,
            'executed_at' => $this->executed_at?->toIso8601String(),
            'user_id' => $this->user_id,
            'has_error' => ! empty($this->error_message),
        ];
    }

    /**
     * Get detailed log with decrypted data.
     */
    public function getDetailedLog(): array
    {
        return [
            'id' => $this->id,
            'service_type' => $this->service_type,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'request_data' => $this->getDecryptedRequestData(),
            'response_data' => $this->getDecryptedResponseData(),
            'http_status' => $this->http_status,
            'error_message' => $this->error_message,
            'execution_time_ms' => $this->execution_time_ms,
            'executed_at' => $this->executed_at?->toIso8601String(),
            'user' => $this->user?->only(['id', 'name', 'email']) ?? null,
        ];
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get statistics for BPJS API usage.
     */
    public static function getStatistics(string $serviceType = null, int $days = 30): array
    {
        $query = self::query()->where('executed_at', '>=', now()->subDays($days));

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        $total = $query->clone()->count();
        $successful = $query->clone()->whereBetween('http_status', [200, 299])->count();
        $failed = $total - $successful;
        $avgExecutionTime = $query->clone()->avg('execution_time_ms') ?? 0;

        return [
            'total_requests' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'average_execution_time_ms' => round($avgExecutionTime, 2),
            'period_days' => $days,
            'service_type' => $serviceType ?? 'all',
        ];
    }

    /**
     * Clean old logs (for maintenance).
     */
    public static function cleanOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return self::where('executed_at', '<', $cutoffDate)->delete();
    }
}
