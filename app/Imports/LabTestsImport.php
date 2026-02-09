<?php

declare(strict_types=1);

namespace App\Imports;

use Throwable;
use App\Models\MasterData\LabTest;
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
 * Lab Tests Import Class
 * 
 * Imports laboratory test master data from Excel.
 */
class LabTestsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithChunkReading, WithBatchInserts
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
     * Create lab test model from row data.
     *
     * @param array<string, mixed> $row
     * @return LabTest|null
     */
    public function model(array $row): ?LabTest
    {
        // Check for duplicate code
        if ($this->skipDuplicates && !empty($row['kode'])) {
            $existing = LabTest::where('test_code', $row['kode'])->first();
            if ($existing) {
                $this->skippedCount++;
                Log::info("Lab test import: Skipped duplicate code {$row['kode']}");
                return null;
            }
        }

        $this->importedCount++;

        return new LabTest([
            'test_code' => $row['kode'],
            'name' => $row['nama'],
            'category' => $this->normalizeCategory($row['kategori'] ?? 'lainnya'),
            'specimen_type' => $this->normalizeSpecimenType($row['jenis_spesimen'] ?? 'darah'),
            'reference_value' => $row['nilai_referensi'] ?? $row['rentang_normal'] ?? null,
            'unit' => $row['satuan'] ?? null,
            'base_price' => $this->parsePrice($row['harga_dasar'] ?? $row['harga'] ?? 0),
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
            'kategori' => 'nullable|string|max:100',
            'jenis_spesimen' => 'nullable|string|max:50',
            'satuan' => 'nullable|string|max:50',
            'harga_dasar' => 'nullable|numeric|min:0',
            'harga' => 'nullable|numeric|min:0',
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
            'kode.required' => 'Kode pemeriksaan wajib diisi',
            'nama.required' => 'Nama pemeriksaan wajib diisi',
            'harga_dasar.numeric' => 'Harga dasar harus berupa angka',
            'harga.numeric' => 'Harga harus berupa angka',
        ];
    }

    /**
     * Handle import errors.
     *
     * @param Throwable $e
     */
    public function onError(Throwable $e): void
    {
        Log::error('Lab test import error: ' . $e->getMessage());
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
     * Normalize category value.
     *
     * @param string $category
     * @return string
     */
    private function normalizeCategory(string $category): string
    {
        $category = strtolower(trim($category));
        
        return match ($category) {
            'hematologi', 'hema', 'blood', 'darah lengkap' => 'hematologi',
            'kimia darah', 'kimia', 'chemistry', 'kimiadarah' => 'kimia_darah',
            'urinalisa', 'urine', 'urin' => 'urinalisa',
            'mikrobiologi', 'mikro', 'microbiology' => 'mikrobiologi',
            'imunologi', 'imuno', 'immunology' => 'imunologi',
            'serologi', 'serology' => 'serologi',
            'endokrinologi', 'endocrine', 'hormon' => 'endokrinologi',
            'tumor marker', 'tumor', 'oncomarker' => 'tumor_marker',
            'elektrolit', 'electrolyte', 'elektrolit' => 'elektrolit',
            'gula darah', 'glucose', 'glukosa' => 'gula_darah',
            'fungsi ginjal', 'renal', 'kidney' => 'fungsi_ginjal',
            'fungsi hati', 'liver', 'hepatik' => 'fungsi_hati',
            'lemak darah', 'lipid', 'lipi' => 'lemak_darah',
            'koagulasi', 'coagulation', 'koagulasi' => 'koagulasi',
            'gas darah', 'blood gas', 'gasa' => 'gas_darah',
            'sitologi', 'cytology' => 'sitologi',
            'patologi anatomi', 'patologi', 'anap' => 'patologi_anatomi',
            'molekuler', 'molecular', 'pcr' => 'molekuler',
            default => 'lainnya',
        };
    }

    /**
     * Normalize specimen type.
     *
     * @param string $type
     * @return string
     */
    private function normalizeSpecimenType(string $type): string
    {
        $type = strtolower(trim($type));
        
        return match ($type) {
            'darah', 'blood', 'serum', 'plasma' => 'darah',
            'urine', 'urin', 'air seni' => 'urine',
            'feses', 'feces', 'tinja', 'stool' => 'feses',
            'sputum', 'dahak' => 'sputum',
            'lendir', 'mucus' => 'lendir',
            'jaringan', 'tissue', 'biopsi' => 'jaringan',
            'cairan serebrospinal', 'csf', 'serebrospinal' => 'cairan_serebrospinal',
            'cairan sendi', 'sendi', 'joint fluid' => 'cairan_sendi',
            'cairan pleura', 'pleura', 'pleural fluid' => 'cairan_pleura',
            'swab', 'swap' => 'swab',
            default => 'lainnya',
        };
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
