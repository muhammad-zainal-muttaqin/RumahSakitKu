<?php

declare(strict_types=1);

namespace App\Services\BPJS;

use DateTimeInterface;

/**
 * BPJS PCare Service
 * 
 * Service for BPJS PCare (Primary Care) API integration.
 * 
 * Handles integration with BPJS PCare API for:
 * - Participant verification
 * - Provider queries
 * - Visit registration (Kunjungan)
 * - Visit management
 */
class BpjsPcareService extends BpjsService
{
    protected string $serviceName = 'pcare';

    protected string $pcareUser;

    protected string $pcarePassword;

    protected string $kdAplikasi;

    protected function initializeConfig(): void
    {
        $this->baseUrl = config('bpjs.pcare.base_url', 'https://apijkn.bpjs-kesehatan.go.id/pcare-rest');
        $this->consId = config('bpjs.pcare.cons_id', '');
        $this->secretKey = config('bpjs.pcare.secret_key', '');
        $this->userKey = config('bpjs.pcare.user_key', '');
        $this->pcareUser = config('bpjs.pcare.pcare_user', '');
        $this->pcarePassword = config('bpjs.pcare.pcare_password', '');
        $this->kdAplikasi = config('bpjs.pcare.kd_aplikasi', '095');
    }

    /**
     * Get PCare specific headers.
     * PCare requires additional authorization header.
     */
    public function getHeaders(string $timestamp, string $signature): array
    {
        $headers = parent::getHeaders($timestamp, $signature);
        $headers['X-authorization'] = 'Basic ' . base64_encode("{$this->pcareUser}:{$this->pcarePassword}");

        return $headers;
    }

    /**
     * Format date to PCare standard (dd-MM-yyyy).
     */
    protected function formatDatePcare(string|DateTimeInterface $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('d-m-Y');
        }

        return date('d-m-Y', strtotime($date));
    }

    // ==================== PESERTA (PARTICIPANT) METHODS ====================

    /**
     * Get PCare participant by card number.
     *
     * @param string $nomorKartu BPJS card number
     * @return array Response with participant data
     */
    public function getPeserta(string $nomorKartu): array
    {
        return $this->request(
            endpoint: "peserta/{$nomorKartu}",
            method: 'GET'
        );
    }

    /**
     * Get participant by NIK.
     *
     * @param string $nik National ID number
     * @return array Response with participant data
     */
    public function getPesertaByNik(string $nik): array
    {
        return $this->request(
            endpoint: "peserta/nik/{$nik}",
            method: 'GET'
        );
    }

    /**
     * Get participant by name and birth date.
     *
     * @param string $nama Patient name
     * @param string|DateTimeInterface $tglLahir Birth date
     * @return array Response with participant data
     */
    public function getPesertaByNameAndBirthdate(string $nama, string|DateTimeInterface $tglLahir): array
    {
        $formattedDate = $this->formatDatePcare($tglLahir);

        return $this->request(
            endpoint: "peserta/nama/{$nama}/tglLahir/{$formattedDate}",
            method: 'GET'
        );
    }

    // ==================== PROVIDER METHODS ====================

    /**
     * Get healthcare providers (faskes).
     *
     * @param int $start Start index (0-based)
     * @param int $limit Number of records to retrieve
     * @return array Response with provider list
     */
    public function getProvider(int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "provider/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get provider by code.
     *
     * @param string $kdProvider Provider code
     * @return array Response with provider data
     */
    public function getProviderByCode(string $kdProvider): array
    {
        return $this->request(
            endpoint: "provider/{$kdProvider}",
            method: 'GET'
        );
    }

    // ==================== KUNJUNGAN (VISIT) METHODS ====================

    /**
     * Register new PCare visit.
     *
     * Required data fields:
     * - noKartu: BPJS card number
     * - tglDaftar: Registration date (dd-MM-yyyy)
     * - kdPoli: Polyclinic code
     * - keluhan: Chief complaint
     * - kunjSakit: Visit type (true=sick visit, false=healthy visit)
     * - sistole: Systolic blood pressure
     * - diastole: Diastolic blood pressure
     * - beratBadan: Weight in kg
     * - tinggiBadan: Height in cm
     * - heartRate: Heart rate
     * - respRate: Respiratory rate
     * - lingkarPerut: Abdominal circumference
     * - rujukInternal: Internal referral indicator
     * - tempatRujukan: Referral destination
     * - icd: ICD-10 diagnosis code
     * - anamnesa: Anamnesis/History taking
     * - pemeriksaanFisik: Physical examination
     * - terapi: Therapy/Treatment
     * - statusPulang: Discharge status (1=Recovery, 2=Referral, etc.)
     * - tglPulang: Discharge date
     * - dokter: Doctor code
     *
     * @param array $data Visit data
     * @return array Response with visit registration result
     */
    public function postKunjungan(array $data): array
    {
        $visitData = [
            'noKartu' => $data['noKartu'],
            'tglDaftar' => $data['tglDaftar'],
            'kdPoli' => $data['kdPoli'],
            'keluhan' => $data['keluhan'] ?? '',
            'kunjSakit' => $data['kunjSakit'] ?? true,
            'sistole' => $data['sistole'] ?? 0,
            'diastole' => $data['diastole'] ?? 0,
            'beratBadan' => $data['beratBadan'] ?? 0,
            'tinggiBadan' => $data['tinggiBadan'] ?? 0,
            'heartRate' => $data['heartRate'] ?? 0,
            'respRate' => $data['respRate'] ?? 0,
            'lingkarPerut' => $data['lingkarPerut'] ?? 0,
            'rujukInternal' => $data['rujukInternal'] ?? false,
            'tempatRujukan' => $data['tempatRujukan'] ?? '',
            'icd' => [
                'kdDiag' => $data['icd']['kdDiag'] ?? $data['kdDiag'] ?? '',
                'nmDiag' => $data['icd']['nmDiag'] ?? $data['nmDiag'] ?? '',
            ],
            'anamnesa' => $data['anamnesa'] ?? '',
            'pemeriksaanFisik' => $data['pemeriksaanFisik'] ?? '',
            'terapi' => $data['terapi'] ?? '',
            'statusPulang' => $data['statusPulang'] ?? '1',
            'tglPulang' => $data['tglPulang'] ?? $data['tglDaftar'],
            'dokter' => [
                'kdDokter' => $data['dokter']['kdDokter'] ?? $data['kdDokter'] ?? '',
                'nmDokter' => $data['dokter']['nmDokter'] ?? $data['nmDokter'] ?? '',
            ],
        ];

        // Add secondary diagnoses if provided
        if (! empty($data['icd2'])) {
            $visitData['icd2'] = [];
            foreach ($data['icd2'] as $icd) {
                $visitData['icd2'][] = [
                    'kdDiag' => $icd['kdDiag'] ?? '',
                    'nmDiag' => $icd['nmDiag'] ?? '',
                ];
            }
        }

        // Add procedures if provided
        if (! empty($data['icd9'])) {
            $visitData['icd9'] = [];
            foreach ($data['icd9'] as $icd9) {
                $visitData['icd9'][] = [
                    'kdProcedures' => $icd9['kdProcedures'] ?? '',
                    'nmProcedures' => $icd9['nmProcedures'] ?? '',
                ];
            }
        }

        return $this->request(
            endpoint: 'kunjungan',
            method: 'POST',
            data: $visitData
        );
    }

    /**
     * Update PCare visit.
     *
     * @param array $data Visit data with noKunjungan
     * @return array Response with update result
     */
    public function putKunjungan(array $data): array
    {
        if (empty($data['noKunjungan'])) {
            return [
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => 'noKunjungan is required for update',
                'data' => null,
            ];
        }

        $visitData = [
            'noKunjungan' => $data['noKunjungan'],
            'noKartu' => $data['noKartu'],
            'tglDaftar' => $data['tglDaftar'],
            'kdPoli' => $data['kdPoli'],
            'keluhan' => $data['keluhan'] ?? '',
            'kunjSakit' => $data['kunjSakit'] ?? true,
            'sistole' => $data['sistole'] ?? 0,
            'diastole' => $data['diastole'] ?? 0,
            'beratBadan' => $data['beratBadan'] ?? 0,
            'tinggiBadan' => $data['tinggiBadan'] ?? 0,
            'heartRate' => $data['heartRate'] ?? 0,
            'respRate' => $data['respRate'] ?? 0,
            'lingkarPerut' => $data['lingkarPerut'] ?? 0,
            'rujukInternal' => $data['rujukInternal'] ?? false,
            'tempatRujukan' => $data['tempatRujukan'] ?? '',
            'icd' => [
                'kdDiag' => $data['icd']['kdDiag'] ?? $data['kdDiag'] ?? '',
                'nmDiag' => $data['icd']['nmDiag'] ?? $data['nmDiag'] ?? '',
            ],
            'anamnesa' => $data['anamnesa'] ?? '',
            'pemeriksaanFisik' => $data['pemeriksaanFisik'] ?? '',
            'terapi' => $data['terapi'] ?? '',
            'statusPulang' => $data['statusPulang'] ?? '1',
            'tglPulang' => $data['tglPulang'] ?? $data['tglDaftar'],
            'dokter' => [
                'kdDokter' => $data['dokter']['kdDokter'] ?? $data['kdDokter'] ?? '',
                'nmDokter' => $data['dokter']['nmDokter'] ?? $data['nmDokter'] ?? '',
            ],
        ];

        // Add secondary diagnoses if provided
        if (! empty($data['icd2'])) {
            $visitData['icd2'] = [];
            foreach ($data['icd2'] as $icd) {
                $visitData['icd2'][] = [
                    'kdDiag' => $icd['kdDiag'] ?? '',
                    'nmDiag' => $icd['nmDiag'] ?? '',
                ];
            }
        }

        // Add procedures if provided
        if (! empty($data['icd9'])) {
            $visitData['icd9'] = [];
            foreach ($data['icd9'] as $icd9) {
                $visitData['icd9'][] = [
                    'kdProcedures' => $icd9['kdProcedures'] ?? '',
                    'nmProcedures' => $icd9['nmProcedures'] ?? '',
                ];
            }
        }

        return $this->request(
            endpoint: 'kunjungan',
            method: 'PUT',
            data: $visitData
        );
    }

    /**
     * Delete PCare visit.
     *
     * @param string $noKunjungan Visit number
     * @param string|null $user User performing deletion
     * @return array Response with deletion result
     */
    public function deleteKunjungan(string $noKunjungan, ?string $user = null): array
    {
        return $this->request(
            endpoint: "kunjungan/{$noKunjungan}/{$user}",
            method: 'DELETE'
        );
    }

    /**
     * Get visit by visit number.
     *
     * @param string $noKunjungan Visit number
     * @return array Response with visit data
     */
    public function getKunjungan(string $noKunjungan): array
    {
        return $this->request(
            endpoint: "kunjungan/{$noKunjungan}",
            method: 'GET'
        );
    }

    /**
     * Get visits by card number.
     *
     * @param string $nomorKartu BPJS card number
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with visit list
     */
    public function getKunjunganByKartu(string $nomorKartu, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "kunjungan/peserta/{$nomorKartu}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    // ==================== REFERENCE DATA METHODS ====================

    /**
     * Get diagnosis reference.
     *
     * @param string $keyword Search keyword (diagnosis name or code)
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with diagnosis list
     */
    public function getDiagnosa(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "diagnosa/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get procedure reference (ICD-9).
     *
     * @param string $keyword Search keyword
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with procedure list
     */
    public function getProcedures(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "procedure/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get polyclinic reference.
     *
     * @param string $keyword Search keyword
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with polyclinic list
     */
    public function getPoli(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "poli/fktp/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get doctor reference.
     *
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with doctor list
     */
    public function getDokter(int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "dokter/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get doctor by NIP.
     *
     * @param string $nip Doctor NIP
     * @return array Response with doctor data
     */
    public function getDokterByNip(string $nip): array
    {
        return $this->request(
            endpoint: "dokter/{$nip}",
            method: 'GET'
        );
    }

    /**
     * Get Kelompok Cloning (KC) reference.
     *
     * @param string $kdPoli Polyclinic code
     * @return array Response with KC list
     */
    public function getKelompokCloning(string $kdPoli): array
    {
        return $this->request(
            endpoint: "kelompokcloning/{$kdPoli}",
            method: 'GET'
        );
    }

    /**
     * Get Kelompok Cloning list.
     *
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with KC list
     */
    public function getKelompokCloningList(int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "kelompokcloning/list/{$start}/{$limit}",
            method: 'GET'
        );
    }

    // ==================== STATUS PULANG (DISCHARGE STATUS) METHODS ====================

    /**
     * Get discharge status reference.
     *
     * @param bool $rawatInap Inpatient flag
     * @return array Response with discharge status list
     */
    public function getStatusPulang(bool $rawatInap = false): array
    {
        $flag = $rawatInap ? 'true' : 'false';

        return $this->request(
            endpoint: "statuspulang/rawatInap/{$flag}",
            method: 'GET'
        );
    }

    // ==================== KESETARAAN (EQUIVALENCY) METHODS ====================

    /**
     * Get kesetaraan RTP (Rujukan Tingkat Pertama).
     *
     * @param string $param1 Parameter 1
     * @param string $param2 Parameter 2
     * @return array Response with kesetaraan data
     */
    public function getKesetaraanRtp(string $param1, string $param2): array
    {
        return $this->request(
            endpoint: "kesetaraan/rtp/{$param1}/{$param2}",
            method: 'GET'
        );
    }

    /**
     * Get kesetaraan RSB (Rujukan Selain BPJS).
     *
     * @param string $param1 Parameter 1
     * @param string $param2 Parameter 2
     * @return array Response with kesetaraan data
     */
    public function getKesetaraanRsb(string $param1, string $param2): array
    {
        return $this->request(
            endpoint: "kesetaraan/rsb/{$param1}/{$param2}",
            method: 'GET'
        );
    }

    // ==================== CLUB METHODS ====================

    /**
     * Get club (Prolanis/Prolanis) reference.
     *
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with club list
     */
    public function getClub(int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "club/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get club by code.
     *
     * @param string $kdClub Club code
     * @return array Response with club data
     */
    public function getClubByCode(string $kdClub): array
    {
        return $this->request(
            endpoint: "club/{$kdClub}",
            method: 'GET'
        );
    }

    // ==================== KEGIATAN (ACTIVITY) METHODS ====================
    /**
     * Get kegiatan Kelompok (Group Activity) list.
     *
     * @param string|DateTimeInterface $tglAwal Start date
     * @param string|DateTimeInterface $tglAkhir End date
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with activity list
     */
    public function getKegiatanKelompok(
        string|DateTimeInterface $tglAwal,
        string|DateTimeInterface $tglAkhir,
        int $start = 0,
        int $limit = 10
    ): array {
        $formattedStart = $this->formatDatePcare($tglAwal);
        $formattedEnd = $this->formatDatePcare($tglAkhir);

        return $this->request(
            endpoint: "kegiatan/kelompok/{$formattedStart}/{$formattedEnd}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get peserta kegiatan Kelompok.
     *
     * @param string $eduId Education/Activity ID
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with participant list
     */
    public function getPesertaKegiatanKelompok(string $eduId, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "kegiatan/kelompok/peserta/{$eduId}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    // ==================== TINDAKAN (ACTION) METHODS ====================

    /**
     * Get tindakan reference.
     *
     * @param string $keyword Search keyword
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with tindakan list
     */
    public function getTindakan(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "tindakan/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get tindakan by code.
     *
     * @param string $kdTindakan Tindakan code
     * @return array Response with tindakan data
     */
    public function getTindakanByCode(string $kdTindakan): array
    {
        return $this->request(
            endpoint: "tindakan/{$kdTindakan}",
            method: 'GET'
        );
    }

    // ==================== OBAT (MEDICINE) METHODS ====================

    /**
     * Get obat reference.
     *
     * @param string $keyword Search keyword
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with obat list
     */
    public function getObat(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "obat/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get obat by code.
     *
     * @param string $kdObat Obat code
     * @return array Response with obat data
     */
    public function getObatByCode(string $kdObat): array
    {
        return $this->request(
            endpoint: "obat/{$kdObat}",
            method: 'GET'
        );
    }

    // ==================== SPESIALIST METHODS ====================

    /**
     * Get spesialis reference.
     *
     * @param string $keyword Search keyword
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with spesialis list
     */
    public function getSpesialis(string $keyword, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "spesialis/{$keyword}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get subspesialis by spesialis code.
     *
     * @param string $kdSpesialis Spesialis code
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with subspesialis list
     */
    public function getSubspesialis(string $kdSpesialis, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "spesialis/{$kdSpesialis}/subspesialis/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get sarana by spesialis code.
     *
     * @param string $kdSpesialis Spesialis code
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with sarana list
     */
    public function getSarana(string $kdSpesialis, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "spesialis/{$kdSpesialis}/sarana/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get faskes rujukan by spesialis code.
     *
     * @param string $kdSpesialis Spesialis code
     * @param string|DateTimeInterface $tglEstRujuk Estimated referral date
     * @param string $kdSarana Sarana code
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with faskes list
     */
    public function getFaskesRujukan(
        string $kdSpesialis,
        string|DateTimeInterface $tglEstRujuk,
        string $kdSarana,
        int $start = 0,
        int $limit = 10
    ): array {
        $formattedDate = $this->formatDatePcare($tglEstRujuk);

        return $this->request(
            endpoint: "spesialis/{$kdSpesialis}/tanggalRujuk/{$formattedDate}/sarana/{$kdSarana}/faskes/{$start}/{$limit}",
            method: 'GET'
        );
    }

    // ==================== RUJUKAN METHODS ====================

    /**
     * Submit rujukan (referral).
     *
     * @param array $data Rujukan data
     * @return array Response with submission result
     */
    public function postRujukan(array $data): array
    {
        return $this->request(
            endpoint: 'rujukan',
            method: 'POST',
            data: $data
        );
    }

    /**
     * Update rujukan.
     *
     * @param array $data Rujukan data
     * @return array Response with update result
     */
    public function putRujukan(array $data): array
    {
        return $this->request(
            endpoint: 'rujukan',
            method: 'PUT',
            data: $data
        );
    }

    /**
     * Delete rujukan.
     *
     * @param string $noKunjungan Visit number
     * @param string $user User performing deletion
     * @return array Response with deletion result
     */
    public function deleteRujukan(string $noKunjungan, string $user): array
    {
        return $this->request(
            endpoint: "rujukan/{$noKunjungan}/{$user}",
            method: 'DELETE'
        );
    }

    /**
     * Get rujukan by visit number.
     *
     * @param string $noKunjungan Visit number
     * @return array Response with rujukan data
     */
    public function getRujukan(string $noKunjungan): array
    {
        return $this->request(
            endpoint: "rujukan/{$noKunjungan}",
            method: 'GET'
        );
    }

    // ==================== ANGGOTA METHODS ====================

    /**
     * Get anggota (member) by card number.
     *
     * @param string $nomorKartu BPJS card number
     * @return array Response with anggota data
     */
    public function getAnggota(string $nomorKartu): array
    {
        return $this->request(
            endpoint: "peserta/{$nomorKartu}/anggota",
            method: 'GET'
        );
    }

    // ==================== PENDAFTARAN METHODS ====================
    /**
     * Get pendaftaran (registration) by provider.
     *
     * @param string|DateTimeInterface $tglDaftar Registration date
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with pendaftaran list
     */
    public function getPendaftaranByProvider(
        string|DateTimeInterface $tglDaftar,
        int $start = 0,
        int $limit = 10
    ): array {
        $formattedDate = $this->formatDatePcare($tglDaftar);

        return $this->request(
            endpoint: "pendaftaran/tglDaftar/{$formattedDate}/{$start}/{$limit}",
            method: 'GET'
        );
    }

    /**
     * Get pendaftaran by card number.
     *
     * @param string $nomorKartu BPJS card number
     * @param int $start Start index
     * @param int $limit Number of records
     * @return array Response with pendaftaran list
     */
    public function getPendaftaranByKartu(string $nomorKartu, int $start = 0, int $limit = 10): array
    {
        return $this->request(
            endpoint: "pendaftaran/peserta/{$nomorKartu}/{$start}/{$limit}",
            method: 'GET'
        );
    }
}
