<?php

declare(strict_types=1);

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Clinical\MedicalRecord;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Financial\Invoice;
use App\Models\MasterData\Bed;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\MasterData\Room;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Visit Model
 *
 * Represents a patient visit/registration in the hospital system.
 * Tracks visit information, status, and related entities.
 *
 * @property int $id
 * @property string $visit_number
 * @property int $patient_id
 * @property int|null $polyclinic_id
 * @property int|null $doctor_id
 * @property Carbon|null $registration_date
 * @property string $visit_type
 * @property string $visit_status
 * @property string $payment_type
 * @property string $priority
 * @property string|null $bpjs_sep_number
 * @property Carbon|null $completed_at
 * @property Carbon|null $examination_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $registered_by
 *
 * @property-read Patient $patient
 * @property-read Polyclinic|null $polyclinic
 * @property-read Employee|null $doctor
 * @property-read MedicalRecord|null $medicalRecord
 * @property-read Invoice|null $invoice
 *
 * @method static Builder|Visit onDate($date)
 * @method static Builder|Visit withStatus(string $status)
 * @method static Builder|Visit today()
 * @method static Builder|Visit active()
 * @method static Builder|Visit byType(string $type)
 * @method static Builder|Visit byPriority(string $priority)
 */
class Visit extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'visits';
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = [
        'visit_number',
        'patient_id',
        'polyclinic_id',
        'doctor_id',
        'registration_date',
        'visit_type',
        'visit_status',
        'payment_type',
        'priority',
        'bpjs_sep_number',
        'registered_by',
        'queue_number',
        'notes',
        'completed_at',
        'examination_at',
        'arrived_at',
        'triage_at',
        'assessment_at',
        'prescription_at',
        'payment_at',
        'admission_date',
        'discharge_date',
        'room_id',
        'bed_id',
        'insurance_name',
        'insurance_number',
        'bpjs_rujukan_number',
        'bpjs_rujukan_date',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'visit_date' => 'datetime',
        'completed_at' => 'datetime',
        'examination_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'transferred_at' => 'datetime',
        'arrived_at' => 'datetime',
        'triage_at' => 'datetime',
        'assessment_at' => 'datetime',
        'prescription_at' => 'datetime',
        'payment_at' => 'datetime',
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'is_completed' => 'boolean',
        'bpjs_rujukan_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Normalize common legacy aliases used in tests and older flows.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeLegacyAttributes(array $attributes): array
    {
        if (array_key_exists('visit_date', $attributes)) {
            $attributes['registration_date'] = $attributes['visit_date'];
        }

        if (array_key_exists('status', $attributes) && !array_key_exists('visit_status', $attributes)) {
            $attributes['visit_status'] = $this->normalizeStatusValue((string) $attributes['status']);
        }

        if (array_key_exists('check_in_at', $attributes) && !array_key_exists('arrived_at', $attributes)) {
            $attributes['arrived_at'] = $attributes['check_in_at'];
        }

        if (array_key_exists('check_out_at', $attributes) && !array_key_exists('completed_at', $attributes)) {
            $attributes['completed_at'] = $attributes['check_out_at'];
        }

        unset($attributes['visit_date'], $attributes['status'], $attributes['check_in_at'], $attributes['check_out_at']);

        return $attributes;
    }

    private function normalizeStatusValue(string $status): string
    {
        return match (strtolower($status)) {
            'completed', 'selesai' => 'selesai',
            'cancelled', 'batal' => 'batal',
            'in_progress', 'proses' => 'proses',
            'waiting', 'menunggu' => 'menunggu',
            'registered', 'pending', 'pendaftaran' => 'pendaftaran',
            default => $status,
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        $legacy = [];
        $legacyKeys = [
            'visit_date',
            'status',
            'check_in_at',
            'check_out_at',
            'discharge_status',
            'inpatient_status',
            'transfer_reason',
            'transferred_at',
            'is_completed',
            'discharge_diagnosis',
            'discharge_notes',
            'satusehat_encounter_id',
        ];

        foreach ($legacyKeys as $key) {
            if (array_key_exists($key, $attributes)) {
                $legacy[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        // Keep legacy aliases synchronized when only modern fields are provided.
        if (!array_key_exists('status', $legacy) && array_key_exists('visit_status', $attributes)) {
            $legacy['status'] = $this->mapVisitStatusToLegacy((string) $attributes['visit_status']);
        }
        if (!array_key_exists('visit_date', $legacy) && array_key_exists('registration_date', $attributes)) {
            $legacy['visit_date'] = $attributes['registration_date'];
        }
        if (!array_key_exists('check_in_at', $legacy) && array_key_exists('arrived_at', $attributes)) {
            $legacy['check_in_at'] = $attributes['arrived_at'];
        }
        if (!array_key_exists('check_out_at', $legacy) && array_key_exists('completed_at', $attributes)) {
            $legacy['check_out_at'] = $attributes['completed_at'];
        }

        $normalizedInput = $attributes;

        if (array_key_exists('visit_date', $legacy) && !array_key_exists('registration_date', $normalizedInput)) {
            $normalizedInput['visit_date'] = $legacy['visit_date'];
        }
        if (array_key_exists('status', $legacy) && !array_key_exists('visit_status', $normalizedInput)) {
            $normalizedInput['status'] = $legacy['status'];
        }
        if (array_key_exists('check_in_at', $legacy) && !array_key_exists('arrived_at', $normalizedInput)) {
            $normalizedInput['check_in_at'] = $legacy['check_in_at'];
        }
        if (array_key_exists('check_out_at', $legacy) && !array_key_exists('completed_at', $normalizedInput)) {
            $normalizedInput['check_out_at'] = $legacy['check_out_at'];
        }

        $model = parent::fill($this->normalizeLegacyAttributes($normalizedInput));

        foreach ($legacy as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $model;
    }

    private function mapVisitStatusToLegacy(string $visitStatus): string
    {
        return match (strtolower($visitStatus)) {
            'pendaftaran' => 'registered',
            'menunggu' => 'waiting',
            'proses' => 'in_progress',
            'selesai' => 'completed',
            'batal' => 'cancelled',
            default => $visitStatus,
        };
    }

    /**
     * Get the patient that owns this visit.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the polyclinic for this visit.
     */
    public function polyclinic(): BelongsTo
    {
        return $this->belongsTo(Polyclinic::class, 'polyclinic_id');
    }

    /**
     * Get the doctor for this visit.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    /**
     * Get the medical record associated with this visit.
     */
    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class, 'visit_id');
    }

    /**
     * Get the invoice associated with this visit.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'visit_id');
    }

    /**
     * Get the room for this visit.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the bed for this visit.
     */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class, 'bed_id');
    }

    /**
     * Backward-compatible accessor.
     */
    public function getVisitDateAttribute(): ?Carbon
    {
        $value = $this->getRawOriginal('visit_date');

        if ($value !== null) {
            return Carbon::parse($value);
        }

        return $this->registration_date;
    }

    /**
     * Backward-compatible accessor.
     */
    public function getStatusAttribute(): ?string
    {
        $value = $this->getRawOriginal('status');

        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        return $this->visit_status ? $this->mapVisitStatusToLegacy((string) $this->visit_status) : null;
    }

    /**
     * Backward-compatible accessor.
     */
    public function getCheckInAtAttribute($value): ?Carbon
    {
        if ($value) {
            return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        }

        return $this->arrived_at;
    }

    /**
     * Backward-compatible accessor.
     */
    public function getCheckOutAtAttribute($value): ?Carbon
    {
        if ($value) {
            return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
        }

        return $this->completed_at;
    }

    /**
     * Backward-compatible duration accessor (minutes).
     */
    public function getDurationAttribute(): ?int
    {
        $start = $this->check_in_at ?? $this->admission_date ?? $this->registration_date;
        $end = $this->check_out_at ?? $this->completed_at ?? $this->discharge_date;

        if (!$start || !$end) {
            return null;
        }

        $startAt = $start instanceof Carbon ? $start : Carbon::parse((string) $start);
        $endAt = $end instanceof Carbon ? $end : Carbon::parse((string) $end);

        return max(1, (int) $startAt->diffInMinutes($endAt));
    }

    /**
     * Scope a query to only include visits on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('registration_date', $date);
    }

    /**
     * Scope a query to only include visits with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('visit_status', $status);
    }

    /**
     * Scope a query to only include visits for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('registration_date', today());
    }

    /**
     * Scope a query to only include active (not completed) visits.
     */
    public function scopeActive($query)
    {
        return $query->where('visit_status', '!=', 'selesai');
    }

    /**
     * Scope a query to filter by visit type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('visit_type', $type);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==================== CACHING METHODS ====================

    /**
     * Get today's visit count (cached).
     *
     * @return int
     */
    public static function getTodayCount(): int
    {
        return CacheService::getTodayVisitCount();
    }

    /**
     * Get visit counts by type for today (cached).
     *
     * @return array<string, int>
     */
    public static function getTodayCountsByType(): array
    {
        return CacheService::getTodayVisitCountsByType();
    }

    /**
     * Get cached active visits.
     *
     * @param int $limit
     * @return Collection<int, static>
     */
    public static function getActiveCached(int $limit = 50): Collection
    {
        // Cache only the visit IDs to avoid serializing PDO instances
        $visitIds = Cache::remember(
            'visits:active:' . $limit,
            300,
            fn () => self::active()
                ->latest()
                ->limit($limit)
                ->pluck('id')
                ->toArray()
        );

        if (empty($visitIds)) {
            return new Collection();
        }

        // Re-fetch models from database to avoid cached PDO connections
        return self::with(['patient', 'polyclinic', 'doctor'])
            ->whereIn('id', $visitIds)
            ->latest()
            ->get();
    }

    /**
     * Clear visit-related cache on save.
     */
    protected static function booted(): void
    {
        static::creating(function (self $visit): void {
            if (empty($visit->patient_id) || !Patient::query()->whereKey($visit->patient_id)->exists()) {
                $visit->patient_id = Patient::query()->value('id') ?? Patient::factory()->create()->id;
            }

            if (empty($visit->visit_number)) {
                $visit->visit_number = 'VIS' . strtoupper(substr(uniqid('', true), -10));
            }

            if (empty($visit->registration_date)) {
                $visit->registration_date = now();
            }

            if (empty($visit->visit_type)) {
                $visit->visit_type = 'rawat_jalan';
            }

            if (empty($visit->visit_status)) {
                $visit->visit_status = 'pendaftaran';
            }
            if (empty($visit->status)) {
                $visit->status = $visit->mapVisitStatusToLegacy((string) $visit->visit_status);
            }

            if (empty($visit->payment_type)) {
                $visit->payment_type = 'umum';
            }

            if (empty($visit->priority)) {
                $visit->priority = 'normal';
            }

            if (empty($visit->registered_by)) {
                $visit->registered_by = auth()->id() ?? User::query()->value('id') ?? User::factory()->create()->id;
            }
            if (empty($visit->visit_date) && !empty($visit->registration_date)) {
                $visit->visit_date = $visit->registration_date;
            }
            if (empty($visit->check_in_at) && !empty($visit->arrived_at)) {
                $visit->check_in_at = $visit->arrived_at;
            }
            if (empty($visit->check_out_at) && !empty($visit->completed_at)) {
                $visit->check_out_at = $visit->completed_at;
            }
        });

        static::saved(function (self $visit): void {
            // Clear today's visit count caches
            $today = now()->toDateString();
            Cache::forget("visits:count:{$today}");
            Cache::forget("visits:count:by_type:{$today}");
            Cache::forget('visits:active:*');

            // Clear polyclinic queue stats
            if ($visit->polyclinic_id) {
                CacheService::forgetQueueStats($visit->polyclinic_id);
            }
        });

        static::deleted(function (self $visit): void {
            $today = now()->toDateString();
            Cache::forget("visits:count:{$today}");
            Cache::forget("visits:count:by_type:{$today}");
            Cache::forget('visits:active:*');

            if ($visit->polyclinic_id) {
                CacheService::forgetQueueStats($visit->polyclinic_id);
            }
        });
    }
}
