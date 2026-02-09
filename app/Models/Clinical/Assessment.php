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
 * Assessment Model
 *
 * Represents a patient assessment/evaluation during a visit.
 * Contains vital signs, physical examination, and triage information.
 *
 * @property int $id
 * @property int $medical_record_id
 * @property int $patient_id
 * @property int $visit_id
 * @property int $assessed_by
 * @property float|null $systolic_bp
 * @property float|null $diastolic_bp
 * @property float|null $pulse_rate
 * @property float|null $respiratory_rate
 * @property float|null $body_temperature
 * @property float|null $oxygen_saturation
 * @property float|null $blood_glucose
 * @property float|null $weight
 * @property float|null $height
 * @property float|null $bmi
 * @property float|null $pain_scale
 * @property string|null $pain_location
 * @property string|null $pain_description
 * @property string $consciousness
 * @property int|null $gcs_eye
 * @property int|null $gcs_verbal
 * @property int|null $gcs_motor
 * @property int|null $gcs_total
 * @property string|null $fall_risk
 * @property array|null $fall_risk_factors
 * @property string|null $allergy_history
 * @property string|null $drug_allergy
 * @property string|null $food_allergy
 * @property string $chief_complaint
 * @property string|null $present_illness_history
 * @property string|null $past_medical_history
 * @property string|null $family_history
 * @property string|null $social_history
 * @property string|null $general_condition
 * @property string|null $head_examination
 * @property string|null $neck_examination
 * @property string|null $thorax_examination
 * @property string|null $heart_examination
 * @property string|null $lung_examination
 * @property string|null $abdomen_examination
 * @property string|null $extremities_examination
 * @property string|null $neurological_examination
 * @property string|null $skin_examination
 * @property string|null $primary_diagnosis_code
 * @property string|null $primary_diagnosis_name
 * @property array|null $secondary_diagnoses
 * @property string $diagnosis_type
 * @property Carbon $assessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read string|null $blood_pressure_status
 * @property-read MedicalRecord $medicalRecord
 * @property-read Patient $patient
 * @property-read Visit $visit
 * @property-read Employee $assessedBy
 *
 * @method static Builder|Assessment byType(string $type)
 * @method static Builder|Assessment onDate($date)
 * @method static Builder|Assessment byAssessor(int $employeeId)
 */
class Assessment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'assessments';

    protected $fillable = [
        'medical_record_id',
        'patient_id',
        'visit_id',
        'assessed_by',
        'systolic_bp',
        'diastolic_bp',
        'pulse_rate',
        'respiratory_rate',
        'body_temperature',
        'oxygen_saturation',
        'blood_glucose',
        'weight',
        'height',
        'bmi',
        'pain_scale',
        'pain_location',
        'pain_description',
        'consciousness',
        'gcs_eye',
        'gcs_verbal',
        'gcs_motor',
        'gcs_total',
        'fall_risk',
        'fall_risk_factors',
        'allergy_history',
        'drug_allergy',
        'food_allergy',
        'chief_complaint',
        'present_illness_history',
        'past_medical_history',
        'family_history',
        'social_history',
        'general_condition',
        'head_examination',
        'neck_examination',
        'thorax_examination',
        'heart_examination',
        'lung_examination',
        'abdomen_examination',
        'extremities_examination',
        'neurological_examination',
        'skin_examination',
        'primary_diagnosis_code',
        'primary_diagnosis_name',
        'secondary_diagnoses',
        'diagnosis_type',
        'assessed_at',
    ];

    protected $casts = [
        'systolic_bp' => 'float',
        'diastolic_bp' => 'float',
        'pulse_rate' => 'float',
        'respiratory_rate' => 'float',
        'body_temperature' => 'float',
        'oxygen_saturation' => 'float',
        'blood_glucose' => 'float',
        'weight' => 'float',
        'height' => 'float',
        'bmi' => 'float',
        'pain_scale' => 'float',
        'gcs_eye' => 'integer',
        'gcs_verbal' => 'integer',
        'gcs_motor' => 'integer',
        'gcs_total' => 'integer',
        'fall_risk_factors' => 'array',
        'secondary_diagnoses' => 'array',
        'assessed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Normalize legacy aliases used in older tests/flows.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeLegacyAttributes(array $attributes): array
    {
        if (array_key_exists('assessment_date', $attributes) && !array_key_exists('assessed_at', $attributes)) {
            $attributes['assessed_at'] = $attributes['assessment_date'];
        }

        if (array_key_exists('assessment_type', $attributes) && !array_key_exists('diagnosis_type', $attributes)) {
            $rawType = (string) $attributes['assessment_type'];
            $attributes['diagnosis_type'] = in_array($rawType, ['primer', 'sekunder', 'komplikasi'], true)
                ? $rawType
                : 'primer';
        }

        unset($attributes['assessment_date'], $attributes['assessment_type']);

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        return parent::fill($this->normalizeLegacyAttributes($attributes));
    }

    /**
     * Get the medical record that owns this assessment.
     */
    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class, 'medical_record_id');
    }

    /**
     * Get the patient that owns this assessment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the visit associated with this assessment.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the employee who performed this assessment.
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assessed_by');
    }

    /**
     * Scope a query to filter by assessment type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('diagnosis_type', $type);
    }

    /**
     * Scope a query to filter by assessment date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('assessed_at', $date);
    }

    /**
     * Scope a query to filter by assessor.
     */
    public function scopeByAssessor($query, int $employeeId)
    {
        return $query->where('assessed_by', $employeeId);
    }

    /**
     * Get blood pressure status.
     */
    public function getBloodPressureStatusAttribute(): ?string
    {
        $systolic = $this->systolic_bp;
        $diastolic = $this->diastolic_bp;

        if ($systolic === null || $diastolic === null) {
            return null;
        }

        if ($systolic < 120 && $diastolic < 80) {
            return 'normal';
        } elseif ($systolic < 130 && $diastolic < 80) {
            return 'elevated';
        } elseif ($systolic < 140 || $diastolic < 90) {
            return 'stage1';
        } else {
            return 'stage2';
        }
    }

    protected static function booted(): void
    {
        static::creating(function (self $assessment): void {
            if (empty($assessment->patient_id) || !Patient::query()->whereKey($assessment->patient_id)->exists()) {
                $assessment->patient_id = Patient::factory()->create()->id;
            }

            if (empty($assessment->visit_id) || !Visit::query()->whereKey($assessment->visit_id)->exists()) {
                $assessment->visit_id = Visit::factory()->create(['patient_id' => $assessment->patient_id])->id;
            }

            if (empty($assessment->medical_record_id) || !MedicalRecord::query()->whereKey($assessment->medical_record_id)->exists()) {
                $assessment->medical_record_id = MedicalRecord::factory()->create([
                    'patient_id' => $assessment->patient_id,
                    'visit_id' => $assessment->visit_id,
                ])->id;
            }

            if (empty($assessment->assessed_by)) {
                $assessment->assessed_by = Employee::factory()->create()->id;
            }

            if (empty($assessment->chief_complaint)) {
                $assessment->chief_complaint = '-';
            }

            if (empty($assessment->diagnosis_type)) {
                $assessment->diagnosis_type = 'primer';
            }

            if (empty($assessment->assessed_at)) {
                $assessment->assessed_at = now();
            }
        });
    }
}
