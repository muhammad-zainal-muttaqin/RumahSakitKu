<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Room Model
 *
 * Represents a hospital room or ward.
 * Manages room capacity, pricing, and bed availability.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $room_class
 * @property int|null $floor
 * @property string|null $building
 * @property string|null $gender_preference
 * @property int $total_beds
 * @property int $available_beds
 * @property float|null $base_price
 * @property float|null $bpjs_price
 * @property array|null $facilities
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read int $total_beds_count
 * @property-read int $available_beds_count
 * @property-read int $occupied_beds_count
 * @property-read float $occupancy_rate
 * @property-read bool $is_full
 * @property-read float $total_daily_rate
 * @property-read string $room_class_color
 * @property-read Collection|Bed[] $beds
 * @property-read Collection|Bed[] $availableBeds
 * @property-read Collection|Bed[] $occupiedBeds
 *
 * @method static Builder|Room active()
 * @method static Builder|Room byClass(string $class)
 * @method static Builder|Room onFloor(int $floor)
 * @method static Builder|Room withAvailableBeds()
 */
class Room extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'rooms';

    protected $fillable = [
        'code',
        'name',
        'room_class',
        'floor',
        'building',
        'gender_preference',
        'total_beds',
        'available_beds',
        'base_price',
        'bpjs_price',
        'facilities',
        'description',
        'is_active',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'available_beds' => 'integer',
        'base_price' => 'decimal:2',
        'bpjs_price' => 'decimal:2',
        'facilities' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all beds in this room.
     */
    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class, 'room_id');
    }

    /**
     * Get available beds in this room.
     */
    public function availableBeds(): HasMany
    {
        return $this->hasMany(Bed::class, 'room_id')
            ->select('beds.*')
            ->selectRaw("CASE WHEN status = 'kosong' THEN 'available' ELSE status END as status")
            ->whereIn('status', ['available', 'kosong'])
            ->whereNull('current_visit_id');
    }

    /**
     * Get occupied beds in this room.
     */
    public function occupiedBeds(): HasMany
    {
        return $this->hasMany(Bed::class, 'room_id')
            ->select('beds.*')
            ->selectRaw("CASE WHEN status = 'terisi' THEN 'occupied' ELSE status END as status")
            ->whereIn('status', ['occupied', 'terisi'])
            ->whereNotNull('current_visit_id');
    }

    /**
     * Scope a query to only include active rooms.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by room class.
     */
    public function scopeByClass($query, string $class)
    {
        return $query->where('room_class', $class);
    }

    /**
     * Scope a query to filter by floor.
     */
    public function scopeOnFloor($query, int $floor)
    {
        return $query->where('floor', $floor);
    }

    /**
     * Scope a query to only include rooms with available beds.
     */
    public function scopeWithAvailableBeds($query)
    {
        return $query->whereHas('beds', function ($q) {
            $q->whereIn('status', ['available', 'kosong'])
                ->whereNull('current_visit_id');
        });
    }

    /**
     * Get total bed count.
     */
    public function getTotalBedsCountAttribute(): int
    {
        if (!$this->hasModelKey()) {
            return (int) ($this->getRawOriginal('total_beds') ?? 0);
        }

        if ($this->relationLoaded('beds')) {
            return $this->beds->count();
        }

        return $this->beds()->count();
    }

    /**
     * Get total beds from relation when available, otherwise use stored column.
     */
    public function getTotalBedsAttribute($value): int
    {
        if (!$this->hasModelKey()) {
            return (int) ($value ?? 0);
        }

        if ($this->relationLoaded('beds')) {
            return $this->beds->count();
        }

        $bedCount = $this->beds()->count();
        if ($bedCount > 0 || $value === null) {
            return (int) $bedCount;
        }

        return (int) ($value ?? 0);
    }

    /**
     * Get available bed count.
     */
    public function getAvailableBedsCountAttribute(): int
    {
        $rawAvailableBeds = (int) ($this->getRawOriginal('available_beds') ?? 0);

        if (!$this->hasModelKey()) {
            return $rawAvailableBeds;
        }

        if ($this->relationLoaded('beds')) {
            if ($this->beds->isEmpty()) {
                return $rawAvailableBeds;
            }

            return $this->beds
                ->whereIn('status', ['available', 'kosong'])
                ->whereNull('current_visit_id')
                ->count();
        }

        $bedsQuery = $this->beds();
        $bedCount = (clone $bedsQuery)->count();

        if ($bedCount === 0) {
            return $rawAvailableBeds;
        }

        return $bedsQuery
            ->whereIn('status', ['available', 'kosong'])
            ->whereNull('current_visit_id')
            ->count();
    }

    /**
     * Get occupied bed count.
     */
    public function getOccupiedBedsCountAttribute(): int
    {
        $rawTotalBeds = (int) ($this->getRawOriginal('total_beds') ?? 0);
        $rawAvailableBeds = (int) ($this->getRawOriginal('available_beds') ?? 0);

        if (!$this->hasModelKey()) {
            return max(0, $rawTotalBeds - $rawAvailableBeds);
        }

        if ($this->relationLoaded('beds')) {
            if ($this->beds->isEmpty()) {
                return max(0, $rawTotalBeds - $rawAvailableBeds);
            }

            return $this->beds
                ->whereIn('status', ['occupied', 'terisi'])
                ->whereNotNull('current_visit_id')
                ->count();
        }

        $bedsQuery = $this->beds();
        $bedCount = (clone $bedsQuery)->count();

        if ($bedCount === 0) {
            return max(0, $rawTotalBeds - $rawAvailableBeds);
        }

        return $bedsQuery
            ->whereIn('status', ['occupied', 'terisi'])
            ->whereNotNull('current_visit_id')
            ->count();
    }

    /**
     * Get occupancy rate percentage.
     */
    public function getOccupancyRateAttribute(): float
    {
        if ($this->hasModelKey()) {
            if ($this->relationLoaded('beds')) {
                if ($this->beds->isEmpty()) {
                    return 0.0;
                }
            } elseif ($this->beds()->count() === 0) {
                return 0.0;
            }
        }

        $totalBeds = $this->total_beds;
        if ($totalBeds <= 0) {
            return 0.0;
        }

        $occupied = $this->occupied_beds_count;

        return round(($occupied / $totalBeds) * 100, 2);
    }

    /**
     * Check if room is full.
     */
    public function getIsFullAttribute(): bool
    {
        return $this->total_beds > 0 && $this->available_beds_count <= 0;
    }

    /**
     * Get total daily rate.
     */
    public function getTotalDailyRateAttribute(): float
    {
        return (float) ($this->base_price ?? 0);
    }

    /**
     * Get room class color.
     */
    public function getRoomClassColorAttribute(): string
    {
        return match ($this->room_class) {
            'VVIP' => 'danger',
            'VIP' => 'warning',
            'Kelas I' => 'primary',
            'Kelas II' => 'info',
            'Kelas III' => 'success',
            'ICU', 'NICU', 'PICU', 'HCU' => 'purple',
            default => 'gray',
        };
    }

    // ==================== CACHING METHODS ====================

    /**
     * Get cached room occupancy data.
     *
     * @return Collection<int, static>
     */
    public static function getCachedOccupancy(): Collection
    {
        // Cache only the room IDs to avoid serializing PDO instances
        $roomIds = Cache::remember(
            'rooms:occupancy:detailed',
            300,
            fn () => self::active()
                ->pluck('id')
                ->toArray()
        );

        if (empty($roomIds)) {
            return new Collection();
        }

        // Re-fetch models from database to avoid cached PDO connections
        return self::with(['beds' => fn ($q) => $q->with('currentVisit.patient')])
            ->whereIn('id', $roomIds)
            ->get();
    }

    /**
     * Get cached available rooms.
     *
     * @return Collection<int, static>
     */
    public static function getCachedAvailable(): Collection
    {
        // Cache only the room IDs to avoid serializing PDO instances
        $roomIds = Cache::remember(
            'rooms:available',
            300,
            fn () => self::whereHas('beds', fn ($q) => $q->where('status', 'kosong')->whereNull('current_visit_id'))
                ->pluck('id')
                ->toArray()
        );

        if (empty($roomIds)) {
            return new Collection();
        }

        // Re-fetch models from database to avoid cached PDO connections
        return self::with('availableBeds')
            ->whereIn('id', $roomIds)
            ->get();
    }

    /**
     * Get room summary statistics (cached).
     *
     * @return array<string, mixed>
     */
    public static function getCachedSummary(): array
    {
        return CacheService::getRoomOccupancy();
    }

    /**
     * Clear room cache on save.
     */
    protected static function booted(): void
    {
        static::saved(function (self $room): void {
            CacheService::forgetRoomOccupancy();
            Cache::forget('rooms:occupancy:detailed');
            Cache::forget('rooms:available');
        });

        static::deleted(function (self $room): void {
            CacheService::forgetRoomOccupancy();
            Cache::forget('rooms:occupancy:detailed');
            Cache::forget('rooms:available');
        });

        static::creating(function (self $room): void {
            if ($room->base_price === null) {
                $room->base_price = 0;
            }
            if ($room->bpjs_price === null) {
                $room->bpjs_price = 0;
            }
        });
    }

    private function hasModelKey(): bool
    {
        $keyName = $this->getKeyName();

        return $this->getRawOriginal($keyName) !== null
            || $this->getAttributeFromArray($keyName) !== null;
    }
}
