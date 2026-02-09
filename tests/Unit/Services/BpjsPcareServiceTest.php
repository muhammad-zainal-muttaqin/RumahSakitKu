<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BPJS\BpjsPcareService;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for BPJS PCare Service.
 *
 * Tests participant verification, visit management, provider queries,
 * and PCare-specific endpoints.
 */
class BpjsPcareServiceTest extends TestCase
{
    private BpjsPcareService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config(['bpjs.pcare.base_url' => 'https://apijkn.bpjs-kesehatan.go.id/pcare-rest']);
        config(['bpjs.pcare.cons_id' => 'test-cons-id']);
        config(['bpjs.pcare.secret_key' => 'test-secret-key']);
        config(['bpjs.pcare.user_key' => 'test-user-key']);
        config(['bpjs.pcare.pcare_user' => 'pcare-user']);
        config(['bpjs.pcare.pcare_password' => 'pcare-password']);
        config(['bpjs.pcare.kd_aplikasi' => '095']);

        $this->service = new BpjsPcareService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test service initialization with correct configuration.
     */
    public function test_service_initializes_with_correct_configuration(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $serviceName = $reflection->getProperty('serviceName');
        $serviceName->setAccessible(true);
        $this->assertEquals('pcare', $serviceName->getValue($this->service));

        $pcareUser = $reflection->getProperty('pcareUser');
        $pcareUser->setAccessible(true);
        $this->assertEquals('pcare-user', $pcareUser->getValue($this->service));

        $pcarePassword = $reflection->getProperty('pcarePassword');
        $pcarePassword->setAccessible(true);
        $this->assertEquals('pcare-password', $pcarePassword->getValue($this->service));

        $kdAplikasi = $reflection->getProperty('kdAplikasi');
        $kdAplikasi->setAccessible(true);
        $this->assertEquals('095', $kdAplikasi->getValue($this->service));
    }

    /**
     * Test PCare headers include X-authorization with Basic auth.
     */
    public function test_get_headers_includes_x_authorization(): void
    {
        $timestamp = '1234567890';
        $signature = 'test-signature';
        $headers = $this->service->getHeaders($timestamp, $signature);

        $this->assertArrayHasKey('X-cons-id', $headers);
        $this->assertArrayHasKey('X-timestamp', $headers);
        $this->assertArrayHasKey('X-signature', $headers);
        $this->assertArrayHasKey('user_key', $headers);
        $this->assertArrayHasKey('X-authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);

        $this->assertStringStartsWith('Basic ', $headers['X-authorization']);

        // Verify base64 encoding
        $decoded = base64_decode(substr($headers['X-authorization'], 6));
        $this->assertEquals('pcare-user:pcare-password', $decoded);
    }

    /**
     * Test formatDatePcare method.
     */
    public function test_format_date_pcare(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatDatePcare');
        $method->setAccessible(true);

        // Test with string
        $result = $method->invoke($this->service, '2024-01-15');
        $this->assertEquals('15-01-2024', $result);

        // Test with DateTime
        $dateTime = new \DateTime('2024-01-15');
        $result = $method->invoke($this->service, $dateTime);
        $this->assertEquals('15-01-2024', $result);

        // Test with DateTimeImmutable
        $dateTimeImmutable = new \DateTimeImmutable('2024-01-15');
        $result = $method->invoke($this->service, $dateTimeImmutable);
        $this->assertEquals('15-01-2024', $result);
    }

    /**
     * Test getPeserta success.
     */
    public function test_get_peserta_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'noKartu' => '0001234567890',
                    'nama' => 'John Doe',
                    'hubunganKeluarga' => '1',
                    'sex' => 'L',
                    'tglLahir' => '1990-01-01',
                    'kdProvider' => '090101',
                    'nmProvider' => 'Puskesmas Test',
                ],
            ], 200),
        ]);

        $result = $this->service->getPeserta('0001234567890');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('noKartu', $result['data']);
    }

    /**
     * Test getPesertaByNik success.
     */
    public function test_get_peserta_by_nik_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'noKartu' => '0001234567890',
                    'nik' => '1234567890123456',
                    'nama' => 'John Doe',
                ],
            ], 200),
        ]);

        $result = $this->service->getPesertaByNik('1234567890123456');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPesertaByNameAndBirthdate success.
     */
    public function test_get_peserta_by_name_and_birthdate_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['noKartu' => '0001234567890', 'nama' => 'John Doe'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPesertaByNameAndBirthdate('John Doe', '1990-01-01');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getProvider success.
     */
    public function test_get_provider_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdProvider' => '090101', 'nmProvider' => 'Puskesmas Test 1'],
                        ['kdProvider' => '090102', 'nmProvider' => 'Puskesmas Test 2'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getProvider(0, 10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('list', $result['data']);
    }

    /**
     * Test getProviderByCode success.
     */
    public function test_get_provider_by_code_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'kdProvider' => '090101',
                    'nmProvider' => 'Puskesmas Test',
                    'alamat' => 'Jl. Test No. 1',
                ],
            ], 200),
        ]);

        $result = $this->service->getProviderByCode('090101');

        $this->assertTrue($result['success']);
        $this->assertEquals('090101', $result['data']['kdProvider']);
    }

    /**
     * Data provider for visit test cases.
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function visitProvider(): array
    {
        return [
            'sick_visit' => [
                [
                    'noKartu' => '0001234567890',
                    'tglDaftar' => '15-01-2024',
                    'kdPoli' => '001',
                    'keluhan' => 'Sakit kepala',
                    'kunjSakit' => true,
                    'sistole' => 120,
                    'diastole' => 80,
                    'beratBadan' => 70,
                    'tinggiBadan' => 170,
                    'heartRate' => 80,
                    'respRate' => 20,
                    'lingkarPerut' => 80,
                    'rujukInternal' => false,
                    'icd' => ['kdDiag' => 'R51', 'nmDiag' => 'Headache'],
                    'anamnesa' => 'Pasien mengeluh sakit kepala',
                    'pemeriksaanFisik' => 'Keadaan umum baik',
                    'terapi' => 'Paracetamol 500mg',
                    'statusPulang' => '1',
                    'dokter' => ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test'],
                ],
            ],
            'healthy_visit' => [
                [
                    'noKartu' => '0001234567890',
                    'tglDaftar' => '15-01-2024',
                    'kdPoli' => '001',
                    'keluhan' => '',
                    'kunjSakit' => false,
                    'sistole' => 120,
                    'diastole' => 80,
                    'beratBadan' => 70,
                    'tinggiBadan' => 170,
                    'heartRate' => 80,
                    'respRate' => 20,
                    'lingkarPerut' => 80,
                    'rujukInternal' => false,
                    'statusPulang' => '1',
                    'dokter' => ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test'],
                ],
            ],
        ];
    }

    /**
     * Test postKunjungan with various scenarios.
     *
     * @param array<string, mixed> $visitData
     */
    #[DataProvider('visitProvider')]
    public function test_post_kunjungan_success(array $visitData): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'noKunjungan' => 'KJN001',
                    'message' => 'Kunjungan berhasil disimpan',
                ],
            ], 200),
        ]);

        $result = $this->service->postKunjungan($visitData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test postKunjungan with secondary diagnoses.
     */
    public function test_post_kunjungan_with_secondary_diagnoses(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['noKunjungan' => 'KJN001'],
            ], 200),
        ]);

        $visitData = [
            'noKartu' => '0001234567890',
            'tglDaftar' => '15-01-2024',
            'kdPoli' => '001',
            'icd' => ['kdDiag' => 'R51', 'nmDiag' => 'Headache'],
            'icd2' => [
                ['kdDiag' => 'J06', 'nmDiag' => 'Acute upper respiratory infection'],
                ['kdDiag' => 'K29', 'nmDiag' => 'Gastritis'],
            ],
            'dokter' => ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test'],
        ];

        $result = $this->service->postKunjungan($visitData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test postKunjungan with procedures.
     */
    public function test_post_kunjungan_with_procedures(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['noKunjungan' => 'KJN001'],
            ], 200),
        ]);

        $visitData = [
            'noKartu' => '0001234567890',
            'tglDaftar' => '15-01-2024',
            'kdPoli' => '001',
            'icd' => ['kdDiag' => 'R51', 'nmDiag' => 'Headache'],
            'icd9' => [
                ['kdProcedures' => '89.01', 'nmProcedures' => 'Interview'],
                ['kdProcedures' => '99.01', 'nmProcedures' => 'Injection'],
            ],
            'dokter' => ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test'],
        ];

        $result = $this->service->postKunjungan($visitData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test putKunjungan success.
     */
    public function test_put_kunjungan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Kunjungan berhasil diupdate'],
            ], 200),
        ]);

        $visitData = [
            'noKunjungan' => 'KJN001',
            'noKartu' => '0001234567890',
            'tglDaftar' => '15-01-2024',
            'kdPoli' => '001',
            'keluhan' => 'Updated complaint',
            'dokter' => ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test'],
        ];

        $result = $this->service->putKunjungan($visitData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test putKunjungan without noKunjungan.
     */
    public function test_put_kunjungan_without_no_kunjungan(): void
    {
        $visitData = [
            'noKartu' => '0001234567890',
            'tglDaftar' => '15-01-2024',
            'kdPoli' => '001',
        ];

        $result = $this->service->putKunjungan($visitData);

        $this->assertFalse($result['success']);
        $this->assertEquals('VALIDATION_ERROR', $result['code']);
        $this->assertStringContainsString('noKunjungan is required', $result['message']);
    }

    /**
     * Test deleteKunjungan success.
     */
    public function test_delete_kunjungan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Kunjungan berhasil dihapus'],
            ], 200),
        ]);

        $result = $this->service->deleteKunjungan('KJN001', 'testuser');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKunjungan success.
     */
    public function test_get_kunjungan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'noKunjungan' => 'KJN001',
                    'noKartu' => '0001234567890',
                    'tglDaftar' => '15-01-2024',
                    'keluhan' => 'Sakit kepala',
                ],
            ], 200),
        ]);

        $result = $this->service->getKunjungan('KJN001');

        $this->assertTrue($result['success']);
        $this->assertEquals('KJN001', $result['data']['noKunjungan']);
    }

    /**
     * Test getKunjunganByKartu success.
     */
    public function test_get_kunjungan_by_kartu_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['noKunjungan' => 'KJN001', 'tglDaftar' => '15-01-2024'],
                        ['noKunjungan' => 'KJN002', 'tglDaftar' => '10-01-2024'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getKunjunganByKartu('0001234567890', 0, 10);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']['list']);
    }

    /**
     * Test getDiagnosa success.
     */
    public function test_get_diagnosa_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdDiag' => 'R51', 'nmDiag' => 'Headache'],
                        ['kdDiag' => 'R50', 'nmDiag' => 'Fever'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDiagnosa('headache', 0, 10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('list', $result['data']);
    }

    /**
     * Test getProcedures success.
     */
    public function test_get_procedures_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdProcedure' => '89.01', 'nmProcedure' => 'Interview'],
                        ['kdProcedure' => '99.01', 'nmProcedure' => 'Injection'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getProcedures('injection', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPoli success.
     */
    public function test_get_poli_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdPoli' => '001', 'nmPoli' => 'Poli Umum'],
                        ['kdPoli' => '002', 'nmPoli' => 'Poli Gigi'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPoli('umum', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getDokter success.
     */
    public function test_get_dokter_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdDokter' => '123456', 'nmDokter' => 'Dr. Test One'],
                        ['kdDokter' => '654321', 'nmDokter' => 'Dr. Test Two'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDokter(0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getDokterByNip success.
     */
    public function test_get_dokter_by_nip_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'kdDokter' => '123456',
                    'nmDokter' => 'Dr. Test',
                    'nip' => '198501012010011001',
                ],
            ], 200),
        ]);

        $result = $this->service->getDokterByNip('198501012010011001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKelompokCloning success.
     */
    public function test_get_kelompok_cloning_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdKc' => 'KC001', 'nmKc' => 'Kelompok Cloning 1'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getKelompokCloning('001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKelompokCloningList success.
     */
    public function test_get_kelompok_cloning_list_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['list' => []],
            ], 200),
        ]);

        $result = $this->service->getKelompokCloningList(0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getStatusPulang for outpatient.
     */
    public function test_get_status_pulang_outpatient(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdStatusPulang' => '1', 'nmStatusPulang' => 'Sembuh'],
                        ['kdStatusPulang' => '2', 'nmStatusPulang' => 'Rujuk'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getStatusPulang(false);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getStatusPulang for inpatient.
     */
    public function test_get_status_pulang_inpatient(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdStatusPulang' => '1', 'nmStatusPulang' => 'Sembuh'],
                        ['kdStatusPulang' => '4', 'nmStatusPulang' => 'Meninggal'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getStatusPulang(true);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKesetaraanRtp success.
     */
    public function test_get_kesetaraan_rtp_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['data' => []],
            ], 200),
        ]);

        $result = $this->service->getKesetaraanRtp('param1', 'param2');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKesetaraanRsb success.
     */
    public function test_get_kesetaraan_rsb_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['data' => []],
            ], 200),
        ]);

        $result = $this->service->getKesetaraanRsb('param1', 'param2');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getClub success.
     */
    public function test_get_club_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdClub' => 'CLUB001', 'nmClub' => 'Prolanis DM'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getClub(0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getClubByCode success.
     */
    public function test_get_club_by_code_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'kdClub' => 'CLUB001',
                    'nmClub' => 'Prolanis DM',
                ],
            ], 200),
        ]);

        $result = $this->service->getClubByCode('CLUB001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getKegiatanKelompok success.
     */
    public function test_get_kegiatan_kelompok_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['eduId' => 'EDU001', 'tglEdu' => '15-01-2024'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getKegiatanKelompok('2024-01-01', '2024-01-31', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPesertaKegiatanKelompok success.
     */
    public function test_get_peserta_kegiatan_kelompok_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['noKartu' => '0001234567890', 'nama' => 'John Doe'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPesertaKegiatanKelompok('EDU001', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getTindakan success.
     */
    public function test_get_tindakan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdTindakan' => 'T001', 'nmTindakan' => 'Pemeriksaan'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getTindakan('pemeriksaan', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getTindakanByCode success.
     */
    public function test_get_tindakan_by_code_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'kdTindakan' => 'T001',
                    'nmTindakan' => 'Pemeriksaan',
                ],
            ], 200),
        ]);

        $result = $this->service->getTindakanByCode('T001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getObat success.
     */
    public function test_get_obat_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdObat' => 'O001', 'nmObat' => 'Paracetamol'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getObat('paracetamol', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getObatByCode success.
     */
    public function test_get_obat_by_code_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'kdObat' => 'O001',
                    'nmObat' => 'Paracetamol 500mg',
                ],
            ], 200),
        ]);

        $result = $this->service->getObatByCode('O001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getSpesialis success.
     */
    public function test_get_spesialis_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdSpesialis' => 'S001', 'nmSpesialis' => 'Penyakit Dalam'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getSpesialis('dalam', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getSubspesialis success.
     */
    public function test_get_subspesialis_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdSubSpesialis' => 'SS001', 'nmSubSpesialis' => 'Kardiologi'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getSubspesialis('S001', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getSarana success.
     */
    public function test_get_sarana_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdSarana' => 'SR001', 'nmSarana' => 'Rumah Sakit'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getSarana('S001', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getFaskesRujukan success.
     */
    public function test_get_faskes_rujukan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kdFaskes' => 'F001', 'nmFaskes' => 'RS Test'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getFaskesRujukan('S001', '2024-01-15', 'SR001', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test postRujukan success.
     */
    public function test_post_rujukan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['noRujukan' => 'RJK001'],
            ], 200),
        ]);

        $rujukanData = [
            'noKartu' => '0001234567890',
            'kdPoli' => '001',
            'kdSubSpesialis' => 'SS001',
            'kdSarana' => 'SR001',
            'kdTacc' => '0',
        ];

        $result = $this->service->postRujukan($rujukanData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test putRujukan success.
     */
    public function test_put_rujukan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Rujukan berhasil diupdate'],
            ], 200),
        ]);

        $rujukanData = [
            'noRujukan' => 'RJK001',
            'kdSubSpesialis' => 'SS002',
        ];

        $result = $this->service->putRujukan($rujukanData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test deleteRujukan success.
     */
    public function test_delete_rujukan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Rujukan berhasil dihapus'],
            ], 200),
        ]);

        $result = $this->service->deleteRujukan('KJN001', 'testuser');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getRujukan success.
     */
    public function test_get_rujukan_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'noRujukan' => 'RJK001',
                    'tglRujukan' => '15-01-2024',
                ],
            ], 200),
        ]);

        $result = $this->service->getRujukan('KJN001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getAnggota success.
     */
    public function test_get_anggota_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['noKartu' => '0001234567891', 'nama' => 'Family Member'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getAnggota('0001234567890');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPendaftaranByProvider success.
     */
    public function test_get_pendaftaran_by_provider_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['noKartu' => '0001234567890', 'tglDaftar' => '15-01-2024'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPendaftaranByProvider('2024-01-15', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test getPendaftaranByKartu success.
     */
    public function test_get_pendaftaran_by_kartu_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['tglDaftar' => '15-01-2024', 'kdPoli' => '001'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPendaftaranByKartu('0001234567890', 0, 10);

        $this->assertTrue($result['success']);
    }

    /**
     * Test HTTP error handling.
     */
    public function test_http_error_handling(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = $this->service->getPeserta('0001234567890');

        $this->assertFalse($result['success']);
        $this->assertEquals('500', $result['code']);
    }

    /**
     * Test network exception handling.
     */
    public function test_network_exception_handling(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
            },
        ]);

        $result = $this->service->getPeserta('0001234567890');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('System error', $result['message']);
    }
}
