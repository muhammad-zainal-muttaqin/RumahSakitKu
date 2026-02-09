<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use App\Imports\LabTestsImport;
use App\Imports\MedicinesImport;
use App\Imports\PatientsImport;
use App\Imports\VisitsImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import Controller
 * 
 * Handles all Excel import operations for the SIMRS system.
 */
class ImportController extends Controller
{
    /**
     * Import patients from Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function patients(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new PatientsImport(
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Patient import completed',
                'data' => [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors_count' => count($stats['errors']),
                    'failures_count' => count($stats['failures']),
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Patient import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import employees from Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function employees(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new EmployeesImport(
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Employee import completed',
                'data' => [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors_count' => count($stats['errors']),
                    'failures_count' => count($stats['failures']),
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Employee import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import medicines from Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function medicines(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new MedicinesImport(
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Medicine import completed',
                'data' => [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors_count' => count($stats['errors']),
                    'failures_count' => count($stats['failures']),
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Medicine import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import lab tests from Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function labTests(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new LabTestsImport(
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Lab test import completed',
                'data' => [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors_count' => count($stats['errors']),
                    'failures_count' => count($stats['failures']),
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Lab test import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import visits from Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function visits(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'create_medical_record' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $import = new VisitsImport(
                createMedicalRecord: $request->boolean('create_medical_record', false)
            );

            Excel::import($import, $request->file('file'));

            $stats = $import->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Visit import completed',
                'data' => [
                    'imported' => $stats['imported'],
                    'skipped' => $stats['skipped'],
                    'errors_count' => count($stats['errors']),
                    'failures_count' => count($stats['failures']),
                    'errors' => $stats['errors'],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Visit import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download import template.
     *
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function downloadTemplate(Request $request, string $type): JsonResponse
    {
        $templates = [
            'patients' => [
                'columns' => [
                    'nama' => 'Nama lengkap pasien (wajib)',
                    'nik' => 'Nomor Induk Kependudukan (16 digit)',
                    'tempat_lahir' => 'Tempat lahir',
                    'tanggal_lahir' => 'Tanggal lahir (dd/mm/yyyy) (wajib)',
                    'jenis_kelamin' => 'Jenis kelamin (Laki-laki/Perempuan) (wajib)',
                    'golongan_darah' => 'Golongan darah (A/B/AB/O)',
                    'alamat' => 'Alamat lengkap',
                    'telepon' => 'Nomor telepon',
                    'email' => 'Email',
                    'kontak_darurat_nama' => 'Nama kontak darurat',
                    'kontak_darurat_telepon' => 'Telepon kontak darurat',
                    'status_perkawinan' => 'Status (single/married/divorced/widowed)',
                    'pekerjaan' => 'Pekerjaan',
                    'jenis_asuransi' => 'Jenis asuransi (bpjs/private/corporate/self_pay)',
                    'nomor_asuransi' => 'Nomor kartu asuransi',
                    'nomor_bpjs' => 'Nomor kartu BPJS',
                ],
                'example' => [
                    'nama' => 'Ahmad Sudirman',
                    'nik' => '3175081234567890',
                    'tempat_lahir' => 'Jakarta',
                    'tanggal_lahir' => '15/05/1985',
                    'jenis_kelamin' => 'Laki-laki',
                    'golongan_darah' => 'O',
                    'alamat' => 'Jl. Sudirman No. 123',
                    'telepon' => '08123456789',
                    'email' => 'ahmad@example.com',
                    'kontak_darurat_nama' => 'Siti Aminah',
                    'kontak_darurat_telepon' => '08987654321',
                    'status_perkawinan' => 'married',
                    'pekerjaan' => 'Karyawan Swasta',
                    'jenis_asuransi' => 'bpjs',
                    'nomor_asuransi' => '1234567890',
                    'nomor_bpjs' => '0001234567890',
                ],
            ],
            'employees' => [
                'columns' => [
                    'nama' => 'Nama lengkap pegawai (wajib)',
                    'nip' => 'Nomor Induk Pegawai',
                    'jenis_kelamin' => 'Jenis kelamin (Laki-laki/Perempuan)',
                    'tanggal_lahir' => 'Tanggal lahir (dd/mm/yyyy)',
                    'alamat' => 'Alamat lengkap',
                    'telepon' => 'Nomor telepon',
                    'email' => 'Email',
                    'posisi' => 'Posisi/Jabatan (wajib)',
                    'profesi' => 'Profesi',
                    'tipe_pegawai' => 'Tipe (tetap/kontrak/honorer/outsourcing)',
                    'gelar_dokter' => 'Gelar dokter (dr./drg./dr. Sp.X)',
                    'nomor_sip' => 'Nomor SIP (Surat Izin Praktik)',
                    'sip_expired' => 'Masa berlaku SIP (dd/mm/yyyy)',
                    'nomor_str' => 'Nomor STR (Surat Tanda Registrasi)',
                    'str_expired' => 'Masa berlaku STR (dd/mm/yyyy)',
                    'nomor_sertifikasi' => 'Nomor sertifikasi',
                    'tanggal_masuk' => 'Tanggal masuk kerja (dd/mm/yyyy)',
                ],
                'example' => [
                    'nama' => 'dr. Budi Santoso, Sp.PD',
                    'nip' => '198501152010011',
                    'jenis_kelamin' => 'Laki-laki',
                    'tanggal_lahir' => '15/01/1985',
                    'alamat' => 'Jl. Merdeka No. 45',
                    'telepon' => '08129876543',
                    'email' => 'budi.santoso@rs.example.com',
                    'posisi' => 'Dokter Spesialis Penyakit Dalam',
                    'profesi' => 'Dokter',
                    'tipe_pegawai' => 'tetap',
                    'gelar_dokter' => 'dr. Sp.PD',
                    'nomor_sip' => 'SIP-1234/2024',
                    'sip_expired' => '31/12/2025',
                    'nomor_str' => 'STR-5678/2024',
                    'str_expired' => '31/12/2026',
                    'nomor_sertifikasi' => 'CERT-001',
                    'tanggal_masuk' => '01/01/2020',
                ],
            ],
            'medicines' => [
                'columns' => [
                    'kode' => 'Kode obat (wajib, unik)',
                    'nama' => 'Nama obat (wajib)',
                    'klasifikasi' => 'Klasifikasi (obat_bebas/obat_bebas_terbatas/obat_keras/narkotika/psikotropik)',
                    'bentuk_sediaan' => 'Bentuk sediaan (tablet/kapsul/sirup/injeksi/salep/krim/gel/tetes)',
                    'satuan' => 'Satuan (tablet/kapsul/botol/ml/gr)',
                    'pabrik' => 'Nama pabrik/produsen',
                    'nomor_registrasi' => 'Nomor registrasi BPOM',
                    'generik' => 'Generik? (yes/no/true/false)',
                    'stok' => 'Jumlah stok',
                    'stok_minimum' => 'Stok minimum',
                    'harga_jual' => 'Harga jual',
                    'harga_beli' => 'Harga beli',
                    'tanggal_kadaluarsa' => 'Tanggal kadaluarsa (dd/mm/yyyy)',
                ],
                'example' => [
                    'kode' => 'OBT001',
                    'nama' => 'Paracetamol 500mg',
                    'klasifikasi' => 'obat_bebas',
                    'bentuk_sediaan' => 'tablet',
                    'satuan' => 'tablet',
                    'pabrik' => 'Pharos Indonesia',
                    'nomor_registrasi' => 'DBL1234567890A1',
                    'generik' => 'yes',
                    'stok' => '1000',
                    'stok_minimum' => '100',
                    'harga_jual' => '5000',
                    'harga_beli' => '2500',
                    'tanggal_kadaluarsa' => '31/12/2026',
                ],
            ],
            'lab_tests' => [
                'columns' => [
                    'kode' => 'Kode pemeriksaan (wajib, unik)',
                    'nama' => 'Nama pemeriksaan (wajib)',
                    'kategori' => 'Kategori (hematologi/kimia_darah/urinalisa/mikrobiologi/imunologi/serologi)',
                    'jenis_spesimen' => 'Jenis spesimen (darah/urine/feses/sputum/lendir/jaringan)',
                    'nilai_referensi' => 'Nilai referensi/rentang normal',
                    'satuan' => 'Satuan hasil',
                    'harga_dasar' => 'Harga dasar pemeriksaan',
                ],
                'example' => [
                    'kode' => 'LAB001',
                    'nama' => 'Hemoglobin',
                    'kategori' => 'hematologi',
                    'jenis_spesimen' => 'darah',
                    'nilai_referensi' => '12-16',
                    'satuan' => 'g/dL',
                    'harga_dasar' => '35000',
                ],
            ],
            'visits' => [
                'columns' => [
                    'no_rm' => 'Nomor Rekam Medis (wajib, harus sudah terdaftar)',
                    'tanggal' => 'Tanggal kunjungan (dd/mm/yyyy) (wajib)',
                    'tipe' => 'Tipe kunjungan (outpatient/inpatient/emergency)',
                    'jenis_registrasi' => 'Jenis registrasi (walk_in/referral/appointment)',
                    'prioritas' => 'Prioritas (normal/urgent/emergency/vip)',
                    'keluhan' => 'Keluhan utama',
                    'rujukan_dari' => 'Rujukan dari (RS/Puskesmas/Dokter)',
                    'nomor_rujukan' => 'Nomor surat rujukan',
                    'nomor_sep' => 'Nomor SEP BPJS',
                    'diagnosis' => 'Diagnosis (jika create_medical_record=true)',
                    'kode_icd10' => 'Kode ICD10',
                    'deskripsi_icd10' => 'Deskripsi ICD10',
                    'catatan' => 'Catatan kunjungan',
                    'catatan_medis' => 'Catatan medis',
                ],
                'example' => [
                    'no_rm' => 'RM2024ABC123',
                    'tanggal' => '15/02/2024',
                    'tipe' => 'outpatient',
                    'jenis_registrasi' => 'walk_in',
                    'prioritas' => 'normal',
                    'keluhan' => 'Demam dan sakit kepala',
                    'rujukan_dari' => '-',
                    'nomor_rujukan' => '-',
                    'nomor_sep' => '-',
                    'diagnosis' => 'Common Cold',
                    'kode_icd10' => 'J06.9',
                    'deskripsi_icd10' => 'Acute upper respiratory infection, unspecified',
                    'catatan' => 'Pasien datang dengan demam',
                    'catatan_medis' => 'Diberikan antipiretik',
                ],
            ],
        ];

        if (!isset($templates[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Template type not found',
                'available_types' => array_keys($templates),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $templates[$type],
        ]);
    }

    /**
     * Get available import types.
     *
     * @return JsonResponse
     */
    public function availableImports(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'key' => 'patients',
                    'name' => 'Import Pasien',
                    'description' => 'Import data pasien dari file Excel',
                    'endpoint' => '/api/imports/patients',
                    'method' => 'POST',
                    'parameters' => [
                        'file' => 'required|file|xlsx,xls,csv|max:10MB',
                        'skip_duplicates' => 'optional|boolean',
                    ],
                ],
                [
                    'key' => 'employees',
                    'name' => 'Import Pegawai',
                    'description' => 'Import data pegawai/karyawan dari file Excel',
                    'endpoint' => '/api/imports/employees',
                    'method' => 'POST',
                    'parameters' => [
                        'file' => 'required|file|xlsx,xls,csv|max:10MB',
                        'skip_duplicates' => 'optional|boolean',
                    ],
                ],
                [
                    'key' => 'medicines',
                    'name' => 'Import Obat',
                    'description' => 'Import master data obat dari file Excel',
                    'endpoint' => '/api/imports/medicines',
                    'method' => 'POST',
                    'parameters' => [
                        'file' => 'required|file|xlsx,xls,csv|max:10MB',
                        'skip_duplicates' => 'optional|boolean',
                    ],
                ],
                [
                    'key' => 'lab_tests',
                    'name' => 'Import Pemeriksaan Lab',
                    'description' => 'Import master data pemeriksaan laboratorium',
                    'endpoint' => '/api/imports/lab-tests',
                    'method' => 'POST',
                    'parameters' => [
                        'file' => 'required|file|xlsx,xls,csv|max:10MB',
                        'skip_duplicates' => 'optional|boolean',
                    ],
                ],
                [
                    'key' => 'visits',
                    'name' => 'Import Kunjungan',
                    'description' => 'Import riwayat kunjungan pasien',
                    'endpoint' => '/api/imports/visits',
                    'method' => 'POST',
                    'parameters' => [
                        'file' => 'required|file|xlsx,xls,csv|max:10MB',
                        'create_medical_record' => 'optional|boolean',
                    ],
                ],
            ],
        ]);
    }
}
