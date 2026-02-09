<?php

declare(strict_types=1);

namespace App\Services\BPJS;

use RuntimeException;
use Exception;

/**
 * BPJS E-Klaim Service
 * 
 * Service for BPJS E-Klaim (Electronic Claim) API integration.
 * 
 * Handles integration with BPJS E-Klaim (Electronic Claim) API for:
 * - New claim creation
 * - Claim data management
 * - Grouping (Grouper) stage 1 and 2
 * - Claim finalization
 * - Claim printing
 */
class BpjsEklaimService extends BpjsService
{
    protected string $serviceName = 'eklaim';

    protected function initializeConfig(): void
    {
        $this->baseUrl = config('bpjs.eklaim.base_url', 'https://apijkn.bpjs-kesehatan.go.id/eklaim');
        $this->consId = config('bpjs.eklaim.cons_id', '');
        $this->secretKey = config('bpjs.eklaim.secret_key', '');
        $this->userKey = config('bpjs.eklaim.user_key', '');
    }

    /**
     * Generate E-Klaim specific signature.
     * E-Klaim uses a different signature format than VClaim.
     */
    public function generateSignature(string $timestamp): string
    {
        $data = $this->consId . $this->secretKey . $timestamp;

        return hash('sha256', $data);
    }

    /**
     * Get E-Klaim specific headers.
     */
    public function getHeaders(string $timestamp, string $signature): array
    {
        $auth = base64_encode("{$this->consId}:{$this->userKey}");

        return [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $timestamp,
            'X-signature' => $signature,
            'X-authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Make E-Klaim request with proper formatting.
     */
    protected function makeEklaimRequest(string $action, array $data): array
    {
        $metadata = [
            'method' => $action,
            'tgl_claim' => date('Y-m-d H:i:s'),
        ];

        $requestData = [
            'metadata' => $metadata,
            'data' => $this->encryptRequest($data),
        ];

        return $this->request(
            endpoint: '',
            method: 'POST',
            data: $requestData
        );
    }

    /**
     * Encrypt request data for E-Klaim.
     */
    protected function encryptRequest(array $data): string
    {
        $jsonData = json_encode($data);
        $key = $this->consId . $this->secretKey . time();
        $key = substr(hash('sha256', $key), 0, 32);

        $iv = str_repeat("\0", 16);
        $encrypted = openssl_encrypt($jsonData, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Failed to encrypt E-Klaim request data');
        }

        $compressed = gzencode($encrypted);

        return base64_encode($compressed);
    }

    /**
     * Override decryptResponse for E-Klaim specific format.
     */
    public function decryptResponse(array|string $response): array|string|null
    {
        if (is_array($response) && isset($response['data'])) {
            $encryptedData = $response['data'];
        } elseif (is_string($response)) {
            $encryptedData = $response;
        } else {
            return $response;
        }

        try {
            $compressed = base64_decode($encryptedData, true);
            if ($compressed === false) {
                return $response;
            }

            $encrypted = gzdecode($compressed);
            if ($encrypted === false) {
                return $response;
            }

            $key = $this->consId . $this->secretKey . time();
            $key = substr(hash('sha256', $key), 0, 32);

            $iv = str_repeat("\0", 16);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            if ($decrypted === false) {
                return $response;
            }

            $decoded = json_decode($decrypted, true);

            return $decoded ?? $decrypted;
        } catch (Exception $e) {
            return $response;
        }
    }

    // ==================== CLAIM MANAGEMENT METHODS ====================

    /**
     * Create new claim.
     *
     * Required data fields:
     * - nomor_kartu: BPJS card number
     * - nomor_sep: SEP number
     * - nomor_rm: Medical record number
     * - nama_pasien: Patient name
     * - tgl_lahir: Birth date (YYYY-MM-DD)
     * - gender: Gender (1=Laki-laki, 2=Perempuan)
     *
     * @param array $data Claim initialization data
     * @return array Response with claim status
     */
    public function newClaim(array $data): array
    {
        $claimData = [
            'nomor_kartu' => $data['nomor_kartu'],
            'nomor_sep' => $data['nomor_sep'],
            'nomor_rm' => $data['nomor_rm'],
            'nama_pasien' => $data['nama_pasien'],
            'tgl_lahir' => $data['tgl_lahir'],
            'gender' => $data['gender'],
        ];

        return $this->makeEklaimRequest('new_claim', $claimData);
    }

    /**
     * Set claim data (update claim with complete information).
     *
     * Required data fields:
     * - nomor_sep: SEP number
     * - nomor_kartu: BPJS card number
     * - tgl_masuk: Admission date (YYYY-MM-DD HH:MM:SS)
     * - tgl_pulang: Discharge date (YYYY-MM-DD HH:MM:SS)
     * - jenis_rawat: Care type (1=Rawat Inap, 2=Rawat Jalan, 3=Rawat Darurat)
     * - kelas_rawat: Class (1=Kelas 1, 2=Kelas 2, 3=Kelas 3)
     * - adl_sub_acute: ADL sub acute score
     * - adl_chronic: ADL chronic score
     * - icu_indikator: ICU indicator (0/1)
     * - icu_los: ICU length of stay
     * - ventilator_hour: Ventilator hours
     * - upgrade_class_ind: Upgrade class indicator
     * - upgrade_class_class: Upgraded class
     * - upgrade_class_los: Length of stay in upgraded class
     * - add_payment_pct: Additional payment percentage
     * - birth_weight: Birth weight (grams, for neonates)
     * - discharge_status: Discharge status code
     * - diagnosa: Primary diagnosis (ICD-10)
     * - procedure: Procedures (ICD-9-CM, pipe-separated)
     * - tarif_poli_eks: Polyclinic executive tariff
     * - nama_dokter: Doctor name
     * - kode_tarif: Tariff code (CS, RS, etc.)
     * - payor_id: Payor ID (3 for JKN)
     * - payor_cd: Payor code
     * - cob_cd: COB code
     * - coder_nik: Coder's NIK
     *
     * @param array $data Complete claim data
     * @return array Response with claim update status
     */
    public function setClaimData(array $data): array
    {
        $claimData = [
            'nomor_sep' => $data['nomor_sep'],
            'nomor_kartu' => $data['nomor_kartu'] ?? '',
            'tgl_masuk' => $data['tgl_masuk'],
            'tgl_pulang' => $data['tgl_pulang'],
            'jenis_rawat' => $data['jenis_rawat'],
            'kelas_rawat' => $data['kelas_rawat'],
            'adl_sub_acute' => $data['adl_sub_acute'] ?? '0',
            'adl_chronic' => $data['adl_chronic'] ?? '0',
            'icu_indikator' => $data['icu_indikator'] ?? '0',
            'icu_los' => $data['icu_los'] ?? '0',
            'ventilator_hour' => $data['ventilator_hour'] ?? '0',
            'upgrade_class_ind' => $data['upgrade_class_ind'] ?? '0',
            'upgrade_class_class' => $data['upgrade_class_class'] ?? '',
            'upgrade_class_los' => $data['upgrade_class_los'] ?? '0',
            'add_payment_pct' => $data['add_payment_pct'] ?? '0',
            'birth_weight' => $data['birth_weight'] ?? '0',
            'discharge_status' => $data['discharge_status'],
            'diagnosa' => $data['diagnosa'],
            'procedure' => $data['procedure'] ?? '',
            'tarif_poli_eks' => $data['tarif_poli_eks'] ?? '0',
            'nama_dokter' => $data['nama_dokter'],
            'kode_tarif' => $data['kode_tarif'] ?? 'CS',
            'payor_id' => $data['payor_id'] ?? '3',
            'payor_cd' => $data['payor_cd'] ?? 'JKN',
            'cob_cd' => $data['cob_cd'] ?? '',
            'coder_nik' => $data['coder_nik'],
        ];

        // Add optional fields if provided
        if (isset($data['sistole'])) {
            $claimData['sistole'] = $data['sistole'];
        }
        if (isset($data['diastole'])) {
            $claimData['diastole'] = $data['diastole'];
        }

        return $this->makeEklaimRequest('set_claim_data', $claimData);
    }

    /**
     * Get claim data.
     *
     * @param string $sep SEP number
     * @return array Response with claim data
     */
    public function getClaimData(string $sep): array
    {
        return $this->makeEklaimRequest('get_claim_data', [
            'nomor_sep' => $sep,
        ]);
    }

    // ==================== GROUPING METHODS ====================

    /**
     * Run grouping stage 1 (initial grouper calculation).
     *
     * @param string $sep SEP number
     * @return array Response with grouping results
     */
    public function groupingStage1(string $sep): array
    {
        return $this->makeEklaimRequest('grouper', [
            'nomor_sep' => $sep,
        ]);
    }

    /**
     * Run grouping stage 2 (special consideration).
     *
     * This is used when there are special considerations or
     * when the initial grouping needs refinement.
     *
     * @param string $sep SEP number
     * @param array $specialCmg Special CMG data (optional)
     * @return array Response with refined grouping results
     */
    public function groupingStage2(string $sep, array $specialCmg = []): array
    {
        $data = [
            'nomor_sep' => $sep,
        ];

        // Add special CMG codes if provided
        if (! empty($specialCmg)) {
            $data['special_cmg'] = $specialCmg;
        }

        return $this->makeEklaimRequest('grouper2', $data);
    }

    // ==================== CLAIM FINALIZATION METHODS ====================

    /**
     * Finalize claim.
     *
     * @param string $sep SEP number
     * @return array Response with finalization status
     */
    public function finalClaim(string $sep): array
    {
        return $this->makeEklaimRequest('claim_final', [
            'nomor_sep' => $sep,
        ]);
    }

    /**
     * Re-edit claim (unfinalize for editing).
     *
     * @param string $sep SEP number
     * @return array Response with re-edit status
     */
    public function reeditClaim(string $sep): array
    {
        return $this->makeEklaimRequest('claim_edit', [
            'nomor_sep' => $sep,
        ]);
    }

    /**
     * Delete claim.
     *
     * @param string $sep SEP number
     * @return array Response with deletion status
     */
    public function deleteClaim(string $sep): array
    {
        return $this->makeEklaimRequest('delete_claim', [
            'nomor_sep' => $sep,
        ]);
    }

    // ==================== PRINT METHODS ====================

    /**
     * Get claim print data.
     *
     * @param string $sep SEP number
     * @return array Response with print data (usually HTML/PDF)
     */
    public function printClaim(string $sep): array
    {
        return $this->makeEklaimRequest('claim_print', [
            'nomor_sep' => $sep,
        ]);
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Validate claim data before submission.
     *
     * @param array $data Claim data to validate
     * @return array Validation result with errors if any
     */
    public function validateClaimData(array $data): array
    {
        $errors = [];
        $required = [
            'nomor_sep',
            'tgl_masuk',
            'tgl_pulang',
            'jenis_rawat',
            'kelas_rawat',
            'discharge_status',
            'diagnosa',
            'nama_dokter',
            'coder_nik',
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        // Validate dates
        if (! empty($data['tgl_masuk'])) {
            $masuk = strtotime($data['tgl_masuk']);
            if ($masuk === false) {
                $errors[] = "Invalid admission date format";
            }
        }

        if (! empty($data['tgl_pulang'])) {
            $pulang = strtotime($data['tgl_pulang']);
            if ($pulang === false) {
                $errors[] = "Invalid discharge date format";
            }
        }

        // Validate jenis_rawat
        if (! empty($data['jenis_rawat']) && ! in_array($data['jenis_rawat'], ['1', '2', '3'])) {
            $errors[] = "Invalid care type (jenis_rawat). Must be 1, 2, or 3";
        }

        // Validate kelas_rawat
        if (! empty($data['kelas_rawat']) && ! in_array($data['kelas_rawat'], ['1', '2', '3'])) {
            $errors[] = "Invalid class (kelas_rawat). Must be 1, 2, or 3";
        }

        // Validate gender if provided
        if (! empty($data['gender']) && ! in_array($data['gender'], ['1', '2'])) {
            $errors[] = "Invalid gender. Must be 1 (Male) or 2 (Female)";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get claim status.
     *
     * @param string $sep SEP number
     * @return array Response with claim status
     */
    public function getClaimStatus(string $sep): array
    {
        $response = $this->getClaimData($sep);

        if (! $response['success']) {
            return $response;
        }

        $data = $response['data'] ?? [];

        return [
            'success' => true,
            'code' => '200',
            'message' => 'Claim status retrieved',
            'data' => [
                'nomor_sep' => $sep,
                'status' => $data['status'] ?? 'UNKNOWN',
                'grouping_status' => $data['grouping_status'] ?? null,
                'final_status' => $data['final_status'] ?? null,
                'coder_nik' => $data['coder_nik'] ?? null,
                'grouper_response' => $data['grouper'] ?? null,
            ],
        ];
    }

    /**
     * Submit complete claim workflow.
     * This method handles the entire claim submission process:
     * 1. Create new claim
     * 2. Set claim data
     * 3. Run grouping stage 1
     * 4. Finalize claim
     *
     * @param array $data Complete claim data
     * @return array Response with submission results
     */
    public function submitCompleteClaim(array $data): array
    {
        // Step 1: Validate data
        $validation = $this->validateClaimData($data);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'Claim data validation failed',
                'data' => ['errors' => $validation['errors']],
            ];
        }

        // Step 2: Create new claim
        $newClaimResponse = $this->newClaim([
            'nomor_kartu' => $data['nomor_kartu'],
            'nomor_sep' => $data['nomor_sep'],
            'nomor_rm' => $data['nomor_rm'],
            'nama_pasien' => $data['nama_pasien'],
            'tgl_lahir' => $data['tgl_lahir'],
            'gender' => $data['gender'],
        ]);

        if (! $newClaimResponse['success']) {
            return [
                'success' => false,
                'code' => 'NEW_CLAIM_FAILED',
                'message' => 'Failed to create new claim',
                'data' => $newClaimResponse,
            ];
        }

        // Step 3: Set claim data
        $setDataResponse = $this->setClaimData($data);

        if (! $setDataResponse['success']) {
            return [
                'success' => false,
                'code' => 'SET_DATA_FAILED',
                'message' => 'Failed to set claim data',
                'data' => $setDataResponse,
            ];
        }

        // Step 4: Run grouping
        $groupingResponse = $this->groupingStage1($data['nomor_sep']);

        if (! $groupingResponse['success']) {
            return [
                'success' => false,
                'code' => 'GROUPING_FAILED',
                'message' => 'Grouping stage 1 failed',
                'data' => $groupingResponse,
            ];
        }

        // Step 5: Finalize claim
        $finalResponse = $this->finalClaim($data['nomor_sep']);

        return [
            'success' => $finalResponse['success'],
            'code' => $finalResponse['code'],
            'message' => $finalResponse['success']
                ? 'Claim submitted successfully'
                : 'Claim submission failed at finalization',
            'data' => [
                'new_claim' => $newClaimResponse,
                'set_data' => $setDataResponse,
                'grouping' => $groupingResponse,
                'finalization' => $finalResponse,
            ],
        ];
    }
}
