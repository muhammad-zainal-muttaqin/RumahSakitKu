<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Medicine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prescription Item Model
 *
 * Represents an individual item/medicine in a prescription.
 * Contains dosage instructions and dispensing information.
 *
 * @property int $id
 * @property int $prescription_id
 * @property int|null $medicine_id
 * @property string $generic_name
 * @property string|null $brand_name
 * @property string|null $dosage_form
 * @property string|null $strength
 * @property float $quantity
 * @property string $unit
 * @property string|null $dosage_instructions
 * @property string|null $frequency
 * @property int|null $duration_days
 * @property string|null $route_of_administration
 * @property string|null $instructions
 * @property bool $is_substitutable
 * @property string|null $substitution_notes
 * @property float|null $unit_price
 * @property float|null $total_price
 * @property bool $is_dispensed
 * @property float|null $dispensed_quantity
 * @property Carbon|null $dispensed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $formatted_dosage
 * @property-read string $full_medicine_name
 * @property-read bool $is_partially_dispensed
 * @property-read Prescription $prescription
 * @property-read Medicine|null $medicine
 *
 * @method static Builder|PrescriptionItem dispensed()
 * @method static Builder|PrescriptionItem pending()
 * @method static Builder|PrescriptionItem substitutable()
 * @method static Builder|PrescriptionItem byMedicine(int $medicineId)
 */
class PrescriptionItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'prescription_items';

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'generic_name',
        'brand_name',
        'dosage_form',
        'strength',
        'quantity',
        'unit',
        'dosage_instructions',
        'frequency',
        'duration_days',
        'route_of_administration',
        'instructions',
        'is_substitutable',
        'substitution_notes',
        'unit_price',
        'total_price',
        'is_dispensed',
        'dispensed_quantity',
        'dispensed_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'duration_days' => 'integer',
        'dispensed_quantity' => 'decimal:2',
        'dispensed_at' => 'datetime',
        'is_substitutable' => 'boolean',
        'is_dispensed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the prescription that owns this item.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    /**
     * Get the medicine associated with this item.
     */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    /**
     * Scope a query to only include dispensed items.
     */
    public function scopeDispensed($query)
    {
        return $query->where('is_dispensed', true);
    }

    /**
     * Scope a query to only include pending items.
     */
    public function scopePending($query)
    {
        return $query->where('is_dispensed', false);
    }

    /**
     * Scope a query to only include substitutable items.
     */
    public function scopeSubstitutable($query)
    {
        return $query->where('is_substitutable', true);
    }

    /**
     * Scope a query to filter by medicine.
     */
    public function scopeByMedicine($query, int $medicineId)
    {
        return $query->where('medicine_id', $medicineId);
    }

    /**
     * Calculate total price before saving.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->unit_price && $item->quantity) {
                $item->total_price = $item->unit_price * $item->quantity;
            }
        });
    }

    /**
     * Get formatted dosage instructions.
     */
    public function getFormattedDosageAttribute(): string
    {
        $parts = [];

        if ($this->dosage_instructions) {
            $parts[] = $this->dosage_instructions;
        }

        if ($this->frequency) {
            $parts[] = $this->frequency;
        }

        if ($this->route_of_administration) {
            $parts[] = "via {$this->route_of_administration}";
        }

        if ($this->duration_days) {
            $parts[] = "for {$this->duration_days} days";
        }

        return implode(', ', $parts);
    }

    /**
     * Get the full medicine name.
     */
    public function getFullMedicineNameAttribute(): string
    {
        $name = $this->generic_name;

        if ($this->brand_name) {
            $name .= " ({$this->brand_name})";
        }

        if ($this->strength) {
            $name .= " {$this->strength}";
        }

        return $name;
    }

    /**
     * Check if item is partially dispensed.
     */
    public function getIsPartiallyDispensedAttribute(): bool
    {
        return $this->is_dispensed
            && $this->dispensed_quantity !== null
            && $this->dispensed_quantity < $this->quantity;
    }
}
