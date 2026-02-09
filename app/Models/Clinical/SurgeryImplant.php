<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Surgery Implant Model
 *
 * Represents an implant used during a surgical procedure.
 * Tracks implant details, serial numbers, and expiration.
 *
 * @property int $id
 * @property int $surgery_id
 * @property string $implant_name
 * @property string $implant_type
 * @property string|null $serial_number
 * @property string|null $batch_number
 * @property string|null $manufacturer
 * @property int $quantity
 * @property string $unit
 * @property Carbon|null $expiry_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $implant_type_label
 * @property-read string $unit_label
 * @property-read bool $is_expired
 * @property-read bool $is_expiring_soon
 * @property-read Surgery $surgery
 *
 * @method static Builder|SurgeryImplant byType(string $type)
 * @method static Builder|SurgeryImplant search(string $search)
 */
class SurgeryImplant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'surgery_implants';

    protected $fillable = [
        'surgery_id',
        'implant_name',
        'implant_type',
        'serial_number',
        'batch_number',
        'manufacturer',
        'quantity',
        'unit',
        'expiry_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the surgery that owns this implant.
     */
    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class, 'surgery_id');
    }

    /**
     * Scope a query to filter by implant type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('implant_type', $type);
    }

    /**
     * Scope a query to search by implant name or serial number.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('implant_name', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('manufacturer', 'like', "%{$search}%");
        });
    }

    /**
     * Get available implant types.
     * @return array<string, string>
     */
    public static function getImplantTypes(): array
    {
        return [
            'prosthetic' => 'Prostetik',
            'orthopedic' => 'Ortopedi',
            'cardiac' => 'Jantung',
            'vascular' => 'Vaskular',
            'neurosurgery' => 'Neurosurgery',
            'ophthalmic' => 'Oftalmik',
            'dental' => 'Dental',
            'surgical_mesh' => 'Surgical Mesh',
            'bone_cement' => 'Bone Cement',
            'plate_screw' => 'Plate & Screw',
            'stent' => 'Stent',
            'pacemaker' => 'Pacemaker',
            'defibrillator' => 'Defibrillator',
            'shunt' => 'Shunt',
            'catheter' => 'Kateter',
            'drain' => 'Drain',
            'clip' => 'Clip',
            'suture' => 'Suture',
            'other' => 'Lainnya',
        ];
    }

    /**
     * Get unit options.
     * @return array<string, string>
     */
    public static function getUnits(): array
    {
        return [
            'pcs' => 'Pcs',
            'set' => 'Set',
            'pair' => 'Pair',
            'unit' => 'Unit',
            'ml' => 'ml',
            'cc' => 'cc',
            'gram' => 'gram',
            'mg' => 'mg',
        ];
    }

    /**
     * Get implant type label.
     */
    public function getImplantTypeLabelAttribute(): string
    {
        return self::getImplantTypes()[$this->implant_type] ?? ucfirst($this->implant_type);
    }

    /**
     * Get unit label.
     */
    public function getUnitLabelAttribute(): string
    {
        return self::getUnits()[$this->unit] ?? $this->unit;
    }

    /**
     * Check if implant is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if implant is expiring soon (within 30 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date || $this->is_expired) {
            return false;
        }

        $daysUntilExpiry = now()->diffInDays($this->expiry_date, false);

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
    }
}
