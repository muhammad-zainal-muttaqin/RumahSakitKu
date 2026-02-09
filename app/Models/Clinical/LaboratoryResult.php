<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Employee;
use App\Models\MasterData\LabTest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Laboratory Result Model
 *
 * Represents an individual laboratory test result.
 * Contains the test value, reference ranges, and validation status.
 *
 * @property int $id
 * @property int $laboratory_order_id
 * @property int|null $lab_test_id
 * @property float|null $result_value
 * @property string|null $result_text
 * @property string|null $flag
 * @property string|null $reference_range
 * @property string|null $unit
 * @property string|null $notes
 * @property string|null $test_method
 * @property string|null $analyzer_machine
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $flag_color
 * @property-read string $flag_label
 * @property-read string $display_value
 * @property-read bool $is_abnormal
 * @property-read bool $is_critical
 * @property-read bool $is_validated
 * @property-read string $formatted_reference_range
 * @property-read LaboratoryOrder $laboratoryOrder
 * @property-read LabTest|null $labTest
 * @property-read Employee|null $validatedBy
 *
 * @method static Builder|LaboratoryResult withFlag(string $flag)
 * @method static Builder|LaboratoryResult abnormal()
 * @method static Builder|LaboratoryResult critical()
 * @method static Builder|LaboratoryResult validated()
 * @method static Builder|LaboratoryResult pending()
 */
class LaboratoryResult extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'laboratory_results';

    protected $fillable = [
        'laboratory_order_id',
        'lab_test_id',
        'result_value',
        'result_text',
        'flag',
        'reference_range',
        'unit',
        'notes',
        'test_method',
        'analyzer_machine',
        'validated_by',
        'validated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the lab order that owns this result.
     */
    public function laboratoryOrder(): BelongsTo
    {
        return $this->belongsTo(LaboratoryOrder::class, 'laboratory_order_id');
    }

    /**
     * Get the lab test definition.
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    /**
     * Get the user who validated this result.
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }

    /**
     * Scope a query to only include results with a specific flag.
     */
    public function scopeWithFlag($query, string $flag)
    {
        return $query->where('flag', $flag);
    }

    /**
     * Scope a query to only include abnormal results.
     */
    public function scopeAbnormal($query)
    {
        return $query->whereIn('flag', ['low', 'high', 'abnormal', 'critical']);
    }

    /**
     * Scope a query to only include critical results.
     */
    public function scopeCritical($query)
    {
        return $query->where('flag', 'critical');
    }

    /**
     * Scope a query to only include validated results.
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    /**
     * Scope a query to only include pending results.
     */
    public function scopePending($query)
    {
        return $query->whereNull('result_value')->whereNull('result_text');
    }

    /**
     * Get flag color for badges.
     */
    public function getFlagColorAttribute(): string
    {
        return match ($this->flag) {
            'normal' => 'success',
            'low' => 'warning',
            'high' => 'warning',
            'abnormal' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get flag label.
     */
    public function getFlagLabelAttribute(): string
    {
        return match ($this->flag) {
            'normal' => 'Normal',
            'low' => 'Rendah',
            'high' => 'Tinggi',
            'abnormal' => 'Abnormal',
            'critical' => 'Kritis',
            default => '-',
        };
    }

    /**
     * Get the display value (numeric or text).
     */
    public function getDisplayValueAttribute(): string
    {
        if ($this->result_value !== null) {
            return (string) $this->result_value;
        }

        if ($this->result_text !== null) {
            return $this->result_text;
        }

        return '-';
    }

    /**
     * Check if result is abnormal.
     */
    public function getIsAbnormalAttribute(): bool
    {
        return in_array($this->flag, ['low', 'high', 'abnormal', 'critical']);
    }

    /**
     * Check if result is critical.
     */
    public function getIsCriticalAttribute(): bool
    {
        return $this->flag === 'critical';
    }

    /**
     * Check if result has been validated.
     */
    public function getIsValidatedAttribute(): bool
    {
        return $this->validated_at !== null;
    }

    /**
     * Get formatted reference range with unit.
     */
    public function getFormattedReferenceRangeAttribute(): string
    {
        if ($this->reference_range && $this->unit) {
            return "{$this->reference_range} {$this->unit}";
        }

        return $this->reference_range ?? '-';
    }
}
