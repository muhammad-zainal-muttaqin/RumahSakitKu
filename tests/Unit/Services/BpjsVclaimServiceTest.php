<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BPJS\BpjsVclaimService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for BPJS VClaim 2.0 Service.
 *
 * Tests participant verification, SEP management, referral management,
 * and healthcare facility queries.
 */
class BpjsVclaimServiceTest extends TestCase
{
    private BpjsVclaimService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config(['bpjs.vclaim.base_url' => 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest']);
        config(['bpjs.vclaim.cons_id' => 'test-cons-id']);
        config(['bpjs.vclaim.secret_key' => 'test-secret-key']);
        config(['bpjs.vclaim.user_key' => 'test-user-key']);

        $this->service = new BpjsVclaimService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock HTTP response.
     *
     * @param array<string, mixed> $data
     * @param int $status
     * @return Response
     */
    private function createMockResponse(array $data, int $status = 200): Response
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn($status >= 200 && $status < 300);
        $response->shouldReceive('status')->andReturn($status);
        $response->shouldReceive('body')->andReturn(json_encode($data));
        $response->shouldReceive('json')->andReturn($data);

        return $response;
    }

    /**
     * Test service initialization with correct configuration.
     */
    public function test_service_initializes_with_correct_configuration(): void
    {
        $reflection = new \ReflectionClass($this->service);

        $serviceName = $reflection->getProperty('serviceName');
        $serviceName->setAccessible(true);
        $this->assertEquals('vclaim', $serviceName->getValue($this->service));

        $baseUrl = $reflection->getProperty('baseUrl');
        $baseUrl->setAccessible(true);
        $this->assertStringContainsString('vclaim-rest', $baseUrl->getValue($this->service));
    }

    /**
     * Data provider for peserta test cases.
     *
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function pesertaProvider(): array
    {
        return [
            'active_participant' => [
                '1234567890123456',
                '2024-01-15',
                [
                    'metaData' => ['code' => '200', 'message' => 'OK'],
                    'response' => [
                        'peserta' => [
                            'noKartu' => '0001234567890',
                            'nama' => 'John Doe',
                            'hakKelas' => ['kode' => '1', 'keterangan' => 'KELAS I'],
                            'statusPeserta' => ['kode' => '0', 'keterangan' => 'AKTIF'],
                        ],
                    ],
                ],
            ],
            'inactive_participant' => [
                '1234567890123457',
                '2024-01-15',
                [
                    'metaData' => ['code' => '200', 'message' => 'OK'],
                    'response' => [
                        'peserta' => [
                            'noKartu' => '0001234567891',
                            'nama' => 'Jane Doe',
                            'hakKelas' => ['kode' => '2', 'keterangan' => 'KELAS II'],
                            'statusPeserta' => ['kode' => '1', 'keterangan' => 'TIDAK AKTIF'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getPesertaByNik with various scenarios.
     *
     * @param string $nik
     * @param string $tglSep
     * @param array<string, mixed> $responseData
     */
    #[DataProvider('pesertaProvider')]
    public function test_get_peserta_by_nik_success(string $nik, string $tglSep, array $responseData): void
    {
        Http::fake([
            '*' => Http::response($responseData, 200),
        ]);

        $result = $this->service->getPesertaByNik($nik, $tglSep);

        $this->assertTrue($result['success']);
        $this->assertEquals('200', $result['code']);
        $this->assertArrayHasKey('peserta', $result['data']);
    }

    /**
     * Test getPesertaByNik with error response.
     */
    public function test_get_peserta_by_nik_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '201', 'message' => 'Peserta tidak ditemukan'],
            ], 200),
        ]);

        $result = $this->service->getPesertaByNik('0000000000000000', '2024-01-15');

        $this->assertFalse($result['success']);
        $this->assertEquals('201', $result['code']);
    }

    /**
     * Test getPesertaByBpjs with success response.
     */
    public function test_get_peserta_by_bpjs_success(): void
    {
        $responseData = [
            'metaData' => ['code' => '200', 'message' => 'OK'],
            'response' => [
                'peserta' => [
                    'noKartu' => '0001234567890',
                    'nama' => 'John Doe',
                    'hakKelas' => ['kode' => '3', 'keterangan' => 'KELAS III'],
                ],
            ],
        ];

        Http::fake([
            '*' => Http::response($responseData, 200),
        ]);

        $result = $this->service->getPesertaByBpjs('0001234567890', '2024-01-15');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('peserta', $result['data']);
        $this->assertEquals('0001234567890', $result['data']['peserta']['noKartu']);
    }

    /**
     * Test getPesertaByKartu alias method.
     */
    public function test_get_peserta_by_kartu_alias(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['peserta' => ['noKartu' => '0001234567890']],
            ], 200),
        ]);

        $result = $this->service->getPesertaByKartu('0001234567890', '2024-01-15');

        $this->assertTrue($result['success']);
    }

    /**
     * Data provider for SEP creation test cases.
     *
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function sepCreationProvider(): array
    {
        return [
            'outpatient_sep' => [
                [
                    'noKartu' => '0001234567890',
                    'tglSep' => '2024-01-15',
                    'ppkPelayanan' => '0901R001',
                    'jnsPelayanan' => '2',
                    'klsRawatHak' => '3',
                    'noMR' => 'MR001',
                    'asalRujukan' => '2',
                    'tglRujukan' => '2024-01-14',
                    'noRujukan' => 'RJK001',
                    'ppkRujukan' => '0901R002',
                    'catatan' => 'Test SEP',
                    'diagAwal' => 'A00',
                    'poliTujuan' => 'INT',
                    'user' => 'testuser',
                ],
                [
                    'metaData' => ['code' => '200', 'message' => 'OK'],
                    'response' => [
                        'sep' => [
                            'noSep' => '0901R0010124A000001',
                            'tglSep' => '2024-01-15',
                            'noKartu' => '0001234567890',
                        ],
                    ],
                ],
            ],
            'inpatient_sep' => [
                [
                    'noKartu' => '0001234567890',
                    'tglSep' => '2024-01-15',
                    'ppkPelayanan' => '0901R001',
                    'jnsPelayanan' => '1',
                    'klsRawatHak' => '1',
                    'noMR' => 'MR001',
                    'diagAwal' => 'J18',
                    'poliTujuan' => 'IGD',
                    'user' => 'testuser',
                ],
                [
                    'metaData' => ['code' => '200', 'message' => 'OK'],
                    'response' => [
                        'sep' => [
                            'noSep' => '0901R0010124I000001',
                            'tglSep' => '2024-01-15',
                            'jnsPelayanan' => '1',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test createSep with various scenarios.
     *
     * @param array<string, mixed> $sepData
     * @param array<string, mixed> $responseData
     */
    #[DataProvider('sepCreationProvider')]
    public function test_create_sep_success(array $sepData, array $responseData): void
    {
        Http::fake([
            '*' => Http::response($responseData, 200),
        ]);

        $result = $this->service->createSep($sepData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sep', $result['data']);
    }

    /**
     * Test createSep with validation error.
     */
    public function test_create_sep_validation_error(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '201', 'message' => 'Nomor kartu tidak valid'],
            ], 200),
        ]);

        $sepData = [
            'noKartu' => 'INVALID',
            'tglSep' => '2024-01-15',
            'ppkPelayanan' => '0901R001',
            'jnsPelayanan' => '2',
            'klsRawatHak' => '3',
            'noMR' => 'MR001',
            'diagAwal' => 'A00',
            'poliTujuan' => 'INT',
        ];

        $result = $this->service->createSep($sepData);

        $this->assertFalse($result['success']);
        $this->assertEquals('201', $result['code']);
    }

    /**
     * Test updateSep success.
     */
    public function test_update_sep_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['sep' => ['noSep' => '0901R0010124A000001']],
            ], 200),
        ]);

        $updateData = [
            'klsRawatHak' => '2',
            'noMR' => 'MR001',
            'catatan' => 'Updated notes',
            'diagAwal' => 'A01',
            'poliTujuan' => 'INT',
        ];

        $result = $this->service->updateSep('0901R0010124A000001', $updateData);

        $this->assertTrue($result['success']);
    }

    /**
     * Test deleteSep success.
     */
    public function test_delete_sep_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'SEP berhasil dihapus'],
            ], 200),
        ]);

        $result = $this->service->deleteSep('0901R0010124A000001', 'testuser');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getSep success.
     */
    public function test_get_sep_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'sep' => [
                        'noSep' => '0901R0010124A000001',
                        'noKartu' => '0001234567890',
                        'namaPeserta' => 'John Doe',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getSep('0901R0010124A000001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sep', $result['data']);
    }

    /**
     * Test getRujukanByNomor success.
     */
    public function test_get_rujukan_by_nomor_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'rujukan' => [
                        'noRujukan' => 'RJK001',
                        'tglRujukan' => '2024-01-14',
                        'noKartu' => '0001234567890',
                        'nama' => 'John Doe',
                        'poliTujuan' => 'INT',
                        'diagnosa' => 'A00',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getRujukanByNomor('RJK001');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('rujukan', $result['data']);
        $this->assertEquals('RJK001', $result['data']['rujukan']['noRujukan']);
    }

    /**
     * Test getRujukanRsByNomor success.
     */
    public function test_get_rujukan_rs_by_nomor_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['rujukan' => ['noRujukan' => 'RS-RJK001']],
            ], 200),
        ]);

        $result = $this->service->getRujukanRsByNomor('RS-RJK001');

        $this->assertTrue($result['success']);
    }

    /**
     * Test getRujukanByKartu success.
     */
    public function test_get_rujukan_by_kartu_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'rujukan' => [
                        ['noRujukan' => 'RJK001'],
                        ['noRujukan' => 'RJK002'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getRujukanByKartu('0001234567890');

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']['rujukan']);
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
                    'diagnosa' => [
                        ['kode' => 'A00', 'nama' => 'Cholera'],
                        ['kode' => 'A00.0', 'nama' => 'Cholera due to Vibrio cholerae 01, biovar cholerae'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDiagnosa('A00');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('diagnosa', $result['data']);
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
                    'poli' => [
                        ['kode' => 'INT', 'nama' => 'PENYAKIT DALAM'],
                        ['kode' => 'ANA', 'nama' => 'ANAK'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPoli('INT');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('poli', $result['data']);
    }

    /**
     * Test getPoliList success.
     */
    public function test_get_poli_list_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'poli' => [
                        ['kode' => 'INT', 'nama' => 'PENYAKIT DALAM'],
                        ['kode' => 'BED', 'nama' => 'BEDAH'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getPoliList();
        $this->assertTrue($result['success']);
    }

    /**
     * Test getFaskes success.
     */
    public function test_get_faskes_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'faskes' => [
                        ['kode' => '0901R001', 'nama' => 'RS TEST 1'],
                        ['kode' => '0901R002', 'nama' => 'RS TEST 2'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getFaskes('RS TEST', '2');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('faskes', $result['data']);
    }

    /**
     * Test getDokterDpjp success.
     */
    public function test_get_dokter_dpjp_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'list' => [
                        ['kode' => '123456', 'nama' => 'Dr. Test One'],
                        ['kode' => '654321', 'nama' => 'Dr. Test Two'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDokterDpjp('2', '2024-01-15', 'INT');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('list', $result['data']);
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
                    'provider' => [
                        'kode' => '0901R001',
                        'nama' => 'RS TEST',
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getProvider();

        $this->assertTrue($result['success']);
    }

    /**
     * Test getSepHistory success.
     */
    public function test_get_sep_history_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'histori' => [
                        ['noSep' => '0901R0010124A000001', 'tglSep' => '2024-01-01'],
                        ['noSep' => '0901R0010124A000002', 'tglSep' => '2024-01-10'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getSepHistory('0001234567890', '2024-01-01', '2024-01-31');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('histori', $result['data']);
    }

    /**
     * Test getKlaimData success.
     */
    public function test_get_klaim_data_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'klaim' => [
                        ['noSep' => '0901R0010124A000001', 'status' => 'Proses'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getKlaimData('2024-01-01', '2024-01-31', '1');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateSepInternalReg success.
     */
    public function test_update_sep_internal_reg_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Update Berhasil'],
            ], 200),
        ]);

        $result = $this->service->updateSepInternalReg('0901R0010124A000001', 'REG001', 'testuser');

        $this->assertTrue($result['success']);
    }

    /**
     * Test deleteSepInternal success.
     */
    public function test_delete_sep_internal_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Hapus Berhasil'],
            ], 200),
        ]);

        $result = $this->service->deleteSepInternal('0901R0010124A000001', 'testuser');

        $this->assertTrue($result['success']);
    }

    /**
     * Test updateSuplesi success.
     */
    public function test_update_suplesi_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['message' => 'Update Suplesi Berhasil'],
            ], 200),
        ]);

        $suplesiData = [
            'noSepSuplesi' => 'SUP001',
            'tglKejadian' => '2024-01-10',
            'keterangan' => 'Kecelakaan',
            'kdPropinsi' => '31',
            'kdKabupaten' => '3171',
            'kdKecamatan' => '317103',
        ];

        $result = $this->service->updateSuplesi('0901R0010124A000001', $suplesiData);

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

        $result = $this->service->getPesertaByNik('1234567890123456', '2024-01-15');

        $this->assertFalse($result['success']);
        $this->assertEquals('500', $result['code']);
    }

    /**
     * Test network timeout handling.
     */
    public function test_network_timeout_handling(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);

        $result = $this->service->getPesertaByNik('1234567890123456', '2024-01-15');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('System error', $result['message']);
    }

    /**
     * Test date formatting with DateTime object.
     */
    public function test_date_formatting_with_datetime_object(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => ['peserta' => []],
            ], 200),
        ]);

        $dateTime = new \DateTime('2024-01-15');
        $result = $this->service->getPesertaByNik('1234567890123456', $dateTime);

        $this->assertTrue($result['success']);
    }
}
