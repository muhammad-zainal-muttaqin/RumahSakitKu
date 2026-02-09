<?php

declare(strict_types=1);

namespace App\Models\Clinical;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\MasterData\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Radiology Result Model
 *
 * Represents the results of a radiology examination.
 * Contains images, reports, and radiologist findings.
 *
 * @property int $id
 * @property int $radiology_order_id
 * @property array|null $result_images
 * @property string|null $report_text
 * @property string|null $conclusion
 * @property string|null $recommendation
 * @property int|null $radiologist_id
 * @property Carbon|null $reported_at
 * @property string|null $technician_notes
 * @property array|null $exposure_parameters
 * @property string|null $dose_info
 * @property string|null $quality_assurance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read bool $is_reported
 * @property-read array $image_urls
 * @property-read string|null $first_image_url
 * @property-read string $formatted_report
 * @property-read string $radiologist_name
 * @property-read string $report_date_formatted
 * @property-read RadiologyOrder $radiologyOrder
 * @property-read Employee|null $radiologist
 *
 * @method static Builder|RadiologyResult byRadiologist(int $radiologistId)
 * @method static Builder|RadiologyResult reportedToday()
 * @method static Builder|RadiologyResult reported()
 * @method static Builder|RadiologyResult pending()
 * @method static Builder|RadiologyResult search(string $search)
 */
class RadiologyResult extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'radiology_results';

    protected $fillable = [
        'radiology_order_id',
        'result_images',
        'report_text',
        'conclusion',
        'recommendation',
        'radiologist_id',
        'reported_at',
        'technician_notes',
        'exposure_parameters',
        'dose_info',
        'quality_assurance',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'result_images' => 'array',
        'exposure_parameters' => 'array',
        'reported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the radiology order that owns this result.
     */
    public function radiologyOrder(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id');
    }

    /**
     * Get the radiologist who reported this result.
     */
    public function radiologist(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'radiologist_id');
    }

    /**
     * Scope a query to only include results reported by a specific radiologist.
     */
    public function scopeByRadiologist($query, int $radiologistId)
    {
        return $query->where('radiologist_id', $radiologistId);
    }

    /**
     * Scope a query to only include results reported today.
     */
    public function scopeReportedToday($query)
    {
        return $query->whereDate('reported_at', today());
    }

    /**
     * Scope a query to only include reported results.
     */
    public function scopeReported($query)
    {
        return $query->whereNotNull('reported_at');
    }

    /**
     * Scope a query to only include pending results.
     */
    public function scopePending($query)
    {
        return $query->whereNull('reported_at');
    }

    /**
     * Scope a query to search by conclusion or report text.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('conclusion', 'like', "%{$search}%")
                ->orWhere('report_text', 'like', "%{$search}%")
                ->orWhere('recommendation', 'like', "%{$search}%");
        });
    }

    /**
     * Check if result has been reported.
     */
    public function getIsReportedAttribute(): bool
    {
        return $this->reported_at !== null;
    }

    /**
     * Get image URLs attribute.
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->result_images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->result_images);
    }

    /**
     * Get first image URL.
     */
    public function getFirstImageUrlAttribute(): ?string
    {
        $urls = $this->image_urls;
        return $urls[0] ?? null;
    }

    /**
     * Get formatted report with all sections.
     */
    public function getFormattedReportAttribute(): string
    {
        $sections = [];

        if ($this->report_text) {
            $sections[] = "HASIL PEMERIKSAAN:\n{$this->report_text}";
        }

        if ($this->conclusion) {
            $sections[] = "KESIMPULAN:\n{$this->conclusion}";
        }

        if ($this->recommendation) {
            $sections[] = "SARAN:\n{$this->recommendation}";
        }

        return implode("\n\n", $sections);
    }

    /**
     * Get radiologist name.
     */
    public function getRadiologistNameAttribute(): string
    {
        return $this->radiologist?->name ?? '-';
    }

    /**
     * Get report date formatted.
     */
    public function getReportDateFormattedAttribute(): string
    {
        return $this->reported_at?->format('d M Y H:i') ?? '-';
    }
}
