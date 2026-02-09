<?php

declare(strict_types=1);

namespace App\Services\BPJS;

use DateTimeInterface;

/**
 * BPJS VClaim 2.0 Service
 * 
 * Service for BPJS VClaim (Verification & Claim) API integration.
 * 
 * Handles integration with BPJS VClaim API for:
 * - Participant verification (Peserta)
 * - SEP (Surat Eligibilitas Peserta) management
 * - Referral (Rujukan) management
 * - Healthcare facility (Faskes) queries
 * - Diagnosis and polyclinic lookups
 */
class BpjsVclaimService extends BpjsService
{
    protected string $serviceName = 'vclaim';

    protected function initializeConfig(): void
    {
        $this->baseUrl = config('bpjs.vclaim.base_url', 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest');
        $this->consId = config('bpjs.vclaim.cons_id', '');
        $this->secretKey = config('bpjs.vclaim.secret_key', '');
        $this->userKey = config('bpjs.vclaim.user_key', '');
    }

    // ==================== PESERTA (PARTICIPANT) METHODS ====================
    /**
     * Get participant by NIK (Nomor Induk Kependudukan).
     *
     * @param string $nik National ID number (16 digits)
     * @param string|DateTimeInterface $tglSep SEP date (YYYY-MM-DD)
     * @return array Response with participant data
     */
    public function getPesertaByNik(string $nik, string|DateTimeInterface $tglSep): array
    {
        $formattedDate = $this->formatDate($tglSep);

        return $this->request(
            endpoint: "Peserta/nik/{$nik}/tglSEP/{$formattedDate}",
            method: 'GET'
        );
    }

    /**
     * Get participant by BPJS number (Nomor Kartu BPJS).
     *
     * @param string $bpjsNumber BPJS card number
     * @param string|DateTimeInterface $tglSep SEP date (YYYY-MM-DD)
     * @return array Response with participant data
     */
    public function getPesertaByBpjs(string $bpjsNumber, string|DateTimeInterface $tglSep): array
    {
        $formattedDate = $this->formatDate($tglSep);

        return $this->request(
            endpoint: "Peserta/nokartu/{$bpjsNumber}/tglSEP/{$formattedDate}",
            method: 'GET'
        );
    }

    /**
     * Alias for getPesertaByBpjs - Get participant by card number.
     *
     * @param string $noKartu Card number
     * @param string|DateTimeInterface $tglSep SEP date
     * @return array Response with participant data
     */
    public function getPesertaByKartu(string $noKartu, string|DateTimeInterface $tglSep): array
    {
        return $this->getPesertaByBpjs($noKartu, $tglSep);
    }

    // ==================== SEP (SURAT ELIGIBILITAS PESERTA) METHODS ====================

    /**
     * Create new SEP.
     *
     * Required data fields:
     * - noKartu: BPJS card number
     * - tglSep: SEP date (YYYY-MM-DD)
     * - ppkPelayanan: Provider code (faskes)
     * - jnsPelayanan: Service type (1=Rawat Inap, 2=Rawat Jalan)
     * - klsRawat: Class (1=Kelas 1, 2=Kelas 2, 3=Kelas 3)
     * - noMR: Medical record number
     * - asalRujukan: Referral source (1=Faskes 1, 2=Faskes 2)
     * - tglRujukan: Referral date
     * - noRujukan: Referral number
     * - ppkRujukan: Referring provider code
     * - catatan: Notes
     * - diagAwal: Initial diagnosis code (ICD-10)
     * - poliTujuan: Destination polyclinic code
     * - klsRawatHak: Patient class right
     * - user: User creating SEP
     *
     * @param array $data SEP data
     * @return array Response with SEP number
     */
    public function createSep(array $data): array
    {
        $requestData = [
            'request' => [
                't_sep' => [
                    'noKartu' => $data['noKartu'],
                    'tglSep' => $data['tglSep'],
                    'ppkPelayanan' => $data['ppkPelayanan'],
                    'jnsPelayanan' => $data['jnsPelayanan'],
                    'klsRawat' => [
                        'klsRawatHak' => $data['klsRawatHak'] ?? $data['klsRawat'] ?? '3',
                        'klsRawatNaik' => $data['klsRawatNaik'] ?? '',
                        'pembiayaan' => $data['pembiayaan'] ?? '',
                        'penanggungJawab' => $data['penanggungJawab'] ?? '',
                    ],
                    'noMR' => $data['noMR'],
                    'rujukan' => [
                        'asalRujukan' => $data['asalRujukan'] ?? '2',
                        'tglRujukan' => $data['tglRujukan'] ?? '',
                        'noRujukan' => $data['noRujukan'] ?? '',
                        'ppkRujukan' => $data['ppkRujukan'] ?? '',
                    ],
                    'catatan' => $data['catatan'] ?? '',
                    'diagAwal' => $data['diagAwal'],
                    'poli' => [
                        'tujuan' => $data['poliTujuan'],
                        'eksekutif' => $data['poliEksekutif'] ?? '0',
                    ],
                    'cob' => [
                        'cob' => $data['cob'] ?? '0',
                    ],
                    'katarak' => [
                        'katarak' => $data['katarak'] ?? '0',
                    ],
                    'jaminan' => [
                        'lakaLantas' => $data['lakaLantas'] ?? '0',
                        'noLP' => $data['noLP'] ?? '',
                        'penjamin' => [
                            'tglKejadian' => $data['tglKejadian'] ?? '',
                            'keterangan' => $data['keteranganKejadian'] ?? '',
                            'suplesi' => [
                                'suplesi' => $data['suplesi'] ?? '0',
                                'noSepSuplesi' => $data['noSepSuplesi'] ?? '',
                                'lokasiLaka' => [
                                    'kdPropinsi' => $data['kdPropinsi'] ?? '',
                                    'kdKabupaten' => $data['kdKabupaten'] ?? '',
                                    'kdKecamatan' => $data['kdKecamatan'] ?? '',
                                ],
                            ],
                        ],
                    ],
                    'tujuanKunj' => $data['tujuanKunj'] ?? '0',
                    'flagProcedure' => $data['flagProcedure'] ?? '',
                    'kdPenunjang' => $data['kdPenunjang'] ?? '',
                    'assesmentPel' => $data['assesmentPel'] ?? '',
                    'skdp' => [
                        'noSurat' => $data['noSuratKontrol'] ?? '',
                        'kodeDPJP' => $data['kodeDPJP'] ?? '',
                    ],
                    'dpjpLayan' => $data['dpjpLayan'] ?? '',
                    'noTelp' => $data['noTelp'] ?? '',
                    'user' => $data['user'] ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/2.0/insert',
            method: 'POST',
            data: $requestData
        );
    }

    /**
     * Update existing SEP.
     *
     * @param string $noSep SEP number to update
     * @param array $data Updated SEP data
     * @return array Response
     */
    public function updateSep(string $noSep, array $data): array
    {
        $requestData = [
            'request' => [
                't_sep' => [
                    'noSep' => $noSep,
                    'klsRawat' => [
                        'klsRawatHak' => $data['klsRawatHak'] ?? $data['klsRawat'] ?? '3',
                        'klsRawatNaik' => $data['klsRawatNaik'] ?? '',
                        'pembiayaan' => $data['pembiayaan'] ?? '',
                        'penanggungJawab' => $data['penanggungJawab'] ?? '',
                    ],
                    'noMR' => $data['noMR'] ?? '',
                    'catatan' => $data['catatan'] ?? '',
                    'diagAwal' => $data['diagAwal'] ?? '',
                    'poli' => [
                        'tujuan' => $data['poliTujuan'] ?? '',
                        'eksekutif' => $data['poliEksekutif'] ?? '0',
                    ],
                    'cob' => [
                        'cob' => $data['cob'] ?? '0',
                    ],
                    'katarak' => [
                        'katarak' => $data['katarak'] ?? '0',
                    ],
                    'jaminan' => [
                        'lakaLantas' => $data['lakaLantas'] ?? '0',
                        'noLP' => $data['noLP'] ?? '',
                        'penjamin' => [
                            'tglKejadian' => $data['tglKejadian'] ?? '',
                            'keterangan' => $data['keteranganKejadian'] ?? '',
                            'suplesi' => [
                                'suplesi' => $data['suplesi'] ?? '0',
                                'noSepSuplesi' => $data['noSepSuplesi'] ?? '',
                                'lokasiLaka' => [
                                    'kdPropinsi' => $data['kdPropinsi'] ?? '',
                                    'kdKabupaten' => $data['kdKabupaten'] ?? '',
                                    'kdKecamatan' => $data['kdKecamatan'] ?? '',
                                ],
                            ],
                        ],
                    ],
                    'tujuanKunj' => $data['tujuanKunj'] ?? '0',
                    'flagProcedure' => $data['flagProcedure'] ?? '',
                    'kdPenunjang' => $data['kdPenunjang'] ?? '',
                    'assesmentPel' => $data['assesmentPel'] ?? '',
                    'skdp' => [
                        'noSurat' => $data['noSuratKontrol'] ?? '',
                        'kodeDPJP' => $data['kodeDPJP'] ?? '',
                    ],
                    'dpjpLayan' => $data['dpjpLayan'] ?? '',
                    'noTelp' => $data['noTelp'] ?? '',
                    'user' => $data['user'] ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/2.0/update',
            method: 'PUT',
            data: $requestData
        );
    }

    /**
     * Delete SEP.
     *
     * @param string $noSep SEP number to delete
     * @param string|null $user User performing deletion
     * @return array Response
     */
    public function deleteSep(string $noSep, ?string $user = null): array
    {
        $requestData = [
            'request' => [
                't_sep' => [
                    'noSep' => $noSep,
                    'user' => $user ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/2.0/delete',
            method: 'DELETE',
            data: $requestData
        );
    }

    /**
     * Get SEP detail.
     *
     * @param string $noSep SEP number
     * @return array Response with SEP details
     */
    public function getSep(string $noSep): array
    {
        return $this->request(
            endpoint: "SEP/{$noSep}",
            method: 'GET'
        );
    }

    /**
     * Get SEP by internal registration number.
     *
     * @param string $noReg Internal registration number
     * @return array Response with SEP details
     */
    public function getSepByInternalReg(string $noReg): array
    {
        return $this->request(
            endpoint: "SEP/FingerPrint/{$noReg}/Peserta",
            method: 'GET'
        );
    }

    // ==================== RUJUKAN (REFERRAL) METHODS ====================

    /**
     * Get referral by number.
     *
     * @param string $noRujukan Referral number
     * @return array Response with referral data
     */
    public function getRujukanByNomor(string $noRujukan): array
    {
        return $this->request(
            endpoint: "Rujukan/{$noRujukan}",
            method: 'GET'
        );
    }

    /**
     * Get referral by number from RS (Rumah Sakit).
     *
     * @param string $noRujukan Referral number
     * @return array Response with referral data
     */
    public function getRujukanRsByNomor(string $noRujukan): array
    {
        return $this->request(
            endpoint: "Rujukan/RS/{$noRujukan}",
            method: 'GET'
        );
    }

    /**
     * Get referrals by card number (list all).
     *
     * @param string $noKartu BPJS card number
     * @return array Response with list of referrals
     */
    public function getRujukanByKartu(string $noKartu): array
    {
        return $this->request(
            endpoint: "Rujukan/List/Peserta/{$noKartu}",
            method: 'GET'
        );
    }

    /**
     * Get RS referrals by card number.
     *
     * @param string $noKartu BPJS card number
     * @return array Response with list of referrals
     */
    public function getRujukanRsByKartu(string $noKartu): array
    {
        return $this->request(
            endpoint: "Rujukan/RS/List/Peserta/{$noKartu}",
            method: 'GET'
        );
    }

    /**
     * Get referral by card number (single/latest).
     *
     * @param string $noKartu BPJS card number
     * @return array Response with referral data
     */
    public function getRujukanByKartuSingle(string $noKartu): array
    {
        return $this->request(
            endpoint: "Rujukan/Peserta/{$noKartu}",
            method: 'GET'
        );
    }

    /**
     * Get RS referral by card number (single/latest).
     *
     * @param string $noKartu BPJS card number
     * @return array Response with referral data
     */
    public function getRujukanRsByKartuSingle(string $noKartu): array
    {
        return $this->request(
            endpoint: "Rujukan/RS/Peserta/{$noKartu}",
            method: 'GET'
        );
    }

    // ==================== DIAGNOSA & POLI METHODS ====================

    /**
     * Get diagnosis by ICD-10 code.
     *
     * @param string $kode ICD-10 code (e.g., 'A00')
     * @return array Response with diagnosis data
     */
    public function getDiagnosa(string $kode): array
    {
        return $this->request(
            endpoint: "referensi/diagnosa/{$kode}",
            method: 'GET'
        );
    }

    /**
     * Get polyclinic by code.
     *
     * @param string $kode Polyclinic code
     * @return array Response with polyclinic data
     */
    public function getPoli(string $kode): array
    {
        return $this->request(
            endpoint: "referensi/poli/{$kode}",
            method: 'GET'
        );
    }

    /**
     * Get all polyclinics (for dropdown).
     *
     * @param string $keyword Search keyword
     * @return array Response with polyclinic list
     */
    public function getPoliList(string $keyword = ''): array
    {
        return $this->request(
            endpoint: "referensi/poli/{$keyword}",
            method: 'GET'
        );
    }

    // ==================== FASKES (HEALTHCARE FACILITIES) METHODS ====================

    /**
     * Search healthcare facilities.
     *
     * @param string $nama Facility name or keyword
     * @param string $jenisFaskes Type: 1=Faskes 1, 2=Faskes 2/RS
     * @return array Response with facility list
     */
    public function getFaskes(string $nama, string $jenisFaskes = '2'): array
    {
        return $this->request(
            endpoint: "referensi/faskes/{$nama}/{$jenisFaskes}",
            method: 'GET'
        );
    }

    /**
     * Get DPJP (Dokter Penanggung Jawab Pelayanan) list.
     *
     * @param string $jnsPelayanan Service type: 1=Rawat Inap, 2=Rawat Jalan
     * @param string|DateTimeInterface $tglPelayanan Service date
     * @param string $spesialis Specialty code
     * @return array Response with doctor list
     */
    public function getDokterDpjp(
        string $jnsPelayanan,
        string|DateTimeInterface $tglPelayanan,
        string $spesialis
    ): array {
        $formattedDate = $this->formatDate($tglPelayanan);

        return $this->request(
            endpoint: "referensi/dokter/pelayanan/{$jnsPelayanan}/tglPelayanan/{$formattedDate}/Spesialis/{$spesialis}",
            method: 'GET'
        );
    }

    // ==================== PROVIDER METHODS ====================

    /**
     * Get provider data.
     *
     * @return array Response with provider data
     */
    public function getProvider(): array
    {
        return $this->request(
            endpoint: 'referensi/provider',
            method: 'GET'
        );
    }

    // ==================== SEP INTERNAL METHODS ====================

    /**
     * Update SEP internal registration number.
     *
     * @param string $noSep SEP number
     * @param string $noReg Internal registration number
     * @param string|null $user User performing update
     * @return array Response
     */
    public function updateSepInternalReg(string $noSep, string $noReg, ?string $user = null): array
    {
        $requestData = [
            'request' => [
                't_sep' => [
                    'noSep' => $noSep,
                    'noReg' => $noReg,
                    'user' => $user ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/Internal/update',
            method: 'PUT',
            data: $requestData
        );
    }

    /**
     * Delete SEP internal registration.
     *
     * @param string $noSep SEP number
     * @param string|null $user User performing deletion
     * @return array Response
     */
    public function deleteSepInternal(string $noSep, ?string $user = null): array
    {
        $requestData = [
            'request' => [
                't_sep' => [
                    'noSep' => $noSep,
                    'user' => $user ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/Internal/delete',
            method: 'DELETE',
            data: $requestData
        );
    }

    // ==================== SUPLETION (ACCIDENT) METHODS ====================

    /**
     * Update accident information (Suplesi).
     *
     * @param string $noSep SEP number
     * @param array $data Accident data
     * @return array Response
     */
    public function updateSuplesi(string $noSep, array $data): array
    {
        $requestData = [
            'request' => [
                't_suplesi' => [
                    'noSep' => $noSep,
                    'noSepSuplesi' => $data['noSepSuplesi'] ?? '',
                    'tglKejadian' => $data['tglKejadian'] ?? '',
                    'keterangan' => $data['keterangan'] ?? '',
                    'lokasiLaka' => [
                        'kdPropinsi' => $data['kdPropinsi'] ?? '',
                        'kdKabupaten' => $data['kdKabupaten'] ?? '',
                        'kdKecamatan' => $data['kdKecamatan'] ?? '',
                    ],
                    'user' => $data['user'] ?? auth()->user()?->name ?? 'system',
                ],
            ],
        ];

        return $this->request(
            endpoint: 'SEP/Suplesi',
            method: 'PUT',
            data: $requestData
        );
    }

    // ==================== HISTORY METHODS ====================
    /**
     * Get SEP history by card number.
     *
     * @param string $noKartu BPJS card number
     * @param string|DateTimeInterface $tglAwal Start date
     * @param string|DateTimeInterface $tglAkhir End date
     * @return array Response with SEP history
     */
    public function getSepHistory(
        string $noKartu,
        string|DateTimeInterface $tglAwal,
        string|DateTimeInterface $tglAkhir
    ): array {
        $formattedStart = $this->formatDate($tglAwal);
        $formattedEnd = $this->formatDate($tglAkhir);

        return $this->request(
            endpoint: "monitoring/HistoriPelayanan/NoKartu/{$noKartu}/tglAwal/{$formattedStart}/tglAkhir/{$formattedEnd}",
            method: 'GET'
        );
    }

    /**
     * Get claim data (Data Klaim).
     *
     * @param string|DateTimeInterface $tglMasuk Admission date
     * @param string|DateTimeInterface $tglKeluar Discharge date
     * @param string $klaimStatus Claim status
     * @return array Response with claim data
     */
    public function getKlaimData(
        string|DateTimeInterface $tglMasuk,
        string|DateTimeInterface $tglKeluar,
        string $klaimStatus = '1'
    ): array {
        $formattedMasuk = $this->formatDate($tglMasuk);
        $formattedKeluar = $this->formatDate($tglKeluar);

        return $this->request(
            endpoint: "Monitoring/Klaim/Tanggal/{$formattedMasuk}/JnsPelayanan/{$klaimStatus}/Status/{$formattedKeluar}",
            method: 'GET'
        );
    }
}
