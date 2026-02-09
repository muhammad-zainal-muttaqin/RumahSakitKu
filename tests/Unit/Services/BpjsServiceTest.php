<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BPJS\BpjsService;
use Illuminate\Http\Client\PendingRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Test class for BpjsService base functionality.
 *
 * Tests signature generation, timestamp generation, header creation,
 * and common BPJS service methods.
 */
class BpjsServiceTest extends TestCase
{
    /**
     * Concrete implementation of BpjsService for testing.
     */
    private function createConcreteService(): BpjsService
    {
        return new class extends BpjsService {
            protected string $serviceName = 'test';

            protected function initializeConfig(): void
            {
                $this->baseUrl = 'https://test.bpjs.go.id/api';
                $this->consId = 'test-cons-id';
                $this->secretKey = 'test-secret-key';
                $this->userKey = 'test-user-key';
            }
        };
    }

    /**
     * Test timestamp generation returns valid Unix timestamp.
     */
    public function test_generate_timestamp_returns_valid_unix_timestamp(): void
    {
        $service = $this->createConcreteService();

        $before = time();
        $timestamp = $service->generateTimestamp();
        $after = time();

        $this->assertIsString($timestamp);
        $this->assertGreaterThanOrEqual($before, (int) $timestamp);
        $this->assertLessThanOrEqual($after, (int) $timestamp);
    }

    /**
     * Test signature generation produces valid HMAC SHA256 signature.
     */
    public function test_generate_signature_produces_valid_hmac_sha256(): void
    {
        $service = $this->createConcreteService();

        $timestamp = '1234567890';
        $signature = $service->generateSignature($timestamp);

        $this->assertIsString($signature);
        $this->assertNotEmpty($signature);

        // Verify it's valid base64
        $decoded = base64_decode($signature, true);
        $this->assertNotFalse($decoded);

        // Verify signature length (SHA256 = 32 bytes, base64 encoded = 44 chars)
        $this->assertEquals(44, strlen($signature));
    }

    /**
     * Test signature is deterministic for same inputs.
     */
    public function test_signature_is_deterministic_for_same_inputs(): void
    {
        $service = $this->createConcreteService();

        $timestamp = '1234567890';
        $signature1 = $service->generateSignature($timestamp);
        $signature2 = $service->generateSignature($timestamp);

        $this->assertEquals($signature1, $signature2);
    }

    /**
     * Test signature differs for different timestamps.
     */
    public function test_signature_differs_for_different_timestamps(): void
    {
        $service = $this->createConcreteService();

        $signature1 = $service->generateSignature('1234567890');
        $signature2 = $service->generateSignature('0987654321');

        $this->assertNotEquals($signature1, $signature2);
    }

    /**
     * Test headers generation includes all required fields.
     */
    public function test_get_headers_includes_all_required_fields(): void
    {
        $service = $this->createConcreteService();

        $timestamp = '1234567890';
        $signature = $service->generateSignature($timestamp);
        $headers = $service->getHeaders($timestamp, $signature);

        $this->assertArrayHasKey('X-cons-id', $headers);
        $this->assertArrayHasKey('X-timestamp', $headers);
        $this->assertArrayHasKey('X-signature', $headers);
        $this->assertArrayHasKey('user_key', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);

        $this->assertEquals('test-cons-id', $headers['X-cons-id']);
        $this->assertEquals($timestamp, $headers['X-timestamp']);
        $this->assertEquals($signature, $headers['X-signature']);
        $this->assertEquals('test-user-key', $headers['user_key']);
        $this->assertEquals('application/json; charset=utf-8', $headers['Content-Type']);
    }

    /**
     * Data provider for HTTP status code tests.
     *
     * @return array<string, array{int, string}>
     */
    public static function httpStatusCodeProvider(): array
    {
        return [
            'bad_request' => [400, 'Bad Request - Invalid parameters'],
            'unauthorized' => [401, 'Unauthorized - Invalid credentials'],
            'forbidden' => [403, 'Forbidden - Access denied'],
            'not_found' => [404, 'Not Found - Resource not found'],
            'request_timeout' => [408, 'Request Timeout - Server took too long'],
            'too_many_requests' => [429, 'Too Many Requests - Rate limit exceeded'],
            'internal_server_error' => [500, 'Internal Server Error - BPJS server error'],
            'bad_gateway' => [502, 'Bad Gateway - Invalid response from upstream'],
            'service_unavailable' => [503, 'Service Unavailable - BPJS service down'],
            'gateway_timeout' => [504, 'Gateway Timeout - BPJS server timeout'],
            'unknown_error' => [999, 'HTTP Error 999'],
        ];
    }

    /**
     * Test error message mapping for various HTTP status codes.
     *
     * @param int $statusCode HTTP status code
     * @param string $expectedMessage Expected error message
     */
    #[DataProvider('httpStatusCodeProvider')]
    public function test_error_message_mapping(int $statusCode, string $expectedMessage): void
    {
        $service = $this->createConcreteService();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getErrorMessageFromStatus');
        $method->setAccessible(true);

        $result = $method->invoke($service, $statusCode);

        $this->assertEquals($expectedMessage, $result);
    }

    /**
     * Test decrypt response handles non-encrypted data.
     */
    public function test_decrypt_response_handles_non_encrypted_data(): void
    {
        $service = $this->createConcreteService();

        $plainData = ['key' => 'value'];
        $result = $service->decryptResponse($plainData);

        $this->assertEquals($plainData, $result);
    }

    /**
     * Test decrypt response handles string input.
     */
    public function test_decrypt_response_handles_string_input(): void
    {
        $service = $this->createConcreteService();

        $plainString = 'plain text response';
        $result = $service->decryptResponse($plainString);

        // Non-base64 string should be returned as-is
        $this->assertEquals($plainString, $result);
    }

    /**
     * Test decrypt response handles invalid base64.
     */
    public function test_decrypt_response_handles_invalid_base64(): void
    {
        $service = $this->createConcreteService();

        $invalidBase64 = '!!!invalid-base64!!!';
        $result = $service->decryptResponse(['data' => $invalidBase64]);

        // Should return original response when decryption fails
        $this->assertEquals(['data' => $invalidBase64], $result);
    }

    /**
     * Test format date with various inputs.
     *
     * @return array<string, array{string|\DateTimeInterface, string}>
     */
    public static function dateFormatProvider(): array
    {
        return [
            'date_string' => ['2024-01-15', '2024-01-15'],
            'datetime_object' => [new \DateTime('2024-01-15'), '2024-01-15'],
            'datetime_immutable' => [new \DateTimeImmutable('2024-01-15'), '2024-01-15'],
            'different_format' => ['15/01/2024', '2024-01-15'],
            'timestamp' => ['1705276800', date('Y-m-d', 1705276800)],
        ];
    }

    /**
     * Test date formatting with various input types.
     *
     * @param string|\DateTimeInterface $input
     * @param string $expected
     */
    #[DataProvider('dateFormatProvider')]
    public function test_format_date_with_various_inputs(string|\DateTimeInterface $input, string $expected): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('formatDate');
        $method->setAccessible(true);

        $result = $method->invoke($service, $input);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test get config method.
     */
    public function test_get_config_method(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getConfig');
        $method->setAccessible(true);

        // This will return default since config is not set in testing
        $result = $method->invoke($service, 'test_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    /**
     * Test decompress method with various compression types.
     */
    public function test_decompress_handles_uncompressed_data(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('decompress');
        $method->setAccessible(true);

        $uncompressed = 'uncompressed data';
        $result = $method->invoke($service, $uncompressed);

        $this->assertEquals($uncompressed, $result);
    }

    /**
     * Test decompress method with gzip compressed data.
     */
    public function test_decompress_handles_gzip_data(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('decompress');
        $method->setAccessible(true);

        $original = 'compressed data for testing';
        $compressed = gzencode($original);

        $result = $method->invoke($service, $compressed);

        $this->assertEquals($original, $result);
    }

    /**
     * Test decompress method with zlib compressed data.
     */
    public function test_decompress_handles_zlib_data(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('decompress');
        $method->setAccessible(true);

        $original = 'zlib compressed data';
        $compressed = zlib_encode($original, ZLIB_ENCODING_DEFLATE);

        $result = $method->invoke($service, $compressed);

        $this->assertEquals($original, $result);
    }

    /**
     * Test HTTP client configuration.
     */
    public function test_http_client_configuration(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('httpClient');
        $method->setAccessible(true);
        $httpClient = $method->invoke($service);

        $this->assertInstanceOf(PendingRequest::class, $httpClient);
    }

    /**
     * Test service name property.
     */
    public function test_service_name_property(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('serviceName');
        $property->setAccessible(true);

        $this->assertEquals('test', $property->getValue($service));
    }

    /**
     * Test retry configuration.
     */
    public function test_retry_configuration(): void
    {
        $service = $this->createConcreteService();

        $reflection = new \ReflectionClass($service);
        $maxRetries = $reflection->getProperty('maxRetries');
        $maxRetries->setAccessible(true);
        $retryDelay = $reflection->getProperty('retryDelay');
        $retryDelay->setAccessible(true);

        $this->assertEquals(3, $maxRetries->getValue($service));
        $this->assertEquals(1000, $retryDelay->getValue($service));
    }

    /**
     * Test signature generation with different configurations.
     */
    public function test_signature_with_different_configurations(): void
    {
        $service1 = new class extends BpjsService {
            protected string $serviceName = 'test1';
            protected function initializeConfig(): void
            {
                $this->consId = 'cons1';
                $this->secretKey = 'secret1';
            }
        };

        $service2 = new class extends BpjsService {
            protected string $serviceName = 'test2';
            protected function initializeConfig(): void
            {
                $this->consId = 'cons2';
                $this->secretKey = 'secret2';
            }
        };

        $timestamp = '1234567890';
        $signature1 = $service1->generateSignature($timestamp);
        $signature2 = $service2->generateSignature($timestamp);

        // Signatures should be different for different configurations
        $this->assertNotEquals($signature1, $signature2);
    }
}
