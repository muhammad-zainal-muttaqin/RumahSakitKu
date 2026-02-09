<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use Exception;
use App\Models\MasterData\Medicine;
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
 * Medicines Import Class
 * 
 * Imports medicine master data from Excel.
 */
class MedicinesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
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
     * @param bool $skipDuplicates Skip records with duplicate code
     */
    public function __construct(bool $skipDuplicates = true)
    {
        $this->skipDuplicates = $skipDuplicates;
    }

    /**
     * Create medicine model from row data.
     *
     * @param array<string, mixed> $row
     * @return Medicine|null
     */
    public function model(array $row): ?Medicine
    {
        // Check for duplicate code
        if ($this->skipDuplicates && !empty($row['kode'])) {
            $existing = Medicine::where('code', $row['kode'])->first();
            if ($existing) {
                $this->skippedCount++;
                Log::info("Medicine import: Skipped duplicate code {$row['kode']}");
                return null;
            }
        }

        $this->importedCount++;

        return new Medicine([
            'code' => $row['kode'],
            'name' => $row['nama'],
            'classification' => $this->normalizeClassification($row['klasifikasi'] ?? null),
            'dosage_form' => $this->normalizeDosageForm($row['bentuk_sediaan'] ?? null),
            'unit' => $row['satuan'] ?? 'tablet',
            'manufacturer' => $row['pabrik'] ?? $row['produsen'] ?? null,
            'registration_number' => $row['nomor_registrasi'] ?? null,
            'is_generic' => $this->parseBoolean($row['generik'] ?? 'no'),
            'stock' => $row['stok'] ?? 0,
            'min_stock' => $row['stok_minimum'] ?? 10,
            'selling_price' => $this->parsePrice($row['harga_jual'] ?? 0),
            'purchase_price' => $this->parsePrice($row['harga_beli'] ?? 0),
            'expired_date' => $this->parseDate($row['tanggal_kadaluarsa'] ?? null),
            'is_active' => true,
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
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'harga_jual' => 'nullable|numeric|min:0',
            'harga_beli' => 'nullable|numeric|min:0',
            'stok' => 'nullable|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
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
            'kode.required' => 'Kode obat wajib diisi',
            'nama.required' => 'Nama obat wajib diisi',
            'harga_jual.numeric' => 'Harga jual harus berupa angka',
            'harga_beli.numeric' => 'Harga beli harus berupa angka',
            'stok.numeric' => 'Stok harus berupa angka',
            'stok_minimum.numeric' => 'Stok minimum harus berupa angka',
        ];
    }

    /**
     * Handle import errors.
     *
     * @param Throwable $e
     */
    public function onError(Throwable $e): void
    {
        Log::error('Medicine import error: ' . $e->getMessage());
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
     * Normalize medicine classification.
     *
     * @param string|null $classification
     * @return string|null
     */
    private function normalizeClassification(?string $classification): ?string
    {
        if (empty($classification)) {
            return null;
        }

        $classification = strtolower(trim($classification));
        
        return match ($classification) {
            'obat bebas', 'bebass', 'otc' => 'obat_bebas',
            'obat bebas terbatas', 'bebas terbatas', 'obt', 'limited' => 'obat_bebas_terbatas',
            'obat keras', 'keras', 'prescription', 'rx' => 'obat_keras',
            'narkotika', 'narcotic', 'narkoba' => 'narkotika',
            'psikotropik', 'psychotropic' => 'psikotropik',
            default => $classification,
        };
    }

    /**
     * Normalize dosage form.
     *
     * @param string|null $form
     * @return string|null
     */
    private function normalizeDosageForm(?string $form): ?string
    {
        if (empty($form)) {
            return null;
        }

        $form = strtolower(trim($form));
        
        return match ($form) {
            'tablet', 'tab', 'pill' => 'tablet',
            'kapsul', 'capsule', 'cap' => 'kapsul',
            'sirup', 'syrup', 'cair' => 'sirup',
            'injeksi', 'injection', 'suntik' => 'injeksi',
            'salep', 'ointment' => 'salep',
            'krim', 'cream' => 'krim',
            'gel', 'jelly' => 'gel',
            'tetes', 'drops' => 'tetes',
            'inhaler', 'uap' => 'inhaler',
            'supositoria', 'suppository' => 'supositoria',
            'suspensi', 'suspension' => 'suspensi',
            'serbuk', 'powder' => 'serbuk',
            'patch', 'plester' => 'patch',
            default => $form,
        };
    }

    /**
     * Parse boolean value.
     *
     * @param string|bool|null $value
     * @return bool
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['yes', 'ya', '1', 'true', 'generik', 'generic'], true);
        }

        return false;
    }

    /**
     * Parse price value.
     *
     * @param string|float|null $price
     * @return float
     */
    private function parsePrice($price): float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        if (is_string($price)) {
            // Remove currency symbols and thousand separators
            $cleaned = preg_replace('/[^0-9.]/', '', $price);
            return (float) $cleaned;
        }

        return 0.0;
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
