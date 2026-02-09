<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Triage Service
 * 
 * Handles triage calculations for emergency department.
 * Determines triage category based on vital signs and complaints.
 * 
 * Triage Categories:
 * - RED (Emergency): Life-threatening conditions
 * - YELLOW (Urgent): Serious but not immediately life-threatening
 * - GREEN (Non-urgent): Minor conditions
 * - BLACK (Deceased): No hope of survival
 */
class TriageService
{
    /**
     * Triage category constants
     */
    public const CATEGORY_RED = 'red';
    public const CATEGORY_YELLOW = 'yellow';
    public const CATEGORY_GREEN = 'green';
    public const CATEGORY_BLACK = 'black';

    /**
     * Calculate triage category based on vital signs and chief complaint.
     *
     * @param array<string, mixed> $vitalSigns
     * @param string|null $chiefComplaint
     * @return string One of the CATEGORY_* constants
     */
    public static function calculateTriageCategory(array $vitalSigns, ?string $chiefComplaint = null): string
    {
        // Extract vital signs
        $systolicBp = isset($vitalSigns['systolic_bp']) ? (float) $vitalSigns['systolic_bp'] : null;
        $diastolicBp = isset($vitalSigns['diastolic_bp']) ? (float) $vitalSigns['diastolic_bp'] : null;
        $heartRate = isset($vitalSigns['heart_rate']) ? (float) $vitalSigns['heart_rate'] : null;
        $respiratoryRate = isset($vitalSigns['respiratory_rate']) ? (float) $vitalSigns['respiratory_rate'] : null;
        $oxygenSaturation = isset($vitalSigns['oxygen_saturation']) ? (float) $vitalSigns['oxygen_saturation'] : null;
        $bodyTemperature = isset($vitalSigns['body_temperature']) ? (float) $vitalSigns['body_temperature'] : null;
        $gcsTotal = self::calculateGcsTotal($vitalSigns);

        // Check for RED (Emergency) conditions
        if (self::isEmergencyCondition($systolicBp, $diastolicBp, $heartRate, $respiratoryRate, $oxygenSaturation, $gcsTotal, $chiefComplaint)) {
            return self::CATEGORY_RED;
        }

        // Check for YELLOW (Urgent) conditions
        if (self::isUrgentCondition($systolicBp, $diastolicBp, $heartRate, $respiratoryRate, $oxygenSaturation, $bodyTemperature, $gcsTotal, $chiefComplaint)) {
            return self::CATEGORY_YELLOW;
        }

        // Default to GREEN (Non-urgent) if vitals are within normal range
        return self::CATEGORY_GREEN;
    }

    /**
     * Check if the condition is an emergency (RED category).
     *
     * @param float|null $systolicBp
     * @param float|null $diastolicBp
     * @param float|null $heartRate
     * @param float|null $respiratoryRate
     * @param float|null $oxygenSaturation
     * @param int|null $gcsTotal
     * @param string|null $chiefComplaint
     * @return bool
     */
    private static function isEmergencyCondition(
        ?float $systolicBp,
        ?float $diastolicBp,
        ?float $heartRate,
        ?float $respiratoryRate,
        ?float $oxygenSaturation,
        ?int $gcsTotal,
        ?string $chiefComplaint
    ): bool {
        // Critical blood pressure
        if ($systolicBp !== null && ($systolicBp < 90 || $systolicBp > 180)) {
            return true;
        }

        if ($diastolicBp !== null && ($diastolicBp < 60 || $diastolicBp > 120)) {
            return true;
        }

        // Critical heart rate
        if ($heartRate !== null && ($heartRate > 120 || $heartRate < 40)) {
            return true;
        }

        // Critical respiratory rate
        if ($respiratoryRate !== null && ($respiratoryRate > 30 || $respiratoryRate < 8)) {
            return true;
        }

        // Critical oxygen saturation
        if ($oxygenSaturation !== null && $oxygenSaturation < 90) {
            return true;
        }

        // Critical GCS
        if ($gcsTotal !== null && $gcsTotal < 13) {
            return true;
        }

        // Check for severe conditions in chief complaint
        if ($chiefComplaint !== null) {
            $severeKeywords = [
                'berdarah hebat',
                'pendarahan',
                'gumpalan darah',
                'stroke',
                'serangan jantung',
                'infark',
                'cardiac arrest',
                'henti jantung',
                'asfiksia',
                'tidak bernapas',
                'tidak sadar',
                'kejang',
                'kejang berulang',
                'syok',
                'anafilaksis',
                'alergi berat',
                'trauma kepala',
                'kecelakaan berat',
                'luka tembak',
                'luka tusuk',
                'terbakar',
                'tenggelam',
                'tercekik',
                'stridor',
            ];

            $chiefComplaintLower = strtolower($chiefComplaint);
            foreach ($severeKeywords as $keyword) {
                if (str_contains($chiefComplaintLower, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the condition is urgent (YELLOW category).
     *
     * @param float|null $systolicBp
     * @param float|null $diastolicBp
     * @param float|null $heartRate
     * @param float|null $respiratoryRate
     * @param float|null $oxygenSaturation
     * @param float|null $bodyTemperature
     * @param int|null $gcsTotal
     * @param string|null $chiefComplaint
     * @return bool
     */
    private static function isUrgentCondition(
        ?float $systolicBp,
        ?float $diastolicBp,
        ?float $heartRate,
        ?float $respiratoryRate,
        ?float $oxygenSaturation,
        ?float $bodyTemperature,
        ?int $gcsTotal,
        ?string $chiefComplaint
    ): bool {
        // Moderate blood pressure abnormalities
        if ($systolicBp !== null && ($systolicBp >= 160 && $systolicBp <= 180)) {
            return true;
        }

        if ($systolicBp !== null && ($systolicBp >= 90 && $systolicBp < 100)) {
            return true;
        }

        // Moderate heart rate abnormalities
        if ($heartRate !== null && (($heartRate >= 100 && $heartRate <= 120) || ($heartRate >= 40 && $heartRate < 60))) {
            return true;
        }

        // Moderate respiratory rate abnormalities
        if ($respiratoryRate !== null && (($respiratoryRate >= 20 && $respiratoryRate <= 30) || ($respiratoryRate >= 8 && $respiratoryRate < 12))) {
            return true;
        }

        // Moderate oxygen saturation
        if ($oxygenSaturation !== null && ($oxygenSaturation >= 90 && $oxygenSaturation < 95)) {
            return true;
        }

        // Fever or hypothermia
        if ($bodyTemperature !== null && ($bodyTemperature >= 39 || $bodyTemperature < 35)) {
            return true;
        }

        // Moderate GCS
        if ($gcsTotal !== null && ($gcsTotal >= 13 && $gcsTotal <= 14)) {
            return true;
        }

        // Check for moderate conditions in chief complaint
        if ($chiefComplaint !== null) {
            $urgentKeywords = [
                'nyeri dada',
                'sesak napas',
                'sesak nafas',
                'demam tinggi',
                'muntah darah',
                'bab berdarah',
                'kejang',
                'pingsan',
                'pusing berat',
                'vertigo',
                'sakit kepala berat',
                'trauma',
                'patah tulang',
                'luka bakar',
                'luka robek',
                'keracunan',
                'dehidrasi',
                'diare berat',
                'muntah terus',
                'kehamilan',
                'kontraksi',
            ];

            $chiefComplaintLower = strtolower($chiefComplaint);
            foreach ($urgentKeywords as $keyword) {
                if (str_contains($chiefComplaintLower, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculate GCS total from physical examination data.
     *
     * @param array<string, mixed> $vitalSigns
     * @return int|null
     */
    private static function calculateGcsTotal(array $vitalSigns): ?int
    {
        $gcsEye = $vitalSigns['gcs_eye'] ?? null;
        $gcsVerbal = $vitalSigns['gcs_verbal'] ?? null;
        $gcsMotor = $vitalSigns['gcs_motor'] ?? null;

        if ($gcsEye === null || $gcsVerbal === null || $gcsMotor === null) {
            return null;
        }

        return (int) $gcsEye + (int) $gcsVerbal + (int) $gcsMotor;
    }

    /**
     * Get triage category label.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => 'MERAH - Emergency',
            self::CATEGORY_YELLOW => 'KUNING - Urgent',
            self::CATEGORY_GREEN => 'HIJAU - Non-Urgent',
            self::CATEGORY_BLACK => 'HITAM - Deceased',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Get triage category short label.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryShortLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => 'MERAH',
            self::CATEGORY_YELLOW => 'KUNING',
            self::CATEGORY_GREEN => 'HIJAU',
            self::CATEGORY_BLACK => 'HITAM',
            default => '-',
        };
    }

    /**
     * Get triage category color.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryColor(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => 'danger',
            self::CATEGORY_YELLOW => 'warning',
            self::CATEGORY_GREEN => 'success',
            self::CATEGORY_BLACK => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get triage category hex color.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryHexColor(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => '#EF4444',
            self::CATEGORY_YELLOW => '#EAB308',
            self::CATEGORY_GREEN => '#22C55E',
            self::CATEGORY_BLACK => '#1F2937',
            default => '#6B7280',
        };
    }

    /**
     * Get all triage categories as options array.
     *
     * @return array<string, string>
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_RED => 'MERAH - Emergency (Gawat Darurat)',
            self::CATEGORY_YELLOW => 'KUNING - Urgent (Darurat)',
            self::CATEGORY_GREEN => 'HIJAU - Non-Urgent (Tidak Darurat)',
            self::CATEGORY_BLACK => 'HITAM - Deceased (Meninggal)',
        ];
    }

    /**
     * Get triage category description.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryDescription(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => 'Segera ditangani, resiko kematian tinggi',
            self::CATEGORY_YELLOW => 'Dapat ditunda pelayanan < 60 menit',
            self::CATEGORY_GREEN => 'Dapat ditunda pelayanan > 60 menit',
            self::CATEGORY_BLACK => 'Meninggal/tidak ada harapan',
            default => '',
        };
    }

    /**
     * Get triage category wait time recommendation.
     *
     * @param string $category
     * @return string
     */
    public static function getCategoryWaitTime(string $category): string
    {
        return match ($category) {
            self::CATEGORY_RED => 'Segera (0 menit)',
            self::CATEGORY_YELLOW => '< 60 menit',
            self::CATEGORY_GREEN => '> 60 menit',
            self::CATEGORY_BLACK => '-',
            default => '-',
        };
    }
}
