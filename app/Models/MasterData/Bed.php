<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bed Model
 *
 * Represents an individual hospital bed.
 * Tracks bed status, occupancy, and patient assignment.
 *
 * @property int $id
 * @property int $room_id
 * @property string $bed_number
 * @property string|null $bed_name
 * @property string|null $bed_type
 * @property string $status
 * @property int|null $current_visit_id
 * @property Carbon|null $occupied_at
 * @property Carbon|null $vacated_at
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read bool $is_available
 * @property-read bool $is_occupied
 * @property-read int|null $occupancy_duration
 * @property-read string $full_identifier
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read Room $room
 * @property-read Visit|null $currentVisit
 *
 * @method static Builder|Bed available()
 * @method static Builder|Bed occupied()
 * @method static Builder|Bed maintenance()
 * @method static Builder|Bed reserved()
 * @method static Builder|Bed cleaning()
 * @method static Builder|Bed byType(string $type)
 * @method static Builder|Bed inRoom(int $roomId)
 * @method static Builder|Bed active()
 */
class Bed extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'beds';

    protected $fillable = [
        'room_id',
        'bed_number',
        'bed_name',
        'bed_type',
        'status',
        'current_visit_id',
        'occupied_at',
        'vacated_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'occupied_at' => 'datetime',
        'vacated_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the room that owns this bed.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the visit currently occupying this bed (nullable).
     */
    public function currentVisit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'current_visit_id');
    }

    /**
     * Scope a query to only include available beds (kosong).
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'kosong')
            ->whereNull('current_visit_id');
    }

    /**
     * Scope a query to only include occupied beds (terisi).
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'terisi')
            ->whereNotNull('current_visit_id');
    }

    /**
     * Scope a query to only include maintenance beds.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Scope a query to only include reserved beds.
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope a query to only include cleaning beds.
     */
    public function scopeCleaning($query)
    {
        return $query->where('status', 'cleaning');
    }

    /**
     * Scope a query to filter by bed type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('bed_type', $type);
    }

    /**
     * Scope a query to filter by room.
     */
    public function scopeInRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope a query to only include active beds.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Occupy this bed with a visit.
     */
    public function occupy(int $visitId): bool
    {
        if ($this->status !== 'kosong') {
            return false;
        }

        $this->current_visit_id = $visitId;
        $this->status = 'terisi';
        $this->occupied_at = now();

        $saved = $this->save();
        if ($saved) {
            $this->syncRoomAvailability();
        }

        return $saved;
    }

    /**
     * Vacate this bed.
     */
    public function vacate(): bool
    {
        $this->current_visit_id = null;
        $this->status = 'kosong';
        $this->vacated_at = now();
        $this->occupied_at = null;

        $saved = $this->save();
        if ($saved) {
            $this->syncRoomAvailability();
        }

        return $saved;
    }

    private function syncRoomAvailability(): void
    {
        if (!$this->room_id) {
            return;
        }

        $occupiedBeds = self::query()
            ->where('room_id', $this->room_id)
            ->where('status', 'terisi')
            ->whereNotNull('current_visit_id')
            ->count();

        $room = Room::query()->find($this->room_id);
        if (!$room) {
            return;
        }

        $totalBeds = (int) ($room->getRawOriginal('total_beds') ?? 0);
        $availableBeds = $totalBeds > 0
            ? max(0, $totalBeds - $occupiedBeds)
            : self::query()
                ->where('room_id', $this->room_id)
                ->where('status', 'kosong')
                ->whereNull('current_visit_id')
                ->count();

        Room::query()
            ->whereKey($this->room_id)
            ->update(['available_beds' => $availableBeds]);
    }

    /**
     * Set bed to maintenance status.
     */
    public function setMaintenance(string $notes = null): bool
    {
        if ($this->status === 'terisi') {
            return false;
        }

        $this->status = 'maintenance';
        $this->notes = $notes;

        return $this->save();
    }

    /**
     * Set bed to cleaning status.
     */
    public function setCleaning(): bool
    {
        if ($this->status === 'terisi') {
            return false;
        }

        $this->status = 'cleaning';

        return $this->save();
    }

    /**
     * Set bed to reserved status.
     */
    public function setReserved(): bool
    {
        if ($this->status === 'terisi') {
            return false;
        }

        $this->status = 'reserved';

        return $this->save();
    }

    /**
     * Check if bed is available.
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'kosong' && $this->current_visit_id === null;
    }

    /**
     * Check if bed is occupied.
     */
    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === 'terisi' && $this->current_visit_id !== null;
    }

    /**
     * Get occupancy duration in hours.
     */
    public function getOccupancyDurationAttribute(): ?int
    {
        if (!$this->occupied_at) {
            return null;
        }

        return (int) $this->occupied_at->diffInHours(now());
    }

    /**
     * Get full bed identifier.
     */
    public function getFullIdentifierAttribute(): string
    {
        $roomName = $this->room?->name ?? 'Unknown Room';
        return "{$roomName} - Bed {$this->bed_number}";
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'kosong' => 'success',
            'terisi' => 'danger',
            'reserved' => 'warning',
            'maintenance' => 'gray',
            'cleaning' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'kosong' => 'Kosong',
            'terisi' => 'Terisi',
            'reserved' => 'Dipesan',
            'maintenance' => 'Maintenance',
            'cleaning' => 'Dibersihkan',
            default => ucfirst($this->status),
        };
    }
}
