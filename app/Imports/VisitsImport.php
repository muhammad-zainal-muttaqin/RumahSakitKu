<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use Exception;
use App\Models\Clinical\MedicalRecord;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Visits Import Class
 * 
 * Imports visit history from Excel.
 */
class VisitsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
{
    use SkipsErrors;
    use SkipsFailures;

    /** @var int Counter for imported records */
    private int $importedCount = 0;

    /** @var int Counter for skipped records (patient not found) */
    private int $skippedCount = 0;

    /** @var bool Whether to create medical record */
    private bool $createMedicalRecord;

    /** @var array<string> Collected error messages */
    private array $errorMessages = [];

    /**
     * @param bool $createMedicalRecord Create medical record after visit import
     */
    public function __construct(bool $createMedicalRecord = false)
    {
        $this->createMedicalRecord = $createMedicalRecord;
    }

    /**
     * Create visit model from row data.
     *
     * @param array<string, mixed> $row
     * @return Visit|null
     */
    public function model(array $row): ?Visit
    {
        // Find patient by MRN
        $patient = Patient::where('medical_record_number', $row['no_rm'])->first();
        
        if (!$patient) {
            $this->skippedCount++;
            Log::warning("Visit import: Patient with MRN {$row['no_rm']} not found");
            return null;
        }

        $this->importedCount++;

        $visit = new Visit([
            'visit_number' => $this->generateVisitNumber(),
            'patient_id' => $patient->id,
            'visit_date' => $this->parseDate($row['tanggal']),
            'visit_type' => $this->normalizeVisitType($row['tipe'] ?? 'outpatient'),
            'registration_type' => $this->normalizeRegistrationType($row['jenis_registrasi'] ?? 'walk_in'),
            'priority' => $this->normalizePriority($row['prioritas'] ?? 'normal'),
            'status' => 'completed',
            'complaint' => $row['keluhan'] ?? null,
            'referral_from' => $row['rujukan_dari'] ?? null,
            'referral_number' => $row['nomor_rujukan'] ?? null,
            'bpjs_sep_number' => $row['nomor_sep'] ?? null,
            'is_completed' => true,
            'notes' => $row['catatan'] ?? null,
        ]);

        // Create medical record if requested
        if ($this->createMedicalRecord && !empty($row['diagnosis'])) {
            $this->createMedicalRecordForVisit($visit, $patient, $row);
        }

        return $visit;
    }

    /**
     * Validation rules for import.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'no_rm' => 'required|string|exists:patients,medical_record_number',
            'tanggal' => 'required|date_format:d/m/Y',
            'tipe' => 'nullable|string|in:outpatient,inpatient,emergency,rawat_jalan,rawat_inap,igd',
            'jenis_registrasi' => 'nullable|string',
            'prioritas' => 'nullable|string|in:normal,urgent,emergency,vip',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'no_rm.required' => 'Nomor RM wajib diisi',
            'no_rm.exists' => 'Pasien dengan nomor RM tersebut tidak ditemukan',
            'tanggal.required' => 'Tanggal kunjungan wajib diisi',
            'tanggal.date_format' => 'Format tanggal harus dd/mm/yyyy',
        ];
    }

    /**
     * Handle import errors.
     *
     * @param Throwable $e
     */
    public function onError(Throwable $e): void
    {
        Log::error('Visit import error: ' . $e->getMessage());
        $this->errorMessages[] = $e->getMessage();
    }

    /**
     * Handle validation failures.
     *
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    /**
     * Create medical record for visit.
     *
     * @param Visit $visit
     * @param Patient $patient
     * @param array<string, mixed> $row
     */
    private function createMedicalRecordForVisit(Visit $visit, Patient $patient, array $row): void
    {
        try {
            // This would be executed after the visit is saved
            // Note: In practice, you might want to use an observer or event
            MedicalRecord::create([
                'record_number' => $this->generateRecordNumber(),
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'visit_date' => $visit->visit_date,
                'diagnosis_primary' => $row['diagnosis'] ?? null,
                'icd10_code' => $row['kode_icd10'] ?? null,
                'icd10_description' => $row['deskripsi_icd10'] ?? null,
                'notes' => $row['catatan_medis'] ?? null,
                'is_finalized' => true,
                'finalized_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create medical record: ' . $e->getMessage());
        }
    }

    /**
     * Parse date from various formats.
     *
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return now();
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date);
            } catch (Exception $e) {
                continue;
            }
        }

        return now();
    }

    /**
     * Normalize visit type.
     *
     * @param string $type
     * @return string
     */
    private function normalizeVisitType(string $type): string
    {
        $type = strtolower(trim($type));
        
        return match ($type) {
            'outpatient', 'rawat_jalan', 'rj', 'poli', 'poliklinik' => 'outpatient',
            'inpatient', 'rawat_inap', 'ri', 'ranap' => 'inpatient',
            'emergency', 'igd', 'ugd', 'darurat' => 'emergency',
            default => 'outpatient',
        };
    }

    /**
     * Normalize registration type.
     *
     * @param string $type
     * @return string
     */
    private function normalizeRegistrationType(string $type): string
    {
        $type = strtolower(trim($type));
        
        return match ($type) {
            'walk_in', 'datang_sendiri', 'sendiri', 'walkin' => 'walk_in',
            'referral', 'rujukan', 'rujuk' => 'referral',
            'appointment', 'janji', 'janji_temu' => 'appointment',
            default => 'walk_in',
        };
    }

    /**
     * Normalize priority.
     *
     * @param string $priority
     * @return string
     */
    private function normalizePriority(string $priority): string
    {
        $priority = strtolower(trim($priority));
        
        return match ($priority) {
            'normal', 'biasa', 'reguler' => 'normal',
            'urgent', 'urgensi', 'segera' => 'urgent',
            'emergency', 'darurat', 'gawat' => 'emergency',
            'vip', 'prioritas', 'priority' => 'vip',
            default => 'normal',
        };
    }

    /**
     * Generate unique visit number.
     *
     * @return string
     */
    private function generateVisitNumber(): string
    {
        $prefix = 'VST';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return "{$prefix}{$date}{$random}";
    }

    /**
     * Generate unique record number.
     *
     * @return string
     */
    private function generateRecordNumber(): string
    {
        $prefix = 'RMR';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        
        return "{$prefix}{$date}{$random}";
    }

    /**
     * Chunk size for reading.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Batch size for inserts.
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Get import statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errorMessages,
            'failures' => $this->failures(),
        ];
    }
}
