<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Laboratory Order Model
 *
 * Represents a laboratory test order for a patient.
 * Contains order details and manages test results.
 *
 * @property int $id
 * @property string $order_number
 * @property int $visit_id
 * @property int $patient_id
 * @property int|null $doctor_id
 * @property int|null $medical_record_id
 * @property Carbon|null $order_date
 * @property string $priority
 * @property string $status
 * @property string|null $diagnosis_notes
 * @property string|null $clinical_notes
 * @property float|null $total_price
 * @property bool $is_cito
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read string $priority_color
 * @property-read string $priority_label
 * @property-read string $formatted_order_number
 * @property-read int $total_tests
 * @property-read int $completed_results_count
 * @property-read bool $is_all_results_entered
 * @property-read Visit $visit
 * @property-read Patient $patient
 * @property-read Employee|null $doctor
 * @property-read MedicalRecord|null $medicalRecord
 * @property-read Collection|LaboratoryResult[] $results
 *
 * @method static Builder|LaboratoryOrder withStatus(string $status)
 * @method static Builder|LaboratoryOrder pending()
 * @method static Builder|LaboratoryOrder inProgress()
 * @method static Builder|LaboratoryOrder completed()
 * @method static Builder|LaboratoryOrder validated()
 * @method static Builder|LaboratoryOrder cito()
 * @method static Builder|LaboratoryOrder today()
 * @method static Builder|LaboratoryOrder byPriority(string $priority)
 */
class LaboratoryOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'laboratory_orders';

    protected $fillable = [
        'order_number',
        'visit_id',
        'patient_id',
        'doctor_id',
        'medical_record_id',
        'order_date',
        'priority',
        'status',
        'diagnosis_notes',
        'clinical_notes',
        'total_price',
        'is_cito',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'total_price' => 'decimal:2',
        'is_cito' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the visit associated with this lab order.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the patient associated with this lab order.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the doctor who ordered this lab test.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    /**
     * Get the medical record associated with this lab order.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Get all lab results for this order.
     */
    public function results(): HasMany
    {
        return $this->hasMany(LaboratoryResult::class, 'laboratory_order_id');
    }

    /**
     * Scope a query to only include orders with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include orders in progress.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include validated orders.
     */
    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    /**
     * Scope a query to only include CITO/urgent orders.
     */
    public function scopeCito($query)
    {
        return $query->where('is_cito', true);
    }

    /**
     * Scope a query to only include orders from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'validated' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'Diproses',
            'completed' => 'Selesai',
            'validated' => 'Divalidasi',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get priority color for badges.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'normal' => 'gray',
            'urgent' => 'warning',
            'cito' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'normal' => 'Normal',
            'urgent' => 'Urgent',
            'cito' => 'CITO',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Get formatted order number with prefix.
     */
    public function getFormattedOrderNumberAttribute(): string
    {
        return $this->order_number;
    }

    /**
     * Get the total number of tests in this order.
     */
    public function getTotalTestsAttribute(): int
    {
        return $this->results()->count();
    }

    /**
     * Get the count of completed results.
     */
    public function getCompletedResultsCountAttribute(): int
    {
        return $this->results()->whereNotNull('result_value')->count();
    }

    /**
     * Check if all results have been entered.
     */
    public function getIsAllResultsEnteredAttribute(): bool
    {
        $total = $this->total_tests;
        $completed = $this->completed_results_count;

        return $total > 0 && $total === $completed;
    }

    /**
     * Check if order can be processed.
     */
    public function canBeProcessed(): bool
    {
        return in_array($this->status, ['pending']);
    }

    /**
     * Check if order can have results entered.
     */
    public function canEnterResults(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Check if order can be validated.
     */
    public function canBeValidated(): bool
    {
        return $this->status === 'completed' && $this->is_all_results_entered;
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['validated', 'cancelled']);
    }
}
