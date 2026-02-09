<?php

declare(strict_types=1);

namespace App\Models\MasterData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Procedure Category Model
 *
 * Master data for medical procedure categories.
 * Groups related procedures together for easier management and organization.
 * Supports custom colors and icons for UI display.
 *
 * @property int $id
 * @property string $code Unique code for the category
 * @property string $name Name of the category
 * @property string|null $description Description of the category
 * @property string|null $color Color code for UI display
 * @property string|null $icon Icon class for UI display
 * @property bool $is_active Whether the category is active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection|Procedure[] $procedures All procedures in this category
 * @property-read Collection|Procedure[] $activeProcedures Active procedures only
 * @property-read int $procedure_count Total count of procedures in this category
 * @property-read int $active_procedure_count Count of active procedures in this category
 *
 * @method static Builder|ProcedureCategory active()
 * @method static Builder|ProcedureCategory ordered()
 * @method static Builder|ProcedureCategory search(string $search)
 */
class ProcedureCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'procedure_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'icon',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all procedures in this category.
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'procedure_category_id');
    }

    /**
     * Get active procedures in this category.
     */
    public function activeProcedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'procedure_category_id')
            ->where('is_active', true);
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
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
     * Get procedure count in this category.
     */
    public function getProcedureCountAttribute(): int
    {
        return $this->procedures()->count();
    }

    /**
     * Get active procedure count in this category.
     */
    public function getActiveProcedureCountAttribute(): int
    {
        return $this->procedures()
            ->where('is_active', true)
            ->count();
    }
}
