<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BPJS\BpjsEklaimService;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for BPJS E-Klaim Service.
 *
 * Tests claim creation, grouping, finalization, and complete workflow.
 */
class BpjsEklaimServiceTest extends TestCase
{
    private BpjsEklaimService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configuration
        config(['bpjs.eklaim.base_url' => 'https://apijkn.bpjs-kesehatan.go.id/eklaim']);
        config(['bpjs.eklaim.cons_id' => 'test-cons-id']);
        config(['bpjs.eklaim.secret_key' => 'test-secret-key']);
        config(['bpjs.eklaim.user_key' => 'test-user-key']);

        $this->service = new BpjsEklaimService();
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
        $this->assertEquals('eklaim', $serviceName->getValue($this->service));

        $baseUrl = $reflection->getProperty('baseUrl');
        $baseUrl->setAccessible(true);
        $this->assertStringContainsString('eklaim', $baseUrl->getValue($this->service));
    }

    /**
     * Test E-Klaim signature generation uses SHA256 format.
     */
    public function test_generate_signature_uses_sha256_format(): void
    {
        $timestamp = '1234567890';
        $signature = $this->service->generateSignature($timestamp);

        // E-Klaim signature is SHA256 hash (64 characters hex)
        $this->assertEquals(64, strlen($signature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    /**
     * Test E-Klaim headers include X-authorization.
     */
    public function test_get_headers_includes_x_authorization(): void
    {
        $timestamp = '1234567890';
        $signature = $this->service->generateSignature($timestamp);
        $headers = $this->service->getHeaders($timestamp, $signature);

        $this->assertArrayHasKey('X-cons-id', $headers);
        $this->assertArrayHasKey('X-timestamp', $headers);
        $this->assertArrayHasKey('X-signature', $headers);
        $this->assertArrayHasKey('X-authorization', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);

        $this->assertEquals('application/x-www-form-urlencoded', $headers['Content-Type']);
        $this->assertStringStartsWith('Basic ', $headers['X-authorization']);
    }

    /**
     * Test newClaim success.
     */
    public function test_new_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'success']))),
            ], 200),
        ]);

        $claimData = [
            'nomor_kartu' => '0001234567890',
            'nomor_sep' => '0901R0010124A000001',
            'nomor_rm' => 'MR001',
            'nama_pasien' => 'John Doe',
            'tgl_lahir' => '1990-01-01',
            'gender' => '1',
        ];

        $result = $this->service->newClaim($claimData);

        $this->assertIsArray($result);
    }

    /**
     * Test setClaimData success.
     */
    public function test_set_claim_data_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'success']))),
            ], 200),
        ]);

        $claimData = [
            'nomor_sep' => '0901R0010124A000001',
            'nomor_kartu' => '0001234567890',
            'tgl_masuk' => '2024-01-15 08:00:00',
            'tgl_pulang' => '2024-01-17 14:00:00',
            'jenis_rawat' => '1',
            'kelas_rawat' => '2',
            'discharge_status' => '1',
            'diagnosa' => 'A00|A01',
            'procedure' => '99.01|99.02',
            'nama_dokter' => 'Dr. Test',
            'coder_nik' => '1234567890123456',
        ];

        $result = $this->service->setClaimData($claimData);

        $this->assertIsArray($result);
    }

    /**
     * Test getClaimData success.
     */
    public function test_get_claim_data_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode([
                    'nomor_sep' => '0901R0010124A000001',
                    'status' => 'finalized',
                ]))),
            ], 200),
        ]);

        $result = $this->service->getClaimData('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test groupingStage1 success.
     */
    public function test_grouping_stage1_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode([
                    'grouper' => [
                        'code' => 'I-10-01',
                        'description' => 'Sample Grouper Result',
                        'tariff' => 5000000,
                    ],
                ]))),
            ], 200),
        ]);

        $result = $this->service->groupingStage1('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test groupingStage2 success.
     */
    public function test_grouping_stage2_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode([
                    'grouper' => [
                        'code' => 'I-10-01-S',
                        'description' => 'Special CMG Applied',
                    ],
                ]))),
            ], 200),
        ]);

        $specialCmg = ['SD0001', 'SC0001'];
        $result = $this->service->groupingStage2('0901R0010124A000001', $specialCmg);

        $this->assertIsArray($result);
    }

    /**
     * Test groupingStage2 without special CMG.
     */
    public function test_grouping_stage2_without_special_cmg(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['grouper' => []]))),
            ], 200),
        ]);

        $result = $this->service->groupingStage2('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test finalClaim success.
     */
    public function test_final_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'finalized']))),
            ], 200),
        ]);

        $result = $this->service->finalClaim('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test reeditClaim success.
     */
    public function test_reedit_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'editable']))),
            ], 200),
        ]);

        $result = $this->service->reeditClaim('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test deleteClaim success.
     */
    public function test_delete_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'deleted']))),
            ], 200),
        ]);

        $result = $this->service->deleteClaim('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Test printClaim success.
     */
    public function test_print_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode([
                    'html' => '<html><body>Claim Print</body></html>',
                ]))),
            ], 200),
        ]);

        $result = $this->service->printClaim('0901R0010124A000001');

        $this->assertIsArray($result);
    }

    /**
     * Data provider for claim validation test cases.
     *
     * @return array<string, array{array<string, mixed>, bool, array<int, string>}>
     */
    public static function claimValidationProvider(): array
    {
        return [
            'valid_claim' => [
                [
                    'nomor_sep' => '0901R0010124A000001',
                    'tgl_masuk' => '2024-01-15 08:00:00',
                    'tgl_pulang' => '2024-01-17 14:00:00',
                    'jenis_rawat' => '1',
                    'kelas_rawat' => '2',
                    'discharge_status' => '1',
                    'diagnosa' => 'A00',
                    'nama_dokter' => 'Dr. Test',
                    'coder_nik' => '1234567890123456',
                ],
                true,
                [],
            ],
            'missing_required_fields' => [
                [
                    'nomor_sep' => '0901R0010124A000001',
                    'nama_dokter' => 'Dr. Test',
                ],
                false,
                [
                    "Field 'tgl_masuk' is required",
                    "Field 'tgl_pulang' is required",
                    "Field 'jenis_rawat' is required",
                    "Field 'kelas_rawat' is required",
                    "Field 'discharge_status' is required",
                    "Field 'diagnosa' is required",
                    "Field 'coder_nik' is required",
                ],
            ],
            'invalid_jenis_rawat' => [
                [
                    'nomor_sep' => '0901R0010124A000001',
                    'tgl_masuk' => '2024-01-15 08:00:00',
                    'tgl_pulang' => '2024-01-17 14:00:00',
                    'jenis_rawat' => '5',
                    'kelas_rawat' => '2',
                    'discharge_status' => '1',
                    'diagnosa' => 'A00',
                    'nama_dokter' => 'Dr. Test',
                    'coder_nik' => '1234567890123456',
                ],
                false,
                ["Invalid care type (jenis_rawat). Must be 1, 2, or 3"],
            ],
            'invalid_kelas_rawat' => [
                [
                    'nomor_sep' => '0901R0010124A000001',
                    'tgl_masuk' => '2024-01-15 08:00:00',
                    'tgl_pulang' => '2024-01-17 14:00:00',
                    'jenis_rawat' => '1',
                    'kelas_rawat' => '5',
                    'discharge_status' => '1',
                    'diagnosa' => 'A00',
                    'nama_dokter' => 'Dr. Test',
                    'coder_nik' => '1234567890123456',
                ],
                false,
                ["Invalid class (kelas_rawat). Must be 1, 2, or 3"],
            ],
            'invalid_date_format' => [
                [
                    'nomor_sep' => '0901R0010124A000001',
                    'tgl_masuk' => 'invalid-date',
                    'tgl_pulang' => '2024-01-17 14:00:00',
                    'jenis_rawat' => '1',
                    'kelas_rawat' => '2',
                    'discharge_status' => '1',
                    'diagnosa' => 'A00',
                    'nama_dokter' => 'Dr. Test',
                    'coder_nik' => '1234567890123456',
                ],
                false,
                ["Invalid admission date format"],
            ],
        ];
    }

    /**
     * Test claim data validation.
     *
     * @param array<string, mixed> $data
     * @param bool $expectedValid
     * @param array<int, string> $expectedErrors
     */
    #[DataProvider('claimValidationProvider')]
    public function test_validate_claim_data(array $data, bool $expectedValid, array $expectedErrors): void
    {
        $result = $this->service->validateClaimData($data);

        $this->assertEquals($expectedValid, $result['valid']);

        foreach ($expectedErrors as $error) {
            $this->assertContains($error, $result['errors']);
        }
    }

    /**
     * Test validateClaimData with valid gender.
     */
    public function test_validate_claim_data_with_valid_gender(): void
    {
        $data = [
            'nomor_sep' => '0901R0010124A000001',
            'tgl_masuk' => '2024-01-15 08:00:00',
            'tgl_pulang' => '2024-01-17 14:00:00',
            'jenis_rawat' => '1',
            'kelas_rawat' => '2',
            'discharge_status' => '1',
            'diagnosa' => 'A00',
            'nama_dokter' => 'Dr. Test',
            'coder_nik' => '1234567890123456',
            'gender' => '1',
        ];

        $result = $this->service->validateClaimData($data);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validateClaimData with invalid gender.
     */
    public function test_validate_claim_data_with_invalid_gender(): void
    {
        $data = [
            'nomor_sep' => '0901R0010124A000001',
            'tgl_masuk' => '2024-01-15 08:00:00',
            'tgl_pulang' => '2024-01-17 14:00:00',
            'jenis_rawat' => '1',
            'kelas_rawat' => '2',
            'discharge_status' => '1',
            'diagnosa' => 'A00',
            'nama_dokter' => 'Dr. Test',
            'coder_nik' => '1234567890123456',
            'gender' => '3',
        ];

        $result = $this->service->validateClaimData($data);

        $this->assertFalse($result['valid']);
        $this->assertContains("Invalid gender. Must be 1 (Male) or 2 (Female)", $result['errors']);
    }

    /**
     * Test getClaimStatus success.
     */
    public function test_get_claim_status_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode([
                    'status' => 'finalized',
                    'grouping_status' => 'completed',
                    'final_status' => 'approved',
                    'coder_nik' => '1234567890123456',
                    'grouper' => ['code' => 'I-10-01'],
                ]))),
            ], 200),
        ]);

        $result = $this->service->getClaimStatus('0901R0010124A000001');

        $this->assertTrue($result['success']);
        $this->assertEquals('200', $result['code']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('status', $result['data']);
    }

    /**
     * Test getClaimStatus with failed getClaimData.
     */
    public function test_get_claim_status_with_failed_request(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 201, 'message' => 'Claim not found'],
            ], 200),
        ]);

        $result = $this->service->getClaimStatus('INVALID-SEP');

        $this->assertFalse($result['success']);
    }

    /**
     * Test submitCompleteClaim success workflow.
     */
    public function test_submit_complete_claim_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['status' => 'success']))),
            ], 200),
        ]);

        $claimData = [
            'nomor_kartu' => '0001234567890',
            'nomor_sep' => '0901R0010124A000001',
            'nomor_rm' => 'MR001',
            'nama_pasien' => 'John Doe',
            'tgl_lahir' => '1990-01-01',
            'gender' => '1',
            'tgl_masuk' => '2024-01-15 08:00:00',
            'tgl_pulang' => '2024-01-17 14:00:00',
            'jenis_rawat' => '1',
            'kelas_rawat' => '2',
            'discharge_status' => '1',
            'diagnosa' => 'A00',
            'nama_dokter' => 'Dr. Test',
            'coder_nik' => '1234567890123456',
        ];

        $result = $this->service->submitCompleteClaim($claimData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * Test submitCompleteClaim with validation failure.
     */
    public function test_submit_complete_claim_validation_failure(): void
    {
        $invalidData = [
            'nomor_sep' => '0901R0010124A000001',
            // Missing required fields
        ];

        $result = $this->service->submitCompleteClaim($invalidData);

        $this->assertFalse($result['success']);
        $this->assertEquals('VALIDATION_ERROR', $result['code']);
        $this->assertArrayHasKey('errors', $result['data']);
    }

    /**
     * Test submitCompleteClaim with newClaim failure.
     */
    public function test_submit_complete_claim_new_claim_failure(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 201, 'message' => 'Invalid SEP number'],
            ], 200),
        ]);

        $claimData = [
            'nomor_kartu' => '0001234567890',
            'nomor_sep' => 'INVALID',
            'nomor_rm' => 'MR001',
            'nama_pasien' => 'John Doe',
            'tgl_lahir' => '1990-01-01',
            'gender' => '1',
            'tgl_masuk' => '2024-01-15 08:00:00',
            'tgl_pulang' => '2024-01-17 14:00:00',
            'jenis_rawat' => '1',
            'kelas_rawat' => '2',
            'discharge_status' => '1',
            'diagnosa' => 'A00',
            'nama_dokter' => 'Dr. Test',
            'coder_nik' => '1234567890123456',
        ];

        $result = $this->service->submitCompleteClaim($claimData);

        $this->assertFalse($result['success']);
        $this->assertEquals('NEW_CLAIM_FAILED', $result['code']);
    }

    /**
     * Test encryptRequest method.
     */
    public function test_encrypt_request(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('encryptRequest');
        $method->setAccessible(true);

        $data = ['test' => 'data'];
        $encrypted = $method->invoke($this->service, $data);

        $this->assertIsString($encrypted);
        $this->assertNotEmpty($encrypted);

        // Verify it's valid base64
        $decoded = base64_decode($encrypted, true);
        $this->assertNotFalse($decoded);
    }

    /**
     * Test decryptResponse with E-Klaim format.
     */
    public function test_decrypt_response_with_eklaim_format(): void
    {
        $originalData = ['status' => 'success', 'message' => 'Test'];

        // Encrypt the data first
        $reflection = new \ReflectionClass($this->service);
        $encryptMethod = $reflection->getMethod('encryptRequest');
        $encryptMethod->setAccessible(true);
        $encrypted = $encryptMethod->invoke($this->service, $originalData);

        // Now test decrypt
        $result = $this->service->decryptResponse($encrypted);

        $this->assertIsArray($result);
        $this->assertEquals($originalData['status'], $result['status']);
    }

    /**
     * Test decryptResponse with array input.
     */
    public function test_decrypt_response_with_array_input(): void
    {
        $data = ['data' => 'encrypted-string-here'];
        $result = $this->service->decryptResponse($data);

        $this->assertIsArray($result);
    }

    /**
     * Test decryptResponse with non-base64 string.
     */
    public function test_decrypt_response_with_non_base64_string(): void
    {
        $invalidData = '!!!not-valid-base64!!!';
        $result = $this->service->decryptResponse($invalidData);

        $this->assertEquals($invalidData, $result);
    }

    /**
     * Test decryptResponse with invalid gzip data.
     */
    public function test_decrypt_response_with_invalid_gzip(): void
    {
        $invalidCompressed = base64_encode('not-valid-gzip-data');
        $result = $this->service->decryptResponse($invalidCompressed);

        $this->assertEquals($invalidCompressed, $result);
    }

    /**
     * Test makeEklaimRequest method.
     */
    public function test_make_eklaim_request(): void
    {
        Http::fake([
            '*' => Http::response([
                'metadata' => ['code' => 200, 'message' => 'OK'],
                'data' => base64_encode(gzencode(json_encode(['result' => 'success']))),
            ], 200),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('makeEklaimRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'test_action', ['key' => 'value']);

        $this->assertIsArray($result);
    }

    /**
     * Test HTTP error handling.
     */
    public function test_http_error_handling(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = $this->service->newClaim([
            'nomor_kartu' => '0001234567890',
            'nomor_sep' => '0901R0010124A000001',
            'nomor_rm' => 'MR001',
            'nama_pasien' => 'John Doe',
            'tgl_lahir' => '1990-01-01',
            'gender' => '1',
        ]);

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

        $result = $this->service->newClaim([
            'nomor_kartu' => '0001234567890',
            'nomor_sep' => '0901R0010124A000001',
            'nomor_rm' => 'MR001',
            'nama_pasien' => 'John Doe',
            'tgl_lahir' => '1990-01-01',
            'gender' => '1',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('System error', $result['message']);
    }
}
