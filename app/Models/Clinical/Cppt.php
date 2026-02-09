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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CPPT Model (Catatan Perkembangan Pasien Terintegrasi)
 *
 * Represents an integrated patient progress note.
 * Contains SOAP format notes for ongoing patient care.
 *
 * @property int $id
 * @property int $medical_record_id
 * @property int $patient_id
 * @property int $visit_id
 * @property Carbon|null $cppt_date
 * @property Carbon|null $cppt_time
 * @property string|null $subjective
 * @property string|null $objective
 * @property string|null $assessment
 * @property string|null $plan
 * @property string|null $instruction
 * @property string|null $progress_notes
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property bool $is_verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read array $soap_array
 * @property-read string $full_soap_note
 * @property-read MedicalRecord $medicalRecord
 * @property-read Patient $patient
 * @property-read Visit $visit
 * @property-read Employee|null $verifiedBy
 *
 * @method static Builder|Cppt verified()
 * @method static Builder|Cppt unverified()
 * @method static Builder|Cppt onDate($date)
 * @method static Builder|Cppt byCreator(int $userId)
 */
class Cppt extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'cppts';

    protected $fillable = [
        'medical_record_id',
        'patient_id',
        'visit_id',
        'cppt_date',
        'cppt_time',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'instruction',
        'progress_notes',
        'verified_by',
        'verified_at',
        'is_verified',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cppt_date' => 'date',
        'cppt_time' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the medical record that owns this CPPT.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Get the patient that owns this CPPT.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the visit associated with this CPPT.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the employee who verified this CPPT.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'verified_by');
    }

    /**
     * Scope a query to only include verified CPPTs.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include unverified CPPTs.
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope a query to filter by CPPT date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('cppt_date', $date);
    }

    /**
     * Scope a query to filter by creator.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Get the SOAP note as a formatted array.
     */
    public function getSoapArrayAttribute(): array
    {
        return [
            'subjective' => $this->subjective,
            'objective' => $this->objective,
            'assessment' => $this->assessment,
            'plan' => $this->plan,
        ];
    }

    /**
     * Get the full SOAP note as formatted text.
     */
    public function getFullSoapNoteAttribute(): string
    {
        return <<<SOAP
**Subjective:**
{$this->subjective}

**Objective:**
{$this->objective}

**Assessment:**
{$this->assessment}

**Plan:**
{$this->plan}
SOAP;
    }
}
