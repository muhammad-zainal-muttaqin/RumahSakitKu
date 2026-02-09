<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu Sehat Log Model
 *
 * Logs all Satu Sehat API interactions for debugging and audit purposes.
 * Tracks FHIR resource synchronization, request/response data, and error messages.
 * Maintains mapping between local IDs and Satu Sehat FHIR IDs.
 *
 * @property int $id
 * @property string $resource_type The FHIR resource type (e.g., Patient, Encounter, Observation)
 * @property string|null $fhir_id The Satu Sehat FHIR ID
 * @property string|null $local_type Local model type
 * @property int|null $local_id Local model ID
 * @property string $action The action performed (CREATE, UPDATE, DELETE, SEARCH)
 * @property array|null $request_data Request payload sent to Satu Sehat
 * @property array|null $response_data Response from Satu Sehat
 * @property string $status Status of the request (pending, success, failed)
 * @property string|null $error_message Error details if failed
 * @property int|null $response_time_ms Response time in milliseconds
 * @property int $retry_count Number of retry attempts
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Model|null $local The local model (polymorphic relation)
 *
 * @method static Builder|SatuSehatLog forResourceType(string $resourceType)
 * @method static Builder|SatuSehatLog forFhirId(string $fhirId)
 * @method static Builder|SatuSehatLog forLocal(string $localType, int $localId)
 * @method static Builder|SatuSehatLog withStatus(string $status)
 * @method static Builder|SatuSehatLog successful()
 * @method static Builder|SatuSehatLog failed()
 * @method static Builder|SatuSehatLog pending()
 * @method static Builder|SatuSehatLog forAction(string $action)
 * @method static Builder|SatuSehatLog inDateRange($startDate, $endDate)
 * @method static Builder|SatuSehatLog recent()
 */
class SatuSehatLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'satusehat_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'resource_type',
        'fhir_id',
        'local_type',
        'local_id',
        'action',
        'request_data',
        'response_data',
        'status',
        'error_message',
        'response_time_ms',
        'retry_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Allow compatibility assignment for timestamp fields without changing fillable contract.
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes)
    {
        $createdAt = $attributes['created_at'] ?? null;
        $updatedAt = $attributes['updated_at'] ?? null;
        unset($attributes['created_at'], $attributes['updated_at']);

        parent::fill($attributes);

        if ($createdAt !== null) {
            $this->setAttribute('created_at', $createdAt);
        }
        if ($updatedAt !== null) {
            $this->setAttribute('updated_at', $updatedAt);
        }

        return $this;
    }

    /**
     * Get the parent local model (polymorphic relation).
     */
    public function local(): MorphTo
    {
        return $this->morphTo('local', 'local_type', 'local_id');
    }

    /**
     * Scope a query to only include logs for a specific resource type.
     */
    public function scopeForResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope a query to only include logs for a specific FHIR ID.
     */
    public function scopeForFhirId($query, string $fhirId)
    {
        return $query->where('fhir_id', $fhirId);
    }

    /**
     * Scope a query to only include logs for a specific local model.
     */
    public function scopeForLocal($query, string $localType, int $localId)
    {
        return $query->where('local_type', $localType)
            ->where('local_id', $localId);
    }

    /**
     * Scope a query to only include logs with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include pending logs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get recent logs first.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get FHIR ID for a local model.
     *
     * @param string $localType
     * @param int $localId
     * @param string|null $resourceType
     * @return string|null
     */
    public static function getFhirId(string $localType, int $localId, ?string $resourceType = null): ?string
    {
        $query = self::where('local_type', $localType)
            ->where('local_id', $localId)
            ->where('status', 'success')
            ->whereNotNull('fhir_id');

        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }

        $log = $query->recent()->first();

        return $log?->fhir_id;
    }

    /**
     * Get local ID from FHIR ID.
     *
     * @param string $fhirId
     * @param string|null $resourceType
     * @return array{type: string|null, id: int|null}
     */
    public static function getLocalId(string $fhirId, ?string $resourceType = null): array
    {
        $query = self::where('fhir_id', $fhirId)
            ->where('status', 'success');

        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }

        $log = $query->recent()->first();

        return [
            'type' => $log?->local_type,
            'id' => $log?->local_id,
        ];
    }

    /**
     * Log a FHIR request/response.
     *
     * @param string $resourceType
     * @param string|null $localType
     * @param int|null $localId
     * @param string $action
     * @param array|null $requestData
     * @param array|null $responseData
     * @param string $status
     * @param string|null $errorMessage
     * @param int|null $responseTimeMs
     * @return self
     */
    public static function log(
        string $resourceType,
        ?string $localType,
        ?int $localId,
        string $action,
        ?array $requestData = null,
        ?array $responseData = null,
        string $status = 'pending',
        ?string $errorMessage = null,
        ?int $responseTimeMs = null
    ): self {
        $fhirId = $responseData['id'] ?? null;

        return self::create([
            'resource_type' => $resourceType,
            'fhir_id' => $fhirId,
            'local_type' => $localType,
            'local_id' => $localId,
            'action' => $action,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'status' => $status,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Get statistics for logs.
     *
     * @param string|null $resourceType
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array<string, mixed>
     */
    public static function getStatistics(?string $resourceType = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = self::query();

        if ($resourceType) {
            $query->where('resource_type', $resourceType);
        }

        if ($startDate && $endDate) {
            $query->inDateRange($startDate, $endDate);
        }

        $total = $query->count();
        $successful = (clone $query)->successful()->count();
        $failed = (clone $query)->failed()->count();
        $pending = (clone $query)->pending()->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get retry candidates (failed requests that can be retried).
     *
     * @param int $maxRetries
     * @return Collection
     */
    public static function getRetryCandidates(int $maxRetries = 3)
    {
        return self::failed()
            ->where('retry_count', '<', $maxRetries)
            ->where('created_at', '>', now()->subDays(7))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Increment retry count.
     *
     * @return void
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Mark as successful.
     *
     * @param array|null $responseData
     * @param string|null $fhirId
     * @return void
     */
    public function markAsSuccessful(?array $responseData = null, ?string $fhirId = null): void
    {
        $this->update([
            'status' => 'success',
            'fhir_id' => $fhirId ?? $responseData['id'] ?? $this->fhir_id,
            'response_data' => $responseData ?? $this->response_data,
            'error_message' => null,
        ]);
    }

    /**
     * Mark as failed.
     *
     * @param string $errorMessage
     * @param array|null $responseData
     * @return void
     */
    public function markAsFailed(string $errorMessage, ?array $responseData = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'response_data' => $responseData ?? $this->response_data,
        ]);
    }

    /**
     * Get the FHIR URL for this resource.
     *
     * @return string|null
     */
    public function getFhirUrl(): ?string
    {
        if (!$this->fhir_id || !$this->resource_type) {
            return null;
        }

        $baseUrl = config('satusehat.' . config('satusehat.mode') . '.base_url');

        return "{$baseUrl}/{$this->resource_type}/{$this->fhir_id}";
    }

    /**
     * Check if this log has a successful mapping.
     *
     * @return bool
     */
    public function hasSuccessfulMapping(): bool
    {
        return $this->status === 'success' && !empty($this->fhir_id);
    }
}
