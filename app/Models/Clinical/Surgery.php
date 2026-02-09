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
 * Surgery Model
 *
 * Represents a surgical procedure scheduled or performed.
 * Manages operating room schedule and surgical team.
 *
 * @property int $id
 * @property string $surgery_number
 * @property int $visit_id
 * @property int $patient_id
 * @property Carbon|null $scheduled_date
 * @property Carbon|null $start_time
 * @property Carbon|null $estimated_end_time
 * @property Carbon|null $actual_start
 * @property Carbon|null $actual_end
 * @property string|null $operating_room
 * @property int|null $surgeon_id
 * @property int|null $assistant_surgeon_id
 * @property int|null $anesthesiologist_id
 * @property string|null $anesthesia_type
 * @property int|null $nurse_id
 * @property int|null $circulating_nurse_id
 * @property string|null $pre_diagnosis
 * @property string|null $post_diagnosis
 * @property string|null $procedure_name
 * @property string|null $procedure_code
 * @property string $surgery_type
 * @property string $status
 * @property bool $safety_checklist_sign_in
 * @property Carbon|null $safety_checklist_sign_in_at
 * @property bool $safety_checklist_time_out
 * @property Carbon|null $safety_checklist_time_out_at
 * @property bool $safety_checklist_sign_out
 * @property Carbon|null $safety_checklist_sign_out_at
 * @property string|null $procedure_notes
 * @property string|null $findings
 * @property string|null $complications
 * @property string|null $specimens
 * @property bool $is_postponed
 * @property string|null $postponed_reason
 * @property Carbon|null $postponed_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read int|null $duration
 * @property-read int|null $estimated_duration
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read string $surgery_type_color
 * @property-read string $surgery_type_label
 * @property-read bool $is_today
 * @property-read bool $is_overdue
 * @property-read int $safety_checklist_progress
 * @property-read bool $is_safety_checklist_complete
 * @property-read Visit $visit
 * @property-read Patient $patient
 * @property-read Employee|null $surgeon
 * @property-read Employee|null $assistantSurgeon
 * @property-read Employee|null $anesthesiologist
 * @property-read Employee|null $nurse
 * @property-read Employee|null $circulatingNurse
 * @property-read Collection|SurgeryImplant[] $implants
 *
 * @method static Builder|Surgery onDate($date)
 * @method static Builder|Surgery today()
 * @method static Builder|Surgery withStatus(string $status)
 * @method static Builder|Surgery scheduled()
 * @method static Builder|Surgery inProgress()
 * @method static Builder|Surgery completed()
 * @method static Builder|Surgery cancelled()
 * @method static Builder|Surgery byType(string $type)
 * @method static Builder|Surgery inRoom(string $room)
 * @method static Builder|Surgery bySurgeon(int $surgeonId)
 * @method static Builder|Surgery cito()
 * @method static Builder|Surgery overlapping(string $room, $start, $end, ?int $excludeId = null)
 */
class Surgery extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'surgeries';

    protected $fillable = [
        'surgery_number',
        'visit_id',
        'patient_id',
        'scheduled_date',
        'start_time',
        'estimated_end_time',
        'actual_start',
        'actual_end',
        'operating_room',
        'surgeon_id',
        'assistant_surgeon_id',
        'anesthesiologist_id',
        'anesthesia_type',
        'nurse_id',
        'circulating_nurse_id',
        'pre_diagnosis',
        'post_diagnosis',
        'procedure_name',
        'procedure_code',
        'surgery_type',
        'status',
        'safety_checklist_sign_in',
        'safety_checklist_sign_in_at',
        'safety_checklist_time_out',
        'safety_checklist_time_out_at',
        'safety_checklist_sign_out',
        'safety_checklist_sign_out_at',
        'procedure_notes',
        'findings',
        'complications',
        'specimens',
        'is_postponed',
        'postponed_reason',
        'postponed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'scheduled_date' => 'date',
        'start_time' => 'datetime',
        'estimated_end_time' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'safety_checklist_sign_in' => 'boolean',
        'safety_checklist_sign_in_at' => 'datetime',
        'safety_checklist_time_out' => 'boolean',
        'safety_checklist_time_out_at' => 'datetime',
        'safety_checklist_sign_out' => 'boolean',
        'safety_checklist_sign_out_at' => 'datetime',
        'is_postponed' => 'boolean',
        'postponed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the visit associated with this surgery.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the patient for this surgery.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the surgeon (operator).
     */
    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'surgeon_id');
    }

    /**
     * Get the assistant surgeon.
     */
    public function assistantSurgeon(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assistant_surgeon_id');
    }

    /**
     * Get the anesthesiologist.
     */
    public function anesthesiologist(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'anesthesiologist_id');
    }

    /**
     * Get the scrub nurse.
     */
    public function nurse(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nurse_id');
    }

    /**
     * Get the circulating nurse.
     */
    public function circulatingNurse(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'circulating_nurse_id');
    }

    /**
     * Get all implants used in this surgery.
     */
    public function implants(): HasMany
    {
        return $this->hasMany(SurgeryImplant::class, 'surgery_id');
    }

    /**
     * Scope a query to only include surgeries on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }

    /**
     * Scope a query to only include today's surgeries.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope a query to only include surgeries with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include scheduled surgeries.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include in-progress surgeries.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope a query to only include completed surgeries.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled surgeries.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope a query to filter by surgery type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('surgery_type', $type);
    }

    /**
     * Scope a query to filter by operating room.
     */
    public function scopeInRoom($query, string $room)
    {
        return $query->where('operating_room', $room);
    }

    /**
     * Scope a query to filter by surgeon.
     */
    public function scopeBySurgeon($query, int $surgeonId)
    {
        return $query->where('surgeon_id', $surgeonId);
    }

    /**
     * Scope a query to get CITO/emergency surgeries.
     */
    public function scopeCito($query)
    {
        return $query->whereIn('surgery_type', ['cito', 'emergency']);
    }

    /**
     * Scope to check for overlapping surgeries in a room.
     */
    public function scopeOverlapping($query, string $room, $start, $end, ?int $excludeId = null)
    {
        return $query->where('operating_room', $room)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereNotNull('start_time')
            ->whereNotNull('estimated_end_time')
            // Strict interval overlap: start < newEnd AND end > newStart.
            // This allows exact back-to-back scheduling (end == next start).
            ->where('start_time', '<', $end)
            ->where('estimated_end_time', '>', $start)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }

    /**
     * Get the surgery duration in minutes.
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->actual_start && $this->actual_end) {
            return (int) $this->actual_start->diffInMinutes($this->actual_end);
        }

        return null;
    }

    /**
     * Get the estimated duration in minutes.
     */
    public function getEstimatedDurationAttribute(): ?int
    {
        if ($this->start_time && $this->estimated_end_time) {
            return (int) $this->start_time->diffInMinutes($this->estimated_end_time);
        }

        return null;
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'info',
            'preparation' => 'warning',
            'in_progress' => 'primary',
            'completed' => 'success',
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
            'scheduled' => 'Terjadwal',
            'preparation' => 'Persiapan',
            'in_progress' => 'Sedang Berlangsung',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get surgery type color.
     */
    public function getSurgeryTypeColorAttribute(): string
    {
        return match ($this->surgery_type) {
            'elektif' => 'info',
            'urgent' => 'warning',
            'cito' => 'danger',
            'emergency' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get surgery type label.
     */
    public function getSurgeryTypeLabelAttribute(): string
    {
        return match ($this->surgery_type) {
            'elektif' => 'Elektif',
            'urgent' => 'Urgent',
            'cito' => 'CITO',
            'emergency' => 'Emergency',
            default => ucfirst($this->surgery_type),
        };
    }

    /**
     * Check if surgery is scheduled for today.
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->scheduled_date?->isToday() ?? false;
    }

    /**
     * Check if surgery is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->scheduled_date?->isPast() &&
            !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get safety checklist completion percentage.
     */
    public function getSafetyChecklistProgressAttribute(): int
    {
        $completed = 0;
        if ($this->safety_checklist_sign_in) $completed++;
        if ($this->safety_checklist_time_out) $completed++;
        if ($this->safety_checklist_sign_out) $completed++;

        return (int) round(($completed / 3) * 100);
    }

    /**
     * Check if all safety checklists are completed.
     */
    public function getIsSafetyChecklistCompleteAttribute(): bool
    {
        return $this->safety_checklist_sign_in
            && $this->safety_checklist_time_out
            && $this->safety_checklist_sign_out;
    }

    /**
     * Get available operating rooms.
     * @return array<string, string>
     */
    public static function getOperatingRooms(): array
    {
        return [
            'OK1' => 'OK 1',
            'OK2' => 'OK 2',
            'OK3' => 'OK 3',
            'OK4' => 'OK 4',
            'OK5' => 'OK 5',
            'OK6' => 'OK 6',
            'OK_CITO' => 'OK CITO/Emergency',
            'OK_RSIA' => 'OK RSIA',
        ];
    }

    /**
     * Get available anesthesia types.
     * @return array<string, string>
     */
    public static function getAnesthesiaTypes(): array
    {
        return [
            'umum' => 'Anestesi Umum (General)',
            'spinal' => 'Spinal/Epidural',
            'lokal' => 'Lokal',
            'blok' => 'Blok Saraf (Nerve Block)',
            'sedasi' => 'Sedasi',
            'tiva' => 'TIVA',
        ];
    }
}
