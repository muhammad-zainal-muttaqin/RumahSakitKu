<?php

declare(strict_types=1);

namespace App\Models\Patient;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\Patient\PatientFactory;
use App\Models\Clinical\MedicalRecord;
use App\Models\Clinical\Prescription;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Concerns\HasMedicalRecordNumber;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Patient Model
 *
 * Represents a patient in the hospital system.
 * Contains personal information, contact details, and insurance information.
 *
 * @property int $id
 * @property string $medical_record_number
 * @property string $name
 * @property string $nik
 * @property string|null $birth_place
 * @property Carbon|null $birth_date
 * @property string $gender
 * @property string|null $blood_type
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $marital_status
 * @property string|null $occupation
 * @property string $insurance_type
 * @property string|null $insurance_number
 * @property string|null $bpjs_card_number
 * @property string|null $photo_path
 * @property bool $is_active
 * @property Carbon|null $registered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read int $age
 * @property-read string $full_address
 * @property-read Collection|Visit[] $visits
 * @property-read Collection|MedicalRecord[] $medicalRecords
 * @property-read Collection|Prescription[] $prescriptions
 *
 * @method static Builder|Patient active()
 * @method static Builder|Patient search(string $search)
 * @method static Builder|Patient byInsuranceType(string $type)
 * @method static Builder|Patient byMedicalRecordNumber(string $mrn)
 * @method static Builder|Patient byMrnDateRange(string $startDate, string $endDate)
 * @method static PatientFactory factory($count = null, $state = [])
 */
class Patient extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;
    use HasMedicalRecordNumber;

    protected $table = 'patients';

    protected $fillable = [
        'medical_record_number',
        'name',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'blood_type',
        'address',
        'phone_primary',
        'phone_secondary',
        'email',
        'emergency_name',
        'emergency_phone',
        'marital_status',
        'occupation',
        'insurance_name',
        'insurance_number',
        'bpjs_number',
        'bpjs_ppk_code',
        'bpjs_class',
        'photo_path',
        'is_active',
        'registered_at',
        'created_by',
        'updated_by',
        'education',
        'nationality',
        'religion',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'emergency_relation',
        'emergency_address',
        'insurance_card_path',
        'mother_patient_id',
        'first_visit_at',
        'last_visit_at',
        'total_visits',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registered_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all visits for this patient.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'patient_id');
    }

    /**
     * Get all medical records for this patient.
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }

    /**
     * Get all prescriptions for this patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    /**
     * Scope a query to only include active patients.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search by name or medical record number.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('medical_record_number', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%")
                ->orWhere('bpjs_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by insurance name.
     */
    public function scopeByInsuranceType($query, string $type)
    {
        return $query->where('insurance_name', $type);
    }

    /**
     * Get the patient's age attribute.
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date?->age ?? 0;
    }

    /**
     * Get the patient's full address with formatting.
     */
    public function getFullAddressAttribute(): string
    {
        return $this->address ?? '-';
    }

    /**
     * Backward-compatible mass assignment aliases used by older tests/flows.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeLegacyAttributes(array $attributes): array
    {
        if (array_key_exists('phone', $attributes) && !array_key_exists('phone_primary', $attributes)) {
            $attributes['phone_primary'] = $attributes['phone'];
        }
        if (array_key_exists('emergency_contact_name', $attributes) && !array_key_exists('emergency_name', $attributes)) {
            $attributes['emergency_name'] = $attributes['emergency_contact_name'];
        }
        if (array_key_exists('emergency_contact_phone', $attributes) && !array_key_exists('emergency_phone', $attributes)) {
            $attributes['emergency_phone'] = $attributes['emergency_contact_phone'];
        }
        if (array_key_exists('insurance_type', $attributes) && !array_key_exists('insurance_name', $attributes)) {
            $attributes['insurance_name'] = $attributes['insurance_type'];
        }
        if (array_key_exists('bpjs_card_number', $attributes) && !array_key_exists('bpjs_number', $attributes)) {
            $attributes['bpjs_number'] = $attributes['bpjs_card_number'];
        }

        unset(
            $attributes['phone'],
            $attributes['emergency_contact_name'],
            $attributes['emergency_contact_phone'],
            $attributes['insurance_type'],
            $attributes['bpjs_card_number']
        );

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        return parent::fill($this->normalizeLegacyAttributes($attributes));
    }

    // ==================== CACHING METHODS ====================

    /**
     * Find a patient with caching.
     *
     * @param int $id
     * @return static|null
     */
    public static function findCached(int $id): ?self
    {
        return CacheService::getPatient($id);
    }

    /**
     * Get patient by medical record number with caching.
     *
     * @param string $mrn
     * @return static|null
     */
    public static function findByMrnCached(string $mrn): ?self
    {
        return Cache::remember(
            "patient:mrn:{$mrn}",
            3600,
            fn () => self::byMedicalRecordNumber($mrn)->first()
        );
    }

    /**
     * Clear patient cache when saved.
     */
    protected static function booted(): void
    {
        static::creating(function (self $patient): void {
            if (empty($patient->address)) {
                $patient->address = '-';
            }

            if (empty($patient->bpjs_class)) {
                $patient->bpjs_class = 'Non-BPJS';
            }

            if (empty($patient->nationality)) {
                $patient->nationality = 'Indonesia';
            }

            if ($patient->is_active === null) {
                $patient->is_active = true;
            }

            if ($patient->total_visits === null) {
                $patient->total_visits = 0;
            }
        });

        static::saved(function (self $patient): void {
            CacheService::forgetPatient($patient->id);
            Cache::forget("patient:mrn:{$patient->medical_record_number}");
        });

        static::deleted(function (self $patient): void {
            CacheService::forgetPatient($patient->id);
            Cache::forget("patient:mrn:{$patient->medical_record_number}");
        });
    }
}
