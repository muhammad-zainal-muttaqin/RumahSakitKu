<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Polyclinic Model
 *
 * Represents a polyclinic/clinic unit in the hospital.
 * Manages clinic operations and doctor assignments.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $category
 * @property string|null $queue_prefix
 * @property string|null $bpjs_poli_code
 * @property string|null $bpjs_poli_name
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $max_queue_per_day
 * @property Carbon|null $open_time
 * @property Carbon|null $close_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read int $today_visit_count
 * @property-read bool $has_reached_quota
 * @property-read string $formatted_operating_hours
 * @property-read string $category_label
 * @property-read string $category_color
 * @property-read Collection|Visit[] $visits
 * @property-read Collection|Employee[] $employees
 * @property-read Collection|Employee[] $doctors
 *
 * @method static Builder|Polyclinic active()
 * @method static Builder|Polyclinic search(string $search)
 */
class Polyclinic extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'polyclinics';

    protected $fillable = [
        'code',
        'name',
        'category',
        'queue_prefix',
        'bpjs_poli_code',
        'bpjs_poli_name',
        'description',
        'is_active',
        'max_queue_per_day',
        'open_time',
        'close_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_queue_per_day' => 'integer',
        'open_time' => 'datetime:H:i',
        'close_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all visits for this polyclinic.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'polyclinic_id');
    }

    /**
     * Get all employees (doctors) assigned to this polyclinic.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'polyclinic_id');
    }

    /**
     * Get all doctors assigned to this polyclinic.
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Employee::class, 'polyclinic_id')
            ->where('is_doctor', true)
            ->where('status', 'aktif');
    }

    /**
     * Scope a query to only include active polyclinics.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        });
    }

    /**
     * Get today's visit count.
     */
    public function getTodayVisitCountAttribute(): int
    {
        return $this->visits()
            ->where(function ($query) {
                $query->whereDate('registration_date', today())
                    ->orWhereDate('visit_date', today());
            })
            ->count();
    }

    /**
     * Check if polyclinic has reached daily quota.
     */
    public function getHasReachedQuotaAttribute(): bool
    {
        if (!$this->max_queue_per_day || $this->max_queue_per_day <= 0) {
            return false;
        }

        return $this->today_visit_count >= $this->max_queue_per_day;
    }

    /**
     * Get formatted operating hours.
     */
    public function getFormattedOperatingHoursAttribute(): string
    {
        if (
            !$this->open_time ||
            !$this->close_time ||
            ($this->open_time->format('H:i') === '00:00' && $this->close_time->format('H:i') === '00:00')
        ) {
            return '-';
        }

        return $this->open_time->format('H:i') . ' - ' . $this->close_time->format('H:i');
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'umum' => 'Umum',
            'spesialis' => 'Spesialis',
            'gigi' => 'Gigi',
            'anak' => 'Anak',
            'bedah' => 'Bedah',
            'penyakit_dalam' => 'Penyakit Dalam',
            'syaraf' => 'Syaraf',
            'jiwa' => 'Jiwa',
            'rehabilitasi' => 'Rehabilitasi',
            'radiologi' => 'Radiologi',
            'laboratorium' => 'Laboratorium',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get category color for badges.
     */
    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'umum' => 'gray',
            'spesialis' => 'primary',
            'gigi' => 'success',
            'anak' => 'warning',
            'bedah' => 'danger',
            'penyakit_dalam' => 'info',
            'syaraf' => 'purple',
            'jiwa' => 'pink',
            'rehabilitasi' => 'teal',
            'radiologi' => 'indigo',
            'laboratorium' => 'cyan',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (self $polyclinic): void {
            if ($polyclinic->max_queue_per_day === null) {
                $polyclinic->max_queue_per_day = 0;
            }

            if ($polyclinic->open_time === null) {
                $polyclinic->open_time = '00:00:00';
            }

            if ($polyclinic->close_time === null) {
                $polyclinic->close_time = '00:00:00';
            }
        });
    }
}
