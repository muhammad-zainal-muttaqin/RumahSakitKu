<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Radiology Order Model
 *
 * Represents a radiology/imaging examination order.
 * Manages X-ray, CT scan, MRI, and other imaging requests.
 *
 * @property int $id
 * @property string $order_number
 * @property int $visit_id
 * @property int $patient_id
 * @property int|null $doctor_id
 * @property int|null $medical_record_id
 * @property string $examination_type
 * @property string|null $body_area
 * @property string|null $position
 * @property bool $contrast
 * @property string|null $contrast_type
 * @property string|null $clinical_indication
 * @property Carbon|null $scheduled_date
 * @property string $priority
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read string $examination_type_label
 * @property-read string $priority_color
 * @property-read string $priority_label
 * @property-read string $contrast_label
 * @property-read string $examination_info
 * @property-read Visit $visit
 * @property-read Patient $patient
 * @property-read Employee|null $doctor
 * @property-read MedicalRecord|null $medicalRecord
 * @property-read RadiologyResult|null $result
 *
 * @method static Builder|RadiologyOrder withStatus(string $status)
 * @method static Builder|RadiologyOrder pending()
 * @method static Builder|RadiologyOrder scheduled()
 * @method static Builder|RadiologyOrder inProgress()
 * @method static Builder|RadiologyOrder completed()
 * @method static Builder|RadiologyOrder reported()
 * @method static Builder|RadiologyOrder today()
 * @method static Builder|RadiologyOrder byExaminationType(string $type)
 */
class RadiologyOrder extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'radiology_orders';

    protected $fillable = [
        'order_number',
        'visit_id',
        'patient_id',
        'doctor_id',
        'medical_record_id',
        'examination_type',
        'body_area',
        'position',
        'contrast',
        'contrast_type',
        'clinical_indication',
        'scheduled_date',
        'priority',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'scheduled_date' => 'datetime',
        'contrast' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the visit associated with this radiology order.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the patient associated with this radiology order.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the doctor who ordered this radiology exam.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    /**
     * Get the medical record associated with this radiology order.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Get the radiology result for this order.
     */
    public function result(): HasOne
    {
        return $this->hasOne(RadiologyResult::class, 'radiology_order_id');
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
     * Scope a query to only include scheduled orders.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
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
     * Scope a query to only include reported orders.
     */
    public function scopeReported($query)
    {
        return $query->where('status', 'reported');
    }

    /**
     * Scope a query to only include orders from today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope a query to filter by examination type.
     */
    public function scopeByExaminationType($query, string $type)
    {
        return $query->where('examination_type', $type);
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'scheduled' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'reported' => 'primary',
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
            'pending' => 'Menunggu',
            'scheduled' => 'Terjadwal',
            'in_progress' => 'Sedang Dikerjakan',
            'completed' => 'Selesai',
            'reported' => 'Sudah Dibaca',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get examination type label.
     */
    public function getExaminationTypeLabelAttribute(): string
    {
        return match ($this->examination_type) {
            'xray' => 'Rontgen',
            'ct_scan' => 'CT Scan',
            'mri' => 'MRI',
            'usg' => 'USG',
            'mammografi' => 'Mammografi',
            'fluoroskopi' => 'Fluoroskopi',
            'angiografi' => 'Angiografi',
            'dexa' => 'DEXA',
            'pet_scan' => 'PET Scan',
            'nuklir' => 'Pencitraan Nuklir',
            default => ucfirst(str_replace('_', ' ', $this->examination_type)),
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
            'emergency' => 'danger',
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
            'emergency' => 'Emergency',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Get contrast label.
     */
    public function getContrastLabelAttribute(): string
    {
        if (!$this->contrast) {
            return 'Tanpa Kontras';
        }

        return $this->contrast_type ? "Dengan {$this->contrast_type}" : 'Dengan Kontras';
    }

    /**
     * Check if order can be scheduled.
     */
    public function canBeScheduled(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order can be started.
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, ['pending', 'scheduled']);
    }

    /**
     * Check if order can have results entered.
     */
    public function canEnterResults(): bool
    {
        return in_array($this->status, ['in_progress', 'completed']);
    }

    /**
     * Check if order can be reported.
     */
    public function canBeReported(): bool
    {
        return $this->status === 'completed' && $this->result !== null;
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['reported', 'cancelled']);
    }

    /**
     * Get formatted examination info.
     */
    public function getExaminationInfoAttribute(): string
    {
        $parts = [$this->examination_type_label];

        if ($this->body_area) {
            $parts[] = $this->body_area;
        }

        if ($this->position) {
            $parts[] = $this->position;
        }

        return implode(' - ', $parts);
    }
}
