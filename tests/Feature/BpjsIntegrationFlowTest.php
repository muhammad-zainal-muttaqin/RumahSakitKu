<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BpjsLog;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use App\Services\BPJS\VClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BpjsIntegrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $registrationUser;
    protected User $adminUser;
    protected Patient $patient;
    protected Polyclinic $polyclinic;
    protected Employee $doctor;
    protected VClaimService $vClaimService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);

        // Create polyclinic with BPJS code
        $this->polyclinic = Polyclinic::factory()->create([
            'name' => 'Poli Umum',
            'bpjs_poli_code' => '001',
            'bpjs_poli_name' => 'Poliklinik Umum',
            'is_active' => true,
        ]);

        // Create doctor
        $this->doctor = Employee::factory()->create([
            'employee_type' => 'tetap',
            'is_doctor' => true,
            'specialist_polyclinic_id' => $this->polyclinic->id,
            'status' => 'aktif',
        ]);

        // Create users
        $this->registrationUser = User::factory()->create(['is_active' => true]);
        $this->registrationUser->assignRole('registration');

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        // Create BPJS patient
        $this->patient = Patient::factory()->create([
            'name' => 'Budi Santoso',
            'nik' => '3175091234567890',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'insurance_type' => 'bpjs',
            'bpjs_card_number' => '0001234567890',
        ]);

        // Mock HTTP facade
        Http::preventStrayRequests();
    }

    /**
     * Test can check BPJS peserta by NIK.
     */
    public function test_can_check_bpjs_peserta_by_nik(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'peserta' => [
                        'noKartu' => '0001234567890',
                        'nik' => '3175091234567890',
                        'nama' => 'Budi Santoso',
                        'tglLahir' => '1990-05-15',
                        'jenisKelamin' => 'L',
                        'statusPeserta' => [
                            'kode' => '0',
                            'keterangan' => 'AKTIF',
                        ],
                        'jenisPeserta' => [
                            'kode' => '1',
                            'keterangan' => 'PBI',
                        ],
                        'hakKelas' => [
                            'kode' => '1',
                            'keterangan' => 'Kelas 1',
                        ],
                        'umur' => [
                            'umurSekarang' => '33 Tahun 4 Bulan',
                            'umurSaatPelayanan' => '33 Tahun 4 Bulan',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'peserta' => [
                    'nik' => '3175091234567890',
                    'nama' => 'Budi Santoso',
                ],
            ],
        ]);
    }

    /**
     * Test BPJS peserta check returns inactive status.
     */
    public function test_bpjs_peserta_check_returns_inactive_status(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'peserta' => [
                        'noKartu' => '0001234567890',
                        'nik' => '3175091234567890',
                        'nama' => 'Budi Santoso',
                        'statusPeserta' => [
                            'kode' => '4',
                            'keterangan' => 'TIDAK AKTIF',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');

        $response->assertStatus(200);
        $response->assertJsonPath('data.peserta.statusPeserta.keterangan', 'TIDAK AKTIF');
    }

    /**
     * Test BPJS peserta check handles error response.
     */
    public function test_bpjs_peserta_check_handles_error_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '201',
                    'message' => 'Peserta tidak ditemukan',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=0000000000000000');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'Peserta tidak ditemukan',
        ]);
    }

    /**
     * Test can generate SEP for visit.
     */
    public function test_can_generate_sep_for_visit(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now(),
            'visit_type' => 'rawat_jalan',
            'registration_type' => 'bpjs',
        ]);

        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'sep' => [
                        'noSep' => '0123R0010124V000001',
                        'tglSep' => now()->format('Y-m-d'),
                        'noKartu' => '0001234567890',
                        'nama' => 'Budi Santoso',
                        'poli' => 'Poliklinik Umum',
                        'poliEksekutif' => '0',
                        'diagnosa' => 'Demam',
                        'kodeDiagnosa' => 'R50.9',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->post("/admin/bpjs/visits/{$visit->id}/generate-sep", [
                'noKartu' => '0001234567890',
                'tglSep' => now()->format('Y-m-d'),
                'ppkPelayanan' => '0123R001',
                'jnsPelayanan' => '2',
                'klsRawat' => '1',
                'noMR' => $this->patient->medical_record_number,
                'rujukan' => [
                    'asalRujukan' => '1',
                    'tglRujukan' => now()->subDay()->format('Y-m-d'),
                    'noRujukan' => '123456',
                    'ppkRujukan' => '0123R002',
                ],
                'catatan' => 'Pasien demam',
                'diagAwal' => 'R50.9',
                'poli' => [
                    'tujuan' => '001',
                    'eksekutif' => '0',
                ],
                'cob' => [
                    'cob' => '0',
                ],
                'katarak' => [
                    'katarak' => '0',
                ],
                'jaminan' => [
                    'lakaLantas' => '0',
                ],
                'skdp' => [
                    'noSurat' => 'SKDP001',
                    'kodeDPJP' => '123456',
                ],
                'noTelp' => '081234567890',
                'user' => $this->registrationUser->name,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('0123R0010124V000001', $visit->bpjs_sep_number);
    }

    /**
     * Test SEP generation logs to BpjsLog.
     */
    public function test_sep_generation_logs_to_bpjs_log(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);

        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'sep' => [
                        'noSep' => '0123R0010124V000002',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->registrationUser)
            ->post("/admin/bpjs/visits/{$visit->id}/generate-sep", [
                'noKartu' => '0001234567890',
                'tglSep' => now()->format('Y-m-d'),
                'ppkPelayanan' => '0123R001',
                'jnsPelayanan' => '2',
                'klsRawat' => '1',
                'noMR' => $this->patient->medical_record_number,
                'diagAwal' => 'R50.9',
                'poli' => ['tujuan' => '001', 'eksekutif' => '0'],
                'cob' => ['cob' => '0'],
                'katarak' => ['katarak' => '0'],
                'jaminan' => ['lakaLantas' => '0'],
                'skdp' => ['noSurat' => 'SKDP001', 'kodeDPJP' => '123456'],
                'noTelp' => '081234567890',
                'user' => $this->registrationUser->name,
            ]);

        $this->assertDatabaseHas('bpjs_logs', [
            'service_type' => 'vclaim',
            'endpoint' => 'SEP/1.1/insert',
            'method' => 'POST',
            'http_status' => 200,
        ]);
    }

    /**
     * Test can verify SEP data.
     */
    public function test_can_verify_sep_data(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'bpjs_sep_number' => '0123R0010124V000001',
        ]);

        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'sep' => [
                        'noSep' => '0123R0010124V000001',
                        'tglSep' => now()->format('Y-m-d'),
                        'noKartu' => '0001234567890',
                        'nama' => 'Budi Santoso',
                        'poli' => 'Poliklinik Umum',
                        'diagnosa' => 'Demam',
                        'kodeDiagnosa' => 'R50.9',
                        'status' => 'Aktif',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get("/admin/bpjs/sep/0123R0010124V000001");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'sep' => [
                    'noSep' => '0123R0010124V000001',
                    'nama' => 'Budi Santoso',
                ],
            ],
        ]);
    }

    /**
     * Test SEP verification returns correct patient data.
     */
    public function test_sep_verification_returns_correct_patient_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'sep' => [
                        'noSep' => '0123R0010124V000001',
                        'noKartu' => '0001234567890',
                        'nama' => 'Budi Santoso',
                        'tglLahir' => '1990-05-15',
                        'jenisKelamin' => 'L',
                        'poli' => 'Poliklinik Umum',
                        'diagnosa' => 'Demam',
                        'kodeDiagnosa' => 'R50.9',
                        'noMR' => $this->patient->medical_record_number,
                        'tglSep' => now()->format('Y-m-d'),
                        'ppkPelayanan' => '0123R001',
                        'klsRawat' => 'Kelas 1',
                        'status' => 'Aktif',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/sep/0123R0010124V000001');

        $response->assertStatus(200);
        $response->assertJsonPath('data.sep.noKartu', '0001234567890');
        $response->assertJsonPath('data.sep.nama', 'Budi Santoso');
        $response->assertJsonPath('data.sep.noMR', $this->patient->medical_record_number);
    }

    /**
     * Test can delete SEP.
     */
    public function test_can_delete_sep(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'bpjs_sep_number' => '0123R0010124V000001',
        ]);

        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/bpjs/sep/0123R0010124V000001", [
                'reason' => 'Kesalahan input data',
            ]);

        $response->assertRedirect();

        $visit->refresh();
        $this->assertNull($visit->bpjs_sep_number);
    }

    /**
     * Test BPJS API error handling.
     */
    public function test_bpjs_api_error_handling(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '500',
                    'message' => 'Internal Server Error',
                ],
                'response' => null,
            ], 500),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'code' => '500',
        ]);
    }

    /**
     * Test BPJS API timeout handling.
     */
    public function test_bpjs_api_timeout_handling(): void
    {
        Http::fake([
            '*' => Http::timeout(),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test can check BPJS rujukan.
     */
    public function test_can_check_bpjs_rujukan(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'rujukan' => [
                        'noRujukan' => '123456',
                        'tglRujukan' => now()->subDay()->format('Y-m-d'),
                        'noKartu' => '0001234567890',
                        'nama' => 'Budi Santoso',
                        'poliRujukan' => 'Poliklinik Umum',
                        'diagnosa' => 'Demam',
                        'kodeDiagnosa' => 'R50.9',
                        'ppkRujukan' => '0123R002',
                        'namaPpkRujukan' => 'Puskesmas Sehat',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/rujukan/123456');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'rujukan' => [
                    'noRujukan' => '123456',
                    'nama' => 'Budi Santoso',
                ],
            ],
        ]);
    }

    /**
     * Test can check BPJS history.
     */
    public function test_can_check_bpjs_history(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'histori' => [
                        [
                            'noSep' => '0123R0010124V000001',
                            'tglSep' => now()->subMonth()->format('Y-m-d'),
                            'poli' => 'Poliklinik Umum',
                            'diagnosa' => 'Demam',
                        ],
                        [
                            'noSep' => '0123R0010123V000099',
                            'tglSep' => now()->subMonths(2)->format('Y-m-d'),
                            'poli' => 'Poliklinik Gigi',
                            'diagnosa' => 'Gigi berlubang',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/history?noKartu=0001234567890');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.histori');
    }

    /**
     * Test BPJS request is logged.
     */
    public function test_bpjs_request_is_logged(): void
    {
        Http::fake([
            '*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'OK',
                ],
                'response' => [
                    'peserta' => [
                        'noKartu' => '0001234567890',
                        'nik' => '3175091234567890',
                        'nama' => 'Budi Santoso',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');

        $this->assertDatabaseHas('bpjs_logs', [
            'service_type' => 'vclaim',
            'endpoint' => 'Peserta/nik/3175091234567890',
            'method' => 'GET',
            'http_status' => 200,
            'user_id' => $this->registrationUser->id,
        ]);
    }

    /**
     * Test BPJS signature generation.
     */
    public function test_bpjs_signature_generation(): void
    {
        $service = new class extends \App\Services\BPJS\BpjsService {
            protected function initializeConfig(): void
            {
                $this->consId = 'test-cons-id';
                $this->secretKey = 'test-secret-key';
                $this->baseUrl = 'https://api.bpjs.go.id';
                $this->serviceName = 'vclaim';
            }

            public function testGenerateSignature(string $timestamp): string
            {
                return $this->generateSignature($timestamp);
            }
        };

        $timestamp = (string) time();
        $signature = $service->testGenerateSignature($timestamp);

        $this->assertNotEmpty($signature);
        $this->assertIsString($signature);

        // Verify signature format (base64 encoded HMAC)
        $decoded = base64_decode($signature, true);
        $this->assertNotFalse($decoded);
    }

    /**
     * Test BPJS timestamp generation.
     */
    public function test_bpjs_timestamp_generation(): void
    {
        $service = new class extends \App\Services\BPJS\BpjsService {
            protected function initializeConfig(): void
            {
                $this->consId = 'test-cons-id';
                $this->secretKey = 'test-secret-key';
                $this->baseUrl = 'https://api.bpjs.go.id';
                $this->serviceName = 'vclaim';
            }

            public function testGenerateTimestamp(): string
            {
                return $this->generateTimestamp();
            }
        };

        $before = time();
        $timestamp = $service->testGenerateTimestamp();
        $after = time();

        $this->assertIsString($timestamp);
        $this->assertGreaterThanOrEqual($before, (int) $timestamp);
        $this->assertLessThanOrEqual($after, (int) $timestamp);
    }

    /**
     * Test BPJS headers generation.
     */
    public function test_bpjs_headers_generation(): void
    {
        $service = new class extends \App\Services\BPJS\BpjsService {
            protected function initializeConfig(): void
            {
                $this->consId = 'test-cons-id';
                $this->secretKey = 'test-secret-key';
                $this->userKey = 'test-user-key';
                $this->baseUrl = 'https://api.bpjs.go.id';
                $this->serviceName = 'vclaim';
            }

            public function testGetHeaders(string $timestamp, string $signature): array
            {
                return $this->getHeaders($timestamp, $signature);
            }
        };

        $timestamp = (string) time();
        $signature = 'test-signature';
        $headers = $service->testGetHeaders($timestamp, $signature);

        $this->assertArrayHasKey('X-cons-id', $headers);
        $this->assertArrayHasKey('X-timestamp', $headers);
        $this->assertArrayHasKey('X-signature', $headers);
        $this->assertArrayHasKey('user_key', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);

        $this->assertEquals('test-cons-id', $headers['X-cons-id']);
        $this->assertEquals($timestamp, $headers['X-timestamp']);
        $this->assertEquals($signature, $headers['X-signature']);
        $this->assertEquals('test-user-key', $headers['user_key']);
    }

    /**
     * Test complete BPJS workflow.
     */
    public function test_complete_bpjs_workflow(): void
    {
        // 1. Check peserta
        Http::fake([
            '*/Peserta/nik/3175091234567890' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'peserta' => [
                        'noKartu' => '0001234567890',
                        'nik' => '3175091234567890',
                        'nama' => 'Budi Santoso',
                        'statusPeserta' => ['kode' => '0', 'keterangan' => 'AKTIF'],
                        'hakKelas' => ['kode' => '1', 'keterangan' => 'Kelas 1'],
                    ],
                ],
            ], 200),
        ]);

        $checkResponse = $this->actingAs($this->registrationUser)
            ->get('/admin/bpjs/check-peserta?nik=3175091234567890');
        $checkResponse->assertStatus(200);
        $checkResponse->assertJson(['success' => true]);

        // 2. Create visit
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'visit_date' => now(),
            'visit_type' => 'rawat_jalan',
            'registration_type' => 'bpjs',
        ]);

        // 3. Generate SEP
        Http::fake([
            '*/SEP/1.1/insert' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => [
                    'sep' => [
                        'noSep' => '0123R0010124V000001',
                        'tglSep' => now()->format('Y-m-d'),
                        'noKartu' => '0001234567890',
                        'nama' => 'Budi Santoso',
                    ],
                ],
            ], 200),
        ]);

        $sepResponse = $this->actingAs($this->registrationUser)
            ->post("/admin/bpjs/visits/{$visit->id}/generate-sep", [
                'noKartu' => '0001234567890',
                'tglSep' => now()->format('Y-m-d'),
                'ppkPelayanan' => '0123R001',
                'jnsPelayanan' => '2',
                'klsRawat' => '1',
                'noMR' => $this->patient->medical_record_number,
                'diagAwal' => 'R50.9',
                'poli' => ['tujuan' => '001', 'eksekutif' => '0'],
                'cob' => ['cob' => '0'],
                'katarak' => ['katarak' => '0'],
                'jaminan' => ['lakaLantas' => '0'],
                'skdp' => ['noSurat' => 'SKDP001', 'kodeDPJP' => '123456'],
                'noTelp' => '081234567890',
                'user' => $this->registrationUser->name,
            ]);
        $sepResponse->assertRedirect();

        // 4. Verify SEP saved
        $visit->refresh();
        $this->assertNotNull($visit->bpjs_sep_number);
        $this->assertStringStartsWith('0123', $visit->bpjs_sep_number);

        // 5. Verify log created
        $this->assertDatabaseHas('bpjs_logs', [
            'service_type' => 'vclaim',
            'http_status' => 200,
        ]);
    }
}
