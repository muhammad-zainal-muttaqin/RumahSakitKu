<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Clinical\Assessment;
use App\Models\Clinical\Prescription;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Model
 *
 * Represents a hospital staff member/employee.
 * Contains personal info, professional credentials, and work status.
 *
 * @property int $id
 * @property string $employee_code
 * @property string|null $nip
 * @property string $name
 * @property string|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string $employee_type
 * @property bool $is_doctor
 * @property string|null $doctor_title
 * @property string|null $sip_number
 * @property Carbon|null $sip_expiry_date
 * @property string|null $str_number
 * @property Carbon|null $str_expiry_date
 * @property int|null $specialist_polyclinic_id
 * @property bool $is_nurse
 * @property string|null $sip_nurse_number
 * @property string|null $profession
 * @property string|null $certification_number
 * @property Carbon|null $join_date
 * @property Carbon|null $resign_date
 * @property string $status
 * @property string|null $photo_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read int $today_visit_count
 * @property-read int $total_patients
 * @property-read string $sip_status
 * @property-read string $str_status
 * @property-read string|null $license_expiry_warning
 * @property-read int $age
 * @property-read int $years_of_service
 * @property-read string $full_name_with_title
 * @property-read string $status_color
 * @property-read string $employee_type_label
 * @property-read Polyclinic|null $specialistPolyclinic
 * @property-read Collection|Visit[] $visits
 * @property-read Collection|Assessment[] $assessments
 * @property-read Collection|Prescription[] $prescriptions
 *
 * @method static Builder|Employee active()
 * @method static Builder|Employee byType(string $type)
 * @method static Builder|Employee doctors()
 * @method static Builder|Employee nurses()
 * @method static Builder|Employee pharmacists()
 * @method static Builder|Employee inPolyclinic(int $polyclinicId)
 * @method static Builder|Employee search(string $search)
 * @method static Builder|Employee byStatus(string $status)
 */
class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'employees';

    protected $fillable = [
        'employee_code',
        'nip',
        'name',
        'gender',
        'birth_date',
        'address',
        'phone',
        'email',
        'employee_type',
        'is_doctor',
        'doctor_title',
        'sip_number',
        'sip_expiry_date',
        'str_number',
        'str_expiry_date',
        'specialist_polyclinic_id',
        'is_nurse',
        'sip_nurse_number',
        'profession',
        'certification_number',
        'join_date',
        'resign_date',
        'status',
        'photo_path',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'sip_expiry_date' => 'date',
        'str_expiry_date' => 'date',
        'join_date' => 'date',
        'resign_date' => 'date',
        'is_doctor' => 'boolean',
        'is_nurse' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Normalize legacy aliases used by older payloads/tests.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeLegacyAttributes(array $attributes): array
    {
        if (array_key_exists('gender', $attributes)) {
            $attributes['gender'] = match (strtolower((string) $attributes['gender'])) {
                'male', 'l' => 'L',
                'female', 'p' => 'P',
                default => $attributes['gender'],
            };
        }

        if (array_key_exists('employee_type', $attributes)) {
            $allowedTypes = ['tetap', 'kontrak', 'honorer', 'outsourcing'];
            if (!in_array($attributes['employee_type'], $allowedTypes, true)) {
                $attributes['employee_type'] = 'tetap';
            }
        }

        if (array_key_exists('employee_number', $attributes) && !array_key_exists('employee_code', $attributes)) {
            $attributes['employee_code'] = $attributes['employee_number'];
        }

        if (array_key_exists('polyclinic_id', $attributes) && !array_key_exists('specialist_polyclinic_id', $attributes)) {
            $attributes['specialist_polyclinic_id'] = $attributes['polyclinic_id'];
        }

        unset($attributes['employee_number'], $attributes['polyclinic_id']);

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
     * Get the polyclinic where this employee works (if doctor specialist).
     */
    public function specialistPolyclinic(): BelongsTo
    {
        return $this->belongsTo(Polyclinic::class, 'specialist_polyclinic_id');
    }

    /**
     * Get all visits where this employee is the doctor.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'doctor_id');
    }

    /**
     * Get all assessments performed by this employee.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'assessed_by');
    }

    /**
     * Get all prescriptions prescribed by this employee.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'prescribed_by');
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope a query to filter by employee type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('employee_type', $type);
    }

    /**
     * Scope a query to only include doctors.
     */
    public function scopeDoctors($query)
    {
        return $query->where('is_doctor', true);
    }

    /**
     * Scope a query to only include nurses.
     */
    public function scopeNurses($query)
    {
        return $query->where('is_nurse', true);
    }

    /**
     * Scope a query to only include pharmacists.
     */
    public function scopePharmacists($query)
    {
        return $query->where('profession', 'like', '%farmasi%');
    }

    /**
     * Scope a query to filter by specialist polyclinic.
     */
    public function scopeInPolyclinic($query, int $polyclinicId)
    {
        return $query->where('specialist_polyclinic_id', $polyclinicId);
    }

    /**
     * Scope a query to search by name, NIP, or employee code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%")
                ->orWhere('employee_code', 'like', "%{$search}%")
                ->orWhere('sip_number', 'like', "%{$search}%")
                ->orWhere('str_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get today's visit count.
     */
    public function getTodayVisitCountAttribute(): int
    {
        return $this->visits()
            ->whereDate('registration_date', today())
            ->count();
    }

    /**
     * Get total patient count (unique patients).
     */
    public function getTotalPatientsAttribute(): int
    {
        return $this->visits()
            ->distinct('patient_id')
            ->count('patient_id');
    }

    /**
     * Check if SIP is expired or expiring soon.
     */
    public function getSipStatusAttribute(): string
    {
        if (!$this->sip_expiry_date) {
            return 'no_license';
        }

        if ($this->sip_expiry_date->isPast()) {
            return 'expired';
        }

        if (now()->diffInDays($this->sip_expiry_date) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * Check if STR is expired or expiring soon.
     */
    public function getStrStatusAttribute(): string
    {
        if (!$this->str_expiry_date) {
            return 'no_license';
        }

        if ($this->str_expiry_date->isPast()) {
            return 'expired';
        }

        if (now()->diffInDays($this->str_expiry_date) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * Get any license expiry warning.
     */
    public function getLicenseExpiryWarningAttribute(): ?string
    {
        $warnings = [];

        if ($this->is_doctor) {
            if ($this->sip_status === 'expired') {
                $warnings[] = 'SIP telah expired';
            } elseif ($this->sip_status === 'expiring_soon') {
                $warnings[] = 'SIP akan expired dalam ' . now()->diffInDays($this->sip_expiry_date) . ' hari';
            }

            if ($this->str_status === 'expired') {
                $warnings[] = 'STR telah expired';
            } elseif ($this->str_status === 'expiring_soon') {
                $warnings[] = 'STR akan expired dalam ' . now()->diffInDays($this->str_expiry_date) . ' hari';
            }
        }

        return empty($warnings) ? null : implode(', ', $warnings);
    }

    /**
     * Get employee age.
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date?->age ?? 0;
    }

    /**
     * Get years of service.
     */
    public function getYearsOfServiceAttribute(): int
    {
        return (int) ($this->join_date?->diffInYears(now()) ?? 0);
    }

    /**
     * Get full name with title.
     */
    public function getFullNameWithTitleAttribute(): string
    {
        $title = '';
        if ($this->is_doctor) {
            $title = $this->doctor_title ?? 'dr.';
        }

        return trim("{$title} {$this->name}");
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'aktif' => 'success',
            'cuti' => 'warning',
            'nonaktif' => 'danger',
            'pensiun' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get employee type label.
     */
    public function getEmployeeTypeLabelAttribute(): string
    {
        return match ($this->employee_type) {
            'tetap' => 'Tetap',
            'kontrak' => 'Kontrak',
            'honorer' => 'Honorer',
            'outsourcing' => 'Outsourcing',
            default => ucfirst($this->employee_type),
        };
    }

    /**
     * Backward-compatible alias for legacy `employee_number` field.
     */
    public function getEmployeeNumberAttribute(): string
    {
        return $this->employee_code;
    }

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if (empty($employee->polyclinic_id) && !empty($employee->specialist_polyclinic_id)) {
                $employee->setAttribute('polyclinic_id', $employee->specialist_polyclinic_id);
            }

            if (empty($employee->specialist_polyclinic_id) && !empty($employee->polyclinic_id)) {
                $employee->specialist_polyclinic_id = $employee->polyclinic_id;
            }

            if (empty($employee->employee_code)) {
                $employee->employee_code = 'EMP' . strtoupper(substr(uniqid('', true), -8));
            }

            if (empty($employee->gender)) {
                $employee->gender = 'L';
            }

            if (empty($employee->employee_type)) {
                $employee->employee_type = 'tetap';
            }

            if (empty($employee->join_date)) {
                $employee->join_date = now()->toDateString();
            }

            if (empty($employee->status)) {
                $employee->status = 'aktif';
            }
        });
    }
}
