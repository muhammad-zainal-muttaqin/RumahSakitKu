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
 * Lab Test Model
 *
 * Master data for laboratory tests. Defines available lab tests
 * with their categories, specimen types, reference values, and pricing.
 * Supports various test categories including hematology, chemistry, microbiology, etc.
 *
 * @property int $id
 * @property string $test_code Unique code for the lab test
 * @property string $name Name of the lab test
 * @property string $category Category of the test (hematologi, kimia_darah, urinalisa, etc.)
 * @property string|null $specimen_type Type of specimen required (darah, urine, feses, etc.)
 * @property string|null $reference_value Reference/normal value range
 * @property string|null $unit Unit of measurement for results
 * @property float $base_price Base price for the test
 * @property bool $is_active Whether the test is active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection|LaboratoryResult[] $results
 * @property-read string $formatted_base_price Formatted price with Rp prefix
 * @property-read string $category_label Human-readable category name
 * @property-read string $specimen_type_label Human-readable specimen type name
 * @property-read string $category_color Color code for UI badges
 *
 * @method static Builder|LabTest active()
 * @method static Builder|LabTest byCategory(string $category)
 * @method static Builder|LabTest bySpecimenType(string $specimenType)
 * @method static Builder|LabTest search(string $search)
 */
class LabTest extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lab_tests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'test_code',
        'name',
        'category',
        'specimen_type',
        'reference_value',
        'unit',
        'base_price',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $labTest): void {
            if (empty($labTest->specimen_type)) {
                $labTest->specimen_type = 'darah';
            }
        });
    }

    /**
     * Normalize legacy aliases used by feature tests.
     *
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        if (array_key_exists('test_name', $attributes)) {
            $attributes['name'] = $attributes['test_name'];
        }
        if (array_key_exists('reference_range', $attributes)) {
            $attributes['reference_value'] = $attributes['reference_range'];
        }

        unset($attributes['test_name'], $attributes['reference_range']);

        return parent::fill($attributes);
    }

    /**
     * Scope a query to only include active lab tests.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by specimen type.
     */
    public function scopeBySpecimenType($query, string $specimenType)
    {
        return $query->where('specimen_type', $specimenType);
    }

    /**
     * Scope a query to search by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('test_code', 'like', "%{$search}%");
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
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        if (!$this->category) {
            return '-';
        }

        return match ($this->category) {
            'hematologi' => 'Hematologi',
            'kimia_darah' => 'Kimia Darah',
            'urinalisa' => 'Urinalisa',
            'mikrobiologi' => 'Mikrobiologi',
            'imunologi' => 'Imunologi',
            'serologi' => 'Serologi',
            'endokrinologi' => 'Endokrinologi',
            'tumor_marker' => 'Tumor Marker',
            'elektrolit' => 'Elektrolit',
            'gula_darah' => 'Gula Darah',
            'fungsi_ginjal' => 'Fungsi Ginjal',
            'fungsi_hati' => 'Fungsi Hati',
            'lemak_darah' => 'Lemak Darah',
            'koagulasi' => 'Koagulasi',
            'gas_darah' => 'Gas Darah',
            'sitologi' => 'Sitologi',
            'patologi_anatomi' => 'Patologi Anatomi',
            'molekuler' => 'Molekuler',
            'lainnya' => 'Lainnya',
            default => ucwords(str_replace('_', ' ', $this->category)),
        };
    }

    /**
     * Get specimen type label.
     */
    public function getSpecimenTypeLabelAttribute(): string
    {
        if (!$this->specimen_type) {
            return '-';
        }

        return match ($this->specimen_type) {
            'darah' => 'Darah',
            'urine' => 'Urine',
            'feses' => 'Feses',
            'sputum' => 'Sputum',
            'lendir' => 'Lendir',
            'jaringan' => 'Jaringan',
            'cairan_serebrospinal' => 'Cairan Serebrospinal',
            'cairan_sendi' => 'Cairan Sendi',
            'cairan_pleura' => 'Cairan Pleura',
            'swab' => 'Swab',
            'lainnya' => 'Lainnya',
            default => ucwords(str_replace('_', ' ', $this->specimen_type)),
        };
    }

    /**
     * Get category color for badges.
     */
    public function getCategoryColorAttribute(): string
    {
        if (!$this->category) {
            return 'gray';
        }

        return match ($this->category) {
            'hematologi' => 'danger',
            'kimia_darah' => 'primary',
            'urinalisa' => 'warning',
            'mikrobiologi' => 'success',
            'imunologi' => 'info',
            'serologi' => 'purple',
            'endokrinologi' => 'pink',
            'tumor_marker' => 'orange',
            'elektrolit' => 'cyan',
            'gula_darah' => 'teal',
            'fungsi_ginjal' => 'indigo',
            'fungsi_hati' => 'amber',
            'lemak_darah' => 'lime',
            'koagulasi' => 'rose',
            'gas_darah' => 'sky',
            default => 'gray',
        };
    }
}
