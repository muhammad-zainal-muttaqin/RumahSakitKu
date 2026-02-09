<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Procedure Model
 *
 * Master data for medical procedures. Defines available medical procedures
 * with their categories, pricing, and BPJS coverage information.
 * Used for billing and service management in the hospital information system.
 *
 * @property int $id
 * @property string $procedure_code Unique code for the procedure
 * @property string $name Name of the procedure
 * @property int $category_id Foreign key to procedure category
 * @property float $base_price Base price for the procedure
 * @property float|null $bpjs_tariff BPJS tariff/covered amount
 * @property float|null $material_cost Additional material cost
 * @property bool $is_bpjs_covered Whether the procedure is covered by BPJS
 * @property bool $is_active Whether the procedure is active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ProcedureCategory $category The category this procedure belongs to
 * @property-read string $formatted_base_price Formatted base price with Rp prefix
 * @property-read string $formatted_bpjs_tariff Formatted BPJS tariff with Rp prefix
 * @property-read float $total_price Total price including material cost
 * @property-read string $formatted_total_price Formatted total price with Rp prefix
 *
 * @method static Builder|Procedure active()
 * @method static Builder|Procedure bpjsCovered()
 * @method static Builder|Procedure byCategory(int $categoryId)
 * @method static Builder|Procedure search(string $search)
 */
class Procedure extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'procedures';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'procedure_code',
        'name',
        'category_id',
        'base_price',
        'bpjs_tariff',
        'material_cost',
        'is_bpjs_covered',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'bpjs_tariff' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'is_bpjs_covered' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Keep legacy/new category foreign keys synchronized for compatibility.
     */
    protected static function booted(): void
    {
        static::saving(function (self $procedure): void {
            $categoryId = $procedure->category_id ?? $procedure->getAttribute('procedure_category_id');

            if ($categoryId !== null) {
                $procedure->category_id = (int) $categoryId;
                $procedure->setAttribute('procedure_category_id', (int) $categoryId);
            }
        });
    }

    /**
     * Get the procedure category that owns this procedure.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProcedureCategory::class, 'category_id');
    }

    /**
     * Scope a query to only include active procedures.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include BPJS covered procedures.
     */
    public function scopeBpjsCovered($query)
    {
        return $query->where('is_bpjs_covered', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to search by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('procedure_code', 'like', "%{$search}%");
        });
    }

    /**
     * Get formatted base price.
     */
    public function getFormattedBasePriceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->base_price, 0, ',', '.');
    }

    /**
     * Get formatted BPJS tariff.
     */
    public function getFormattedBpjsTariffAttribute(): string
    {
        return $this->bpjs_tariff ? 'Rp ' . number_format((float) $this->bpjs_tariff, 0, ',', '.') : '-';
    }

    /**
     * Get total price including material cost.
     */
    public function getTotalPriceAttribute(): float
    {
        return ($this->base_price ?? 0) + ($this->material_cost ?? 0);
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }
}
