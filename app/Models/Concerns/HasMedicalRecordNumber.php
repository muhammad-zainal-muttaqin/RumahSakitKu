<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasMedicalRecordNumber
 *
 * Auto-generates unique medical record numbers (MRN) on patient creation.
 * Format: YYMMDD-XX (where XX is auto-increment sequence)
 * Includes retry logic for uniqueness and a random fallback.
 *
 * Features:
 * - Automatic MRN generation on model creation
 * - Unique MRN validation with retry logic
 * - Fallback MRN generation using random sequence
 * - MRN parsing and validation utilities
 * - Query scopes for MRN-based filtering
 *
 * Usage:
 * ```php
 * class Patient extends Model
 * {
 *     use HasMedicalRecordNumber;
 *
 *     // Optional: Custom prefix (default: empty)
 *     protected string $mrnPrefix = 'MR';
 * }
 * ```
 *
 * @package App\Models\Concerns
 *
 * @property string|null $medical_record_number The generated medical record number
 *
 * @method static Builder byMedicalRecordNumber(string $mrn)
 * @method static Builder byMrnDateRange(string $startDate, string $endDate)
 * @method static static|null findByMedicalRecordNumber(string $mrn)
 * @method static static findByMedicalRecordNumberOrFail(string $mrn)
 * @method static bool isValidMedicalRecordNumber(string $mrn)
 * @method static Carbon|null extractDateFromMrn(string $mrn)
 */
trait HasMedicalRecordNumber
{
    /**
     * Medical record number prefix.
     *
     * @var string
     */
    protected string $mrnPrefix = '';

    /**
     * Boot the medical record number trait.
     *
     * Automatically generates MRN when creating a new model if not provided.
     *
     * @return void
     */
    public static function bootHasMedicalRecordNumber(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->medical_record_number)) {
                $model->medical_record_number = $model->generateMedicalRecordNumber();
            }
        });
    }

    /**
     * Generate a unique medical record number.
     *
     * Format: YYMMDD-XX
     * - YYMMDD: Current date
     * - XX: Auto-increment sequence (01-99)
     *
     * Includes retry logic to ensure uniqueness.
     *
     * @return string The generated medical record number
     * @throws Exception When unable to generate unique MRN after max retries
     */
    public function generateMedicalRecordNumber(): string
    {
        $baseDate = $this->registered_at ? Carbon::parse($this->registered_at) : now();
        $prefix = $this->getMrnPrefix();
        $prefixPart = $prefix !== '' ? "{$prefix}-" : '';
        $maxSequence = 99;

        // Generate MRN with retry logic for uniqueness
        $maxRetries = 10;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $pickedDate = null;
            $nextSequence = null;

            for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
                $date = $baseDate->copy()->addDays($dayOffset)->format('ymd');
                $lastSequence = $this->getLastSequenceForDate($date);
                if ($lastSequence < $maxSequence) {
                    $pickedDate = $date;
                    $nextSequence = $lastSequence + 1;
                    break;
                }
            }

            if ($pickedDate === null || $nextSequence === null) {
                break;
            }

            $sequenceStr = str_pad((string) $nextSequence, 2, '0', STR_PAD_LEFT);
            $mrn = "{$prefixPart}{$pickedDate}-{$sequenceStr}";

            if (!$this->medicalRecordNumberExists($mrn)) {
                return $mrn;
            }

            // If exists, increment and try again
            $nextSequence++;
            $attempt++;
        }

        // If all retries failed, use timestamp-based fallback
        $fallbackMrn = $this->generateFallbackMrn($baseDate->format('ymd'));

        Log::warning('Medical record number generation required fallback', [
            'attempted' => $attempt,
            'fallback_mrn' => $fallbackMrn,
        ]);

        return $fallbackMrn;
    }

    /**
     * Get the last sequence number for a given date.
     *
     * Queries the database to find the highest sequence number used today.
     *
     * @param string $date The date in YYMMDD format
     * @return int The last sequence number (0 if none found)
     */
    protected function getLastSequenceForDate(string $date): int
    {
        $prefix = $this->getMrnPrefix();
        $prefixPart = $prefix !== '' ? "{$prefix}-" : '';
        $prefixValue = "{$prefixPart}{$date}-";

        try {
            $lastMrn = static::where('medical_record_number', 'like', $prefixValue . '%')
                ->orderBy('medical_record_number', 'desc')
                ->value('medical_record_number');

            if ($lastMrn) {
                // Extract sequence number from MRN
                $parts = explode('-', $lastMrn);
                $sequence = (int) end($parts);
                return $sequence;
            }
        } catch (Exception $e) {
            Log::error('Error getting last MRN sequence', [
                'error' => $e->getMessage(),
                'date' => $date,
            ]);
        }

        return 0;
    }

    /**
     * Check if medical record number already exists.
     *
     * @param string $mrn The medical record number to check
     * @return bool True if the MRN already exists
     */
    protected function medicalRecordNumberExists(string $mrn): bool
    {
        try {
            return static::where('medical_record_number', $mrn)
                ->where($this->getKeyName(), '!=', $this->getKey() ?? 0)
                ->exists();
        } catch (Exception $e) {
            Log::error('Error checking MRN existence', [
                'error' => $e->getMessage(),
                'mrn' => $mrn,
            ]);
            return false;
        }
    }

    /**
     * Generate fallback MRN using random sequence.
     *
     * Used when sequential MRN generation fails uniqueness checks.
     * Format: YYMMDD-XX (random fallback sequence)
     *
     * @param string $date The date in YYMMDD format
     * @return string The fallback medical record number
     */
    protected function generateFallbackMrn(string $date): string
    {
        $random = random_int(1, 99);
        $sequence = str_pad((string) $random, 2, '0', STR_PAD_LEFT);
        $prefix = $this->getMrnPrefix();
        $prefixPart = $prefix !== '' ? "{$prefix}-" : '';

        return "{$prefixPart}{$date}-{$sequence}";
    }

    /**
     * Get MRN prefix.
     *
     * Returns the model's custom prefix or defaults to empty string.
     *
     * @return string The prefix to use for MRNs
     */
    protected function getMrnPrefix(): string
    {
        return property_exists($this, 'mrnPrefix')
            ? $this->mrnPrefix
            : '';
    }

    /**
     * Validate medical record number format.
     *
     * Checks if the MRN matches the expected pattern: YYMMDD-XX
     *
     * @param string $mrn The medical record number to validate
     * @return bool True if the format is valid
     */
    public static function isValidMedicalRecordNumber(string $mrn): bool
    {
        $prefix = (new static())->getMrnPrefix();
        $prefixPart = $prefix !== '' ? preg_quote($prefix, '/') . '-' : '';
        $pattern = '/^' . $prefixPart . '\d{6}-\d{2}$/';

        return (bool) preg_match($pattern, $mrn);
    }

    /**
     * Extract date from medical record number.
     *
     * Parses the date component from an MRN.
     *
     * @param string $mrn The medical record number
     * @return Carbon|null The extracted date or null if invalid
     */
    public static function extractDateFromMrn(string $mrn): ?Carbon
    {
        $parts = explode('-', $mrn);
        $prefix = (new static())->getMrnPrefix();
        $datePart = $prefix !== '' ? ($parts[1] ?? null) : ($parts[0] ?? null);

        if ($datePart && strlen($datePart) === 6) {
            try {
                return Carbon::createFromFormat('ymd', $datePart);
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Find patient by medical record number.
     *
     * @param string $mrn The medical record number to search for
     * @return static|null The model instance or null if not found
     */
    public static function findByMedicalRecordNumber(string $mrn): ?static
    {
        return static::where('medical_record_number', $mrn)->first();
    }

    /**
     * Find patient by medical record number or fail.
     *
     * @param string $mrn The medical record number to search for
     * @return static The model instance
     * @throws ModelNotFoundException If not found
     */
    public static function findByMedicalRecordNumberOrFail(string $mrn): static
    {
        return static::where('medical_record_number', $mrn)->firstOrFail();
    }

    /**
     * Scope query by medical record number.
     *
     * @param Builder $query
     * @param string $mrn The medical record number
     * @return Builder
     */
    public function scopeByMedicalRecordNumber($query, string $mrn)
    {
        return $query->where('medical_record_number', $mrn);
    }

    /**
     * Scope query by MRN date range.
     *
     * Filters records based on the date encoded in their MRN.
     *
     * @param Builder $query
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return Builder
     */
    public function scopeByMrnDateRange($query, string $startDate, string $endDate)
    {
        $prefix = $this->getMrnPrefix();
        $prefixPart = $prefix !== '' ? "{$prefix}-" : '';
        $startPrefix = $prefixPart . str_replace('-', '', $startDate);
        $endPrefix = $prefixPart . str_replace('-', '', $endDate);

        return $query->whereBetween('medical_record_number', [$startPrefix . '-00', $endPrefix . '-99']);
    }

    /**
     * Regenerate medical record number (use with caution).
     *
     * Generates a new MRN and saves it to the model.
     *
     * @return string The new medical record number
     * @throws Exception When generation fails
     */
    public function regenerateMedicalRecordNumber(): string
    {
        $newMrn = $this->generateMedicalRecordNumber();
        $this->medical_record_number = $newMrn;
        $this->save();

        Log::info('Medical record number regenerated', [
            'model_id' => $this->getKey(),
            'new_mrn' => $newMrn,
        ]);

        return $newMrn;
    }

    /**
     * Get medical record number without prefix.
     *
     * Returns just the date and sequence components.
     *
     * @return string|null The MRN without prefix, or null if not set
     */
    public function getMrnWithoutPrefix(): ?string
    {
        $mrn = $this->medical_record_number;

        if (!$mrn) {
            return null;
        }

        $prefix = $this->getMrnPrefix();
        if ($prefix === '') {
            return $mrn;
        }

        $parts = explode('-', $mrn);
        array_shift($parts);
        return implode('-', $parts);
    }

    /**
     * Get formatted medical record number for display.
     *
     * @return string|null The formatted MRN or null if not set
     */
    public function getFormattedMrn(): ?string
    {
        $mrn = $this->medical_record_number;

        if (!$mrn) {
            return null;
        }

        // Format: YYMMDD-XX as is
        return $mrn;
    }
}
