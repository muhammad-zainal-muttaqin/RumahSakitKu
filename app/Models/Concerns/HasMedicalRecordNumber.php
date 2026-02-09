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
 * Format: RM-YYYYMMDD-XXXX (where XXXX is auto-increment sequence)
 * Includes retry logic for uniqueness and a timestamp-based fallback.
 *
 * Features:
 * - Automatic MRN generation on model creation
 * - Unique MRN validation with retry logic
 * - Fallback MRN generation using timestamp
 * - MRN parsing and validation utilities
 * - Query scopes for MRN-based filtering
 *
 * Usage:
 * ```php
 * class Patient extends Model
 * {
 *     use HasMedicalRecordNumber;
 *
 *     // Optional: Custom prefix (default: RM)
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
    protected string $mrnPrefix = 'RM';

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
     * Format: RM-YYYYMMDD-XXXX
     * - RM: Prefix (Rekam Medis)
     * - YYYYMMDD: Current date
     * - XXXX: Auto-increment sequence (0001-9999)
     *
     * Includes retry logic to ensure uniqueness.
     *
     * @return string The generated medical record number
     * @throws Exception When unable to generate unique MRN after max retries
     */
    public function generateMedicalRecordNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "{$this->getMrnPrefix()}-{$date}-";

        // Get the last sequence number for today
        $lastSequence = $this->getLastSequenceForDate($date);
        $nextSequence = $lastSequence + 1;

        // Generate MRN with retry logic for uniqueness
        $maxRetries = 10;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $sequenceStr = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $mrn = $prefix . $sequenceStr;

            if (!$this->medicalRecordNumberExists($mrn)) {
                return $mrn;
            }

            // If exists, increment and try again
            $nextSequence++;
            $attempt++;
        }

        // If all retries failed, use timestamp-based fallback
        $fallbackMrn = $this->generateFallbackMrn($date);

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
     * @param string $date The date in YYYYMMDD format
     * @return int The last sequence number (0 if none found)
     */
    protected function getLastSequenceForDate(string $date): int
    {
        $prefix = "{$this->getMrnPrefix()}-{$date}-";

        try {
            $lastMrn = static::where('medical_record_number', 'like', $prefix . '%')
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
     * Generate fallback MRN using timestamp.
     *
     * Used when sequential MRN generation fails uniqueness checks.
     * Format: RM-YYYYMMDD-HHMMSSXXXX (includes time and random)
     *
     * @param string $date The date in YYYYMMDD format
     * @return string The fallback medical record number
     */
    protected function generateFallbackMrn(string $date): string
    {
        $timestamp = now()->format('His'); // Hour, minute, second
        $random = random_int(1000, 9999);

        return "{$this->getMrnPrefix()}-{$date}-{$timestamp}{$random}";
    }

    /**
     * Get MRN prefix.
     *
     * Returns the model's custom prefix or defaults to 'RM'.
     *
     * @return string The prefix to use for MRNs
     */
    protected function getMrnPrefix(): string
    {
        return property_exists($this, 'mrnPrefix')
            ? $this->mrnPrefix
            : 'RM';
    }

    /**
     * Validate medical record number format.
     *
     * Checks if the MRN matches the expected pattern: PREFIX-YYYYMMDD-XXXX
     *
     * @param string $mrn The medical record number to validate
     * @return bool True if the format is valid
     */
    public static function isValidMedicalRecordNumber(string $mrn): bool
    {
        $prefix = 'RM';
        $pattern = '/^' . $prefix . '-\d{8}-\d{4}$/';

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

        if (count($parts) >= 2 && strlen($parts[1]) === 8) {
            try {
                return Carbon::createFromFormat('Ymd', $parts[1]);
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
        $startPrefix = "{$this->getMrnPrefix()}-" . str_replace('-', '', $startDate);
        $endPrefix = "{$this->getMrnPrefix()}-" . str_replace('-', '', $endDate);

        return $query->whereBetween('medical_record_number', [$startPrefix, $endPrefix . '-9999']);
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

        $parts = explode('-', $mrn);

        // Remove prefix (first element)
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

        // Format: RM-YYYYMMDD-XXXX as is
        return $mrn;
    }
}
