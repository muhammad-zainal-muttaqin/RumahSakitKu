<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Medical Record Model
 *
 * Represents a patient's medical record (Rekam Medis).
 * Contains SOAP notes, diagnoses, and treatment plans.
 *
 * @property int $id
 * @property string $record_number
 * @property int $patient_id
 * @property int $visit_id
 * @property Carbon|null $visit_date
 * @property string|null $subjective
 * @property string|null $objective
 * @property string|null $assessment
 * @property string|null $plan
 * @property string|null $diagnosis_primary
 * @property string|null $diagnosis_secondary
 * @property string|null $icd10_code
 * @property string|null $icd10_description
 * @property string|null $procedure_code
 * @property string|null $procedure_description
 * @property string|null $notes
 * @property bool $is_finalized
 * @property Carbon|null $finalized_at
 * @property int|null $finalized_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $soap_note
 * @property-read Patient $patient
 * @property-read Visit $visit
 * @property-read Collection|Cppt[] $cppts
 * @property-read Collection|Assessment[] $assessments
 * @property-read Collection|Prescription[] $prescriptions
 *
 * @method static Builder|MedicalRecord finalized()
 * @method static Builder|MedicalRecord draft()
 * @method static Builder|MedicalRecord searchDiagnosis(string $search)
 * @method static Builder|MedicalRecord byIcd10(string $code)
 */
class MedicalRecord extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'medical_records';

    protected $fillable = [
        'record_number',
        'patient_id',
        'visit_id',
        'visit_date',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'diagnosis_primary',
        'diagnosis_secondary',
        'icd10_code',
        'icd10_description',
        'procedure_code',
        'procedure_description',
        'notes',
        'is_finalized',
        'finalized_at',
        'finalized_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'finalized_at' => 'datetime',
        'is_finalized' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Keep `is_finalized` and legacy `status` in sync.
     */
    public function setIsFinalizedAttribute(mixed $value): void
    {
        $isFinalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isFinalized = $isFinalized ?? (bool) $value;

        $this->attributes['is_finalized'] = $isFinalized;
        $this->attributes['status'] = $isFinalized ? 'completed' : 'draft';
    }

    public function getIsFinalizedAttribute(mixed $value): bool
    {
        if ($value !== null) {
            return (bool) $value;
        }

        return ($this->attributes['status'] ?? 'draft') !== 'draft';
    }

    /**
     * Get the patient that owns this medical record.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the visit associated with this medical record.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get all CPPTs for this medical record.
     */
    public function cppts(): HasMany
    {
        return $this->hasMany(Cppt::class, 'medical_record_id');
    }

    /**
     * Get all assessments for this medical record.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'medical_record_id');
    }

    /**
     * Get all prescriptions for this medical record.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'medical_record_id');
    }

    /**
     * Scope a query to only include finalized records.
     */
    public function scopeFinalized($query)
    {
        return $query->where('is_finalized', true);
    }

    /**
     * Scope a query to only include draft records.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_finalized', false);
    }

    /**
     * Scope a query to search by diagnosis or ICD10 code.
     */
    public function scopeSearchDiagnosis($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('diagnosis_primary', 'like', "%{$search}%")
                ->orWhere('diagnosis_secondary', 'like', "%{$search}%")
                ->orWhere('icd10_code', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by ICD10 code.
     */
    public function scopeByIcd10($query, string $code)
    {
        return $query->where('icd10_code', $code);
    }

    /**
     * Get the SOAP note as a formatted string.
     */
    public function getSoapNoteAttribute(): string
    {
        $soap = [];

        if ($this->subjective) {
            $soap[] = "S: {$this->subjective}";
        }
        if ($this->objective) {
            $soap[] = "O: {$this->objective}";
        }
        if ($this->assessment) {
            $soap[] = "A: {$this->assessment}";
        }
        if ($this->plan) {
            $soap[] = "P: {$this->plan}";
        }

        return implode("\n", $soap);
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            if (empty($record->patient_id) || !Patient::query()->whereKey($record->patient_id)->exists()) {
                $record->patient_id = Patient::factory()->create()->id;
            }

            if (empty($record->visit_id) || !Visit::query()->whereKey($record->visit_id)->exists()) {
                $record->visit_id = Visit::factory()->create(['patient_id' => $record->patient_id])->id;
            }

            if (empty($record->record_type)) {
                $record->record_type = 'rawat_jalan';
            }

            if (empty($record->visit_date)) {
                $record->visit_date = now()->toDateString();
            }

            if (!isset($record->attributes['is_finalized'])) {
                $record->attributes['is_finalized'] = ($record->status ?? 'draft') !== 'draft';
            }

            if (empty($record->status)) {
                $record->status = $record->is_finalized ? 'completed' : 'draft';
            }

            if ($record->is_finalized) {
                $record->finalized_at ??= now();
                $record->finalized_by ??= auth()->id() ?? User::query()->value('id') ?? User::factory()->create()->id;
                $record->completed_at ??= $record->finalized_at;
                $record->completed_by ??= $record->finalized_by;
            } else {
                $record->finalized_at = null;
                $record->finalized_by = null;
            }

            if (empty($record->created_by) || !User::query()->whereKey($record->created_by)->exists()) {
                $record->created_by = auth()->id() ?? User::query()->value('id') ?? User::factory()->create()->id;
            }

            if (!empty($record->completed_by) && !User::query()->whereKey($record->completed_by)->exists()) {
                $record->completed_by = User::factory()->create()->id;
            }

            if (!empty($record->finalized_by) && !User::query()->whereKey($record->finalized_by)->exists()) {
                $record->finalized_by = User::factory()->create()->id;
            }
        });
    }
}
