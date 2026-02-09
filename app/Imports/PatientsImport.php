<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use Exception;
use App\Models\Patient\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
 * Patients Import Class
 * 
 * Imports patient data from Excel with validation and duplicate handling.
 */
class PatientsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
{
    use SkipsErrors;
    use SkipsFailures;

    /** @var int Counter for imported records */
    private int $importedCount = 0;

    /** @var int Counter for skipped duplicates */
    private int $skippedCount = 0;

    /** @var bool Whether to skip duplicates */
    private bool $skipDuplicates;

    /** @var array<string> Collected error messages */
    private array $errorMessages = [];

    /**
     * @param bool $skipDuplicates Skip records with duplicate NIK
     */
    public function __construct(bool $skipDuplicates = true)
    {
        $this->skipDuplicates = $skipDuplicates;
    }

    /**
     * Create patient model from row data.
     *
     * @param array<string, mixed> $row
     * @return Patient|null
     */
    public function model(array $row): ?Patient
    {
        // Check for duplicate NIK
        if ($this->skipDuplicates && !empty($row['nik'])) {
            $existing = Patient::where('nik', $row['nik'])->first();
            if ($existing) {
                $this->skippedCount++;
                Log::info("Patient import: Skipped duplicate NIK {$row['nik']}");
                return null;
            }
        }

        $this->importedCount++;

        return new Patient([
            'medical_record_number' => $this->generateMRN(),
            'name' => $row['nama'],
            'nik' => $row['nik'] ?? null,
            'birth_place' => $row['tempat_lahir'] ?? null,
            'birth_date' => $this->parseDate($row['tanggal_lahir']),
            'gender' => $this->normalizeGender($row['jenis_kelamin']),
            'blood_type' => $row['golongan_darah'] ?? null,
            'address' => $row['alamat'] ?? null,
            'phone' => $row['telepon'] ?? null,
            'email' => $row['email'] ?? null,
            'emergency_contact_name' => $row['kontak_darurat_nama'] ?? null,
            'emergency_contact_phone' => $row['kontak_darurat_telepon'] ?? null,
            'marital_status' => $this->normalizeMaritalStatus($row['status_perkawinan'] ?? null),
            'occupation' => $row['pekerjaan'] ?? null,
            'insurance_type' => $this->normalizeInsuranceType($row['jenis_asuransi'] ?? 'self_pay'),
            'insurance_number' => $row['nomor_asuransi'] ?? null,
            'bpjs_card_number' => $row['nomor_bpjs'] ?? null,
            'is_active' => true,
            'registered_at' => now(),
        ]);
    }

    /**
     * Validation rules for import.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'nik' => 'nullable|string|size:16|unique:patients,nik',
            'tanggal_lahir' => 'required|date_format:d/m/Y',
            'jenis_kelamin' => 'required|string|in:male,female,L,P,Laki-laki,Perempuan',
            'email' => 'nullable|email|unique:patients,email',
            'telepon' => 'nullable|string|max:20',
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
            'nama.required' => 'Nama pasien wajib diisi',
            'nik.size' => 'NIK harus 16 digit',
            'nik.unique' => 'NIK sudah terdaftar',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi',
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus dd/mm/yyyy',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
        ];
    }

    /**
     * Handle import errors.
     *
     * @param Throwable $e
     */
    public function onError(Throwable $e): void
    {
        Log::error('Patient import error: ' . $e->getMessage());
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
     * Parse date from various formats.
     *
     * @param string|null $date
     * @return Carbon|null
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'];
        
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date);
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Normalize gender value.
     *
     * @param string $gender
     * @return string
     */
    private function normalizeGender(string $gender): string
    {
        $gender = strtolower(trim($gender));
        
        return match ($gender) {
            'male', 'laki-laki', 'laki laki', 'l' => 'male',
            'female', 'perempuan', 'p' => 'female',
            default => 'male',
        };
    }

    /**
     * Normalize marital status.
     *
     * @param string|null $status
     * @return string|null
     */
    private function normalizeMaritalStatus(?string $status): ?string
    {
        if (empty($status)) {
            return null;
        }

        $status = strtolower(trim($status));
        
        return match ($status) {
            'single', 'belum menikah', 'belum kawin', 'bk' => 'single',
            'married', 'menikah', 'kawin', 'k' => 'married',
            'divorced', 'cerai' => 'divorced',
            'widowed', 'janda', 'duda' => 'widowed',
            default => null,
        };
    }

    /**
     * Normalize insurance type.
     *
     * @param string|null $type
     * @return string
     */
    private function normalizeInsuranceType(?string $type): string
    {
        if (empty($type)) {
            return 'self_pay';
        }

        $type = strtolower(trim($type));
        
        return match ($type) {
            'bpjs', 'jkn', 'jamkesmas', 'jamkesda' => 'bpjs',
            'private', 'swasta', 'asuransi swasta' => 'private',
            'corporate', 'perusahaan', 'asuransi perusahaan' => 'corporate',
            'government', 'pemerintah' => 'government',
            'self_pay', 'umum', 'sendiri', 'pribadi' => 'self_pay',
            default => 'self_pay',
        };
    }

    /**
     * Generate unique medical record number.
     *
     * @return string
     */
    private function generateMRN(): string
    {
        $prefix = 'RM';
        $year = date('Y');
        $random = strtoupper(substr(uniqid(), -6));
        
        return "{$prefix}{$year}{$random}";
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
