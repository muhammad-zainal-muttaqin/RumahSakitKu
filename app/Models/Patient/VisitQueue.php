<?php

declare(strict_types=1);

namespace App\Models\Patient;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Polyclinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Visit Queue Model
 *
 * Represents a patient queue entry for polyclinic visits.
 * Manages queue numbers, calling status, and wait times.
 *
 * @property int $id
 * @property int $visit_id
 * @property int $patient_id
 * @property int $polyclinic_id
 * @property int $queue_number
 * @property string $display_number
 * @property string $status
 * @property Carbon|null $called_at
 * @property Carbon|null $completed_at
 * @property string|null $counter_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read int|null $waiting_time
 * @property-read int|null $service_time
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read bool $can_be_called
 * @property-read bool $can_be_completed
 * @property-read bool $can_be_skipped
 * @property-read Visit $visit
 * @property-read Patient $patient
 * @property-read Polyclinic $polyclinic
 *
 * @method static Builder|VisitQueue withStatus(string $status)
 * @method static Builder|VisitQueue waiting()
 * @method static Builder|VisitQueue called()
 * @method static Builder|VisitQueue completed()
 * @method static Builder|VisitQueue inPolyclinic(int $polyclinicId)
 * @method static Builder|VisitQueue today()
 * @method static Builder|VisitQueue ordered()
 */
class VisitQueue extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'visit_queues';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'polyclinic_id',
        'queue_number',
        'display_number',
        'status',
        'called_at',
        'completed_at',
        'counter_number',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'queue_number' => 'integer',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the visit associated with this queue.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the patient associated with this queue.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the polyclinic associated with this queue.
     */
    public function polyclinic(): BelongsTo
    {
        return $this->belongsTo(Polyclinic::class, 'polyclinic_id');
    }

    /**
     * Scope a query to only include queues with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include waiting queues.
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    /**
     * Scope a query to only include called queues.
     */
    public function scopeCalled($query)
    {
        return $query->where('status', 'called');
    }

    /**
     * Scope a query to only include completed queues.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to filter by polyclinic.
     */
    public function scopeInPolyclinic($query, int $polyclinicId)
    {
        return $query->where('polyclinic_id', $polyclinicId);
    }

    /**
     * Scope a query to only include today's queues.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to order by queue number.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('queue_number', 'asc');
    }

    /**
     * Mark this queue as called.
     */
    public function markAsCalled(?string $counterNumber = null): void
    {
        $this->update([
            'status' => 'called',
            'called_at' => now(),
            'counter_number' => $counterNumber,
        ]);
    }

    /**
     * Mark this queue as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark this queue as skipped.
     */
    public function markAsSkipped(): void
    {
        $this->update([
            'status' => 'skipped',
        ]);
    }

    /**
     * Mark this queue as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    /**
     * Mark this queue as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Get the waiting time in minutes.
     */
    public function getWaitingTimeAttribute(): ?int
    {
        if ($this->called_at) {
            return (int) $this->created_at->diffInMinutes($this->called_at);
        }

        return (int) $this->created_at->diffInMinutes(now());
    }

    /**
     * Get the service time in minutes.
     */
    public function getServiceTimeAttribute(): ?int
    {
        if ($this->called_at && $this->completed_at) {
            return (int) $this->called_at->diffInMinutes($this->completed_at);
        }

        return null;
    }

    /**
     * Get status color for badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'waiting' => 'gray',
            'called' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            'skipped' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting' => 'Menunggu',
            'called' => 'Dipanggil',
            'in_progress' => 'Sedang Dilayani',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'skipped' => 'Dilewati',
            default => $this->status,
        };
    }

    /**
     * Check if queue can be called.
     */
    public function getCanBeCalledAttribute(): bool
    {
        return in_array($this->status, ['waiting', 'skipped'], true);
    }

    /**
     * Check if queue can be completed.
     */
    public function getCanBeCompletedAttribute(): bool
    {
        return in_array($this->status, ['called', 'in_progress'], true);
    }

    /**
     * Check if queue can be skipped.
     */
    public function getCanBeSkippedAttribute(): bool
    {
        return in_array($this->status, ['waiting', 'called'], true);
    }
}
