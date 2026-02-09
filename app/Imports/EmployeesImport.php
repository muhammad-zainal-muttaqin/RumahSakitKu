<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use Exception;
use App\Models\MasterData\Employee;
use Carbon\Carbon;
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
 * Employees Import Class
 * 
 * Imports employee/staff data from Excel.
 */
class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
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
     * @param bool $skipDuplicates Skip records with duplicate NIP
     */
    public function __construct(bool $skipDuplicates = true)
    {
        $this->skipDuplicates = $skipDuplicates;
    }

    /**
     * Create employee model from row data.
     *
     * @param array<string, mixed> $row
     * @return Employee|null
     */
    public function model(array $row): ?Employee
    {
        // Check for duplicate NIP
        if ($this->skipDuplicates && !empty($row['nip'])) {
            $existing = Employee::where('nip', $row['nip'])->first();
            if ($existing) {
                $this->skippedCount++;
                Log::info("Employee import: Skipped duplicate NIP {$row['nip']}");
                return null;
            }
        }

        $this->importedCount++;

        $isDoctor = $this->isDoctor($row['posisi'] ?? $row['jabatan'] ?? '');
        $isNurse = $this->isNurse($row['posisi'] ?? $row['jabatan'] ?? '');

        return new Employee([
            'employee_code' => $this->generateEmployeeCode(),
            'nip' => $row['nip'] ?? null,
            'name' => $row['nama'],
            'gender' => $this->normalizeGender($row['jenis_kelamin'] ?? null),
            'birth_date' => $this->parseDate($row['tanggal_lahir'] ?? null),
            'address' => $row['alamat'] ?? null,
            'phone' => $row['telepon'] ?? null,
            'email' => $row['email'] ?? null,
            'employee_type' => $this->normalizeEmployeeType($row['tipe_pegawai'] ?? 'tetap'),
            'is_doctor' => $isDoctor,
            'doctor_title' => $isDoctor ? ($row['gelar_dokter'] ?? 'dr.') : null,
            'sip_number' => $isDoctor ? ($row['nomor_sip'] ?? null) : null,
            'sip_expiry_date' => $isDoctor ? $this->parseDate($row['sip_expired'] ?? null) : null,
            'str_number' => $isDoctor ? ($row['nomor_str'] ?? null) : null,
            'str_expiry_date' => $isDoctor ? $this->parseDate($row['str_expired'] ?? null) : null,
            'is_nurse' => $isNurse,
            'sip_nurse_number' => $isNurse ? ($row['nomor_sip_perawat'] ?? null) : null,
            'profession' => $row['profesi'] ?? ($row['posisi'] ?? $row['jabatan'] ?? null),
            'certification_number' => $row['nomor_sertifikasi'] ?? null,
            'join_date' => $this->parseDate($row['tanggal_masuk'] ?? now()),
            'status' => 'aktif',
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
            'nip' => 'nullable|string|unique:employees,nip',
            'posisi' => 'required|string|max:100',
            'jenis_kelamin' => 'nullable|string|in:male,female,L,P,Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date_format:d/m/Y',
            'email' => 'nullable|email|unique:employees,email',
            'telepon' => 'nullable|string|max:20',
            'tanggal_masuk' => 'nullable|date_format:d/m/Y',
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
            'nama.required' => 'Nama pegawai wajib diisi',
            'nip.unique' => 'NIP sudah terdaftar',
            'posisi.required' => 'Posisi/jabatan wajib diisi',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan',
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus dd/mm/yyyy',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'tanggal_masuk.date_format' => 'Format tanggal masuk harus dd/mm/yyyy',
        ];
    }

    /**
     * Handle import errors.
     *
     * @param Throwable $e
     */
    public function onError(Throwable $e): void
    {
        Log::error('Employee import error: ' . $e->getMessage());
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
     * Check if position is a doctor.
     *
     * @param string $position
     * @return bool
     */
    private function isDoctor(string $position): bool
    {
        $doctorKeywords = ['dokter', 'doctor', 'dr.', 'dr ', 'spesialis', 'sp.', 'consultant'];
        $positionLower = strtolower($position);
        
        foreach ($doctorKeywords as $keyword) {
            if (str_contains($positionLower, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if position is a nurse.
     *
     * @param string $position
     * @return bool
     */
    private function isNurse(string $position): bool
    {
        $nurseKeywords = ['perawat', 'nurse', 'suster', 'bidan', 'midwife'];
        $positionLower = strtolower($position);
        
        foreach ($nurseKeywords as $keyword) {
            if (str_contains($positionLower, $keyword)) {
                return true;
            }
        }
        
        return false;
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
     * @param string|null $gender
     * @return string|null
     */
    private function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        $gender = strtolower(trim($gender));
        
        return match ($gender) {
            'male', 'laki-laki', 'laki laki', 'l' => 'male',
            'female', 'perempuan', 'p' => 'female',
            default => null,
        };
    }

    /**
     * Normalize employee type.
     *
     * @param string $type
     * @return string
     */
    private function normalizeEmployeeType(string $type): string
    {
        $type = strtolower(trim($type));
        
        return match ($type) {
            'tetap', 'permanent', 'permanen', 'pns' => 'tetap',
            'kontrak', 'contract', 'pkwt' => 'kontrak',
            'honorer', 'honor', 'part time' => 'honorer',
            'outsourcing', 'outsourching', 'os' => 'outsourcing',
            default => 'tetap',
        };
    }

    /**
     * Generate unique employee code.
     *
     * @return string
     */
    private function generateEmployeeCode(): string
    {
        $prefix = 'EMP';
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
