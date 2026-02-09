<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Clinical\PrescriptionItem;
use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Medicine Model
 *
 * Represents a medicine/drug in the hospital pharmacy.
 * Manages stock levels, pricing, and expiration tracking.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $generic_name
 * @property string|null $classification
 * @property string|null $dosage_form
 * @property string|null $unit
 * @property string|null $manufacturer
 * @property string|null $registration_number
 * @property bool $is_generic
 * @property float $stock
 * @property float $min_stock
 * @property float|null $selling_price
 * @property float|null $purchase_price
 * @property Carbon|null $expired_date
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read bool $is_low_stock
 * @property-read bool $is_out_of_stock
 * @property-read bool $is_expired
 * @property-read bool $is_expiring_soon
 * @property-read string $stock_status
 * @property-read string $expiration_status
 * @property-read string $classification_label
 * @property-read string $dosage_form_label
 * @property-read Collection|PrescriptionItem[] $prescriptionItems
 *
 * @method static Builder|Medicine active()
 * @method static Builder|Medicine search(string $search)
 * @method static Builder|Medicine byClassification(string $classification)
 * @method static Builder|Medicine byDosageForm(string $dosageForm)
 * @method static Builder|Medicine lowStock()
 * @method static Builder|Medicine outOfStock()
 * @method static Builder|Medicine expired()
 * @method static Builder|Medicine expiringSoon()
 */
class Medicine extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'medicines';

    protected $fillable = [
        'code',
        'name',
        'generic_name',
        'classification',
        'dosage_form',
        'unit',
        'manufacturer',
        'registration_number',
        'is_generic',
        'stock',
        'min_stock',
        'selling_price',
        'purchase_price',
        'expired_date',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expired_date' => 'date',
        'is_generic' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all prescription items for this medicine.
     */
    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'medicine_id');
    }

    /**
     * Scope a query to only include active medicines.
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
     * Scope a query to filter by classification.
     */
    public function scopeByClassification($query, string $classification)
    {
        return $query->where('classification', $classification);
    }

    /**
     * Scope a query to filter by dosage form.
     */
    public function scopeByDosageForm($query, string $dosageForm)
    {
        return $query->where('dosage_form', $dosageForm);
    }

    /**
     * Scope a query to only include low stock medicines.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    /**
     * Scope a query to only include out of stock medicines.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Scope a query to only include expired medicines.
     */
    public function scopeExpired($query)
    {
        return $query->where('expired_date', '<', now());
    }

    /**
     * Scope a query to only include medicines expiring soon (within 30 days).
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expired_date', '>=', now())
            ->where('expired_date', '<=', now()->addDays(30));
    }

    /**
     * Check if medicine stock is low.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /**
     * Check if medicine is out of stock.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Check if medicine is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_date && $this->expired_date->isPast();
    }

    /**
     * Check if medicine is expiring soon (within 30 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expired_date) {
            return false;
        }

        return $this->expired_date->isFuture() && $this->expired_date->diffInDays(now()) >= -30;
    }

    /**
     * Get stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        }

        if ($this->is_low_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get expiration status.
     */
    public function getExpirationStatusAttribute(): string
    {
        if ($this->is_expired) {
            return 'expired';
        }

        if ($this->is_expiring_soon) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    /**
     * Update stock quantity.
     */
    public function updateStock(float $quantity, string $type = 'in'): bool
    {
        if ($type === 'in') {
            $this->stock += $quantity;
        } else {
            $this->stock -= $quantity;
        }

        return $this->save();
    }

    /**
     * Get classification label.
     */
    public function getClassificationLabelAttribute(): string
    {
        if (!$this->classification) {
            return '-';
        }

        return match ($this->classification) {
            'obat_bebas' => 'Obat Bebas',
            'obat_bebas_terbatas' => 'Obat Bebas Terbatas',
            'obat_keras' => 'Obat Keras',
            'narkotika' => 'Narkotika',
            'psikotropik' => 'Psikotropik',
            default => ucwords(str_replace('_', ' ', $this->classification)),
        };
    }

    /**
     * Get dosage form label.
     */
    public function getDosageFormLabelAttribute(): string
    {
        if (!$this->dosage_form) {
            return '-';
        }

        return match ($this->dosage_form) {
            'tablet' => 'Tablet',
            'kapsul' => 'Kapsul',
            'sirup' => 'Sirup',
            'injeksi' => 'Injeksi',
            'salep' => 'Salep',
            'krim' => 'Krim',
            'gel' => 'Gel',
            'tetes' => 'Tetes',
            'inhaler' => 'Inhaler',
            'supositoria' => 'Supositoria',
            'suspensi' => 'Suspensi',
            'eliksir' => 'Eliksir',
            'serbuk' => 'Serbuk',
            'patch' => 'Patch',
            default => ucfirst($this->dosage_form),
        };
    }
}
