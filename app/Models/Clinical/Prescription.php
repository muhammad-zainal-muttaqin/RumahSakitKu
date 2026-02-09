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
 * Prescription Model
 *
 * Represents a medical prescription for a patient.
 * Contains prescription details and related items.
 *
 * @property int $id
 * @property string $prescription_number
 * @property int $patient_id
 * @property int $visit_id
 * @property int|null $medical_record_id
 * @property Carbon|null $prescription_date
 * @property string $prescription_type
 * @property string $priority
 * @property string $status
 * @property string|null $clinical_indication
 * @property string|null $allergies
 * @property int|null $prescribed_by
 * @property bool $verified_by_pharmacist
 * @property Carbon|null $verified_at
 * @property Carbon|null $dispensed_at
 * @property int|null $dispensed_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read float $total_estimated_cost
 * @property-read int $total_items
 * @property-read bool $is_ready_for_dispensing
 * @property-read Patient $patient
 * @property-read Visit $visit
 * @property-read MedicalRecord|null $medicalRecord
 * @property-read Employee|null $prescribedBy
 * @property-read Employee|null $dispensedBy
 * @property-read Collection|PrescriptionItem[] $items
 *
 * @method static Builder|Prescription withStatus(string $status)
 * @method static Builder|Prescription pending()
 * @method static Builder|Prescription completed()
 * @method static Builder|Prescription byType(string $type)
 * @method static Builder|Prescription onDate($date)
 * @method static Builder|Prescription byDoctor(int $doctorId)
 */
class Prescription extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'prescriptions';

    protected $fillable = [
        'prescription_number',
        'patient_id',
        'visit_id',
        'medical_record_id',
        'prescription_date',
        'prescription_type',
        'priority',
        'status',
        'clinical_indication',
        'allergies',
        'prescribed_by',
        'verified_by_pharmacist',
        'verified_at',
        'dispensed_at',
        'dispensed_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'prescription_date' => 'date',
        'verified_at' => 'datetime',
        'dispensed_at' => 'datetime',
        'verified_by_pharmacist' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the patient that owns this prescription.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the visit associated with this prescription.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the medical record associated with this prescription.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Get the employee who prescribed this prescription.
     */
    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'prescribed_by');
    }

    /**
     * Get the pharmacist who dispensed this prescription.
     */
    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'dispensed_by');
    }

    /**
     * Get all prescription items for this prescription.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id');
    }

    /**
     * Scope a query to filter by prescription status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending prescriptions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include completed prescriptions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'dispensed');
    }

    /**
     * Scope a query to filter by prescription type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('prescription_type', $type);
    }

    /**
     * Scope a query to filter by prescription date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('prescription_date', $date);
    }

    /**
     * Scope a query to only include prescriptions by a specific doctor.
     */
    public function scopeByDoctor($query, int $doctorId)
    {
        return $query->where('prescribed_by', $doctorId);
    }

    /**
     * Get the total estimated cost of all prescription items.
     */
    public function getTotalEstimatedCostAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });
    }

    /**
     * Get the total number of items in this prescription.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * Check if prescription is ready for dispensing.
     */
    public function getIsReadyForDispensingAttribute(): bool
    {
        return $this->status === 'pending' && $this->verified_by_pharmacist;
    }
}
