<?php

declare(strict_types=1);

namespace App\Services\BPJS;

use InvalidArgumentException;
use Exception;
use DateTimeInterface;
use Throwable;
use App\Models\BpjsLog;

/**
 * BPJS Service Base Class
 * 
 * Abstract base class for all BPJS API integrations.
 * Handles authentication, request signing, and response decryption.
 * 
 * Features:
 * - HMAC SHA256 signature generation
 * - Request/response encryption/decryption
 * - Automatic retry logic
 * - Request logging for audit
 */

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BpjsService
{
    protected string $baseUrl;

    protected string $consId;

    protected string $secretKey;

    protected string $userKey;

    protected string $serviceName;

    protected int $maxRetries = 3;

    protected int $retryDelay = 1000;

    public function __construct()
    {
        $this->initializeConfig();
    }

    /**
     * Build HTTP client lazily so Http::fake() in tests is always respected.
     */
    protected function httpClient(): PendingRequest
    {
        return Http::withOptions([
            'timeout' => 60,
            'connect_timeout' => 10,
        ])->retry(
            times: $this->maxRetries,
            sleepMilliseconds: $this->retryDelay,
            throw: false
        );
    }

    /**
     * Initialize configuration from environment variables.
     */
    abstract protected function initializeConfig(): void;

    /**
     * Generate UTC timestamp for BPJS API.
     */
    public function generateTimestamp(): string
    {
        return (string) time();
    }

    /**
     * Generate HMAC SHA256 signature for BPJS API.
     */
    public function generateSignature(string $timestamp): string
    {
        $data = $this->consId . '&' . $timestamp;
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);

        return base64_encode($signature);
    }

    /**
     * Get request headers for BPJS API.
     */
    public function getHeaders(string $timestamp, string $signature): array
    {
        return [
            'X-cons-id' => $this->consId,
            'X-timestamp' => $timestamp,
            'X-signature' => $signature,
            'user_key' => $this->userKey,
            'Content-Type' => 'application/json; charset=utf-8',
        ];
    }

    /**
     * Base HTTP request method with logging and error handling.
     */
    protected function request(
        string $endpoint,
        string $method = 'GET',
        ?array $data = null,
        ?string $customBaseUrl = null
    ): array {
        $timestamp = $this->generateTimestamp();
        $signature = $this->generateSignature($timestamp);
        $headers = $this->getHeaders($timestamp, $signature);

        $url = rtrim($customBaseUrl ?? $this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $startTime = microtime(true);

        try {
            $request = $this->httpClient()->withHeaders($headers);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $data ?? []),
                'PUT' => $request->put($url, $data ?? []),
                'DELETE' => $request->delete($url, $data ?? []),
                'PATCH' => $request->patch($url, $data ?? []),
                default => throw new InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->handleResponse($response, $endpoint, $method, $data, $executionTime);
        } catch (Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logRequest(
                endpoint: $endpoint,
                method: $method,
                requestData: $data,
                responseData: null,
                httpStatus: 0,
                errorMessage: $e->getMessage(),
                executionTime: $executionTime
            );

            return [
                'success' => false,
                'code' => 'SYSTEM_ERROR',
                'message' => 'System error occurred: ' . $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Handle and standardize API response.
     */
    protected function handleResponse(
        Response $response,
        string $endpoint,
        string $method,
        ?array $requestData,
        float $executionTime
    ): array {
        $httpStatus = $response->status();
        $responseBody = $response->body();
        $responseData = null;
        $errorMessage = null;

        if ($response->successful()) {
            try {
                $decodedResponse = $response->json();

                // BPJS VClaim format
                if (isset($decodedResponse['response'])) {
                    $responseData = $this->decryptResponse($decodedResponse['response']);
                } else {
                    $responseData = $decodedResponse;
                }

                // Support both `metaData` (VClaim/PCare) and `metadata` (E-Klaim) payloads.
                $meta = $decodedResponse['metaData'] ?? $decodedResponse['metadata'] ?? [];
                $responseMeta = is_array($decodedResponse['response'] ?? null) ? $decodedResponse['response'] : [];
                $bpjsCode = $meta['code'] ?? $responseMeta['code'] ?? '200';
                $bpjsMessage = $meta['message'] ?? $responseMeta['message'] ?? 'OK';

                $this->logRequest(
                    endpoint: $endpoint,
                    method: $method,
                    requestData: $requestData,
                    responseData: $decodedResponse,
                    httpStatus: $httpStatus,
                    errorMessage: null,
                    executionTime: $executionTime
                );

                if ($bpjsCode !== '200' && $bpjsCode !== 200) {
                    return [
                        'success' => false,
                        'code' => (string) $bpjsCode,
                        'message' => $bpjsMessage,
                        'data' => $responseData,
                    ];
                }

                return [
                    'success' => true,
                    'code' => (string) $bpjsCode,
                    'message' => $bpjsMessage,
                    'data' => $responseData,
                ];
            } catch (Throwable $e) {
                $errorMessage = 'Failed to parse response: ' . $e->getMessage();
            }
        } else {
            $errorMessage = $this->getErrorMessageFromStatus($httpStatus);

            try {
                $decodedResponse = $response->json();
                if (isset($decodedResponse['metaData']['message'])) {
                    $errorMessage = $decodedResponse['metaData']['message'];
                } elseif (isset($decodedResponse['metadata']['message'])) {
                    $errorMessage = $decodedResponse['metadata']['message'];
                }
            } catch (Throwable) {
                // Use default error message
            }
        }

        $this->logRequest(
            endpoint: $endpoint,
            method: $method,
            requestData: $requestData,
            responseData: $response->json() ?? ['raw' => $responseBody],
            httpStatus: $httpStatus,
            errorMessage: $errorMessage,
            executionTime: $executionTime
        );

        return [
            'success' => false,
            'code' => (string) $httpStatus,
            'message' => $errorMessage ?? 'Unknown error occurred',
            'data' => $response->json() ?? ['raw' => $responseBody],
        ];
    }

    /**
     * Decrypt BPJS encrypted response.
     */
    public function decryptResponse(array|string $response): array|string|null
    {
        if (is_array($response)) {
            if (! isset($response['data'])) {
                return $response;
            }

            $encryptedData = $response['data'];
        } else {
            $encryptedData = $response;
        }

        if (! is_string($encryptedData) || $encryptedData === '') {
            return $response;
        }

        try {
            // BPJS uses a specific encryption method
            // Key generation: cons_id + secret_key + timestamp (as string)
            $key = $this->consId . $this->secretKey . time();
            $key = substr(hash('sha256', $key), 0, 32);

            // Decode base64
            $decoded = base64_decode($encryptedData, true);
            if ($decoded === false) {
                // Data might not be encrypted, return as-is
                return $response;
            }

            // Try to decrypt using AES-256-CBC (common BPJS encryption)
            $iv = str_repeat("\0", 16); // BPJS typically uses zero IV
            $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            if ($decrypted === false) {
                // Decryption failed, return original response
                return $response;
            }

            // Decompress if using LZ-string or similar
            $decompressed = $this->decompress($decrypted);

            $jsonDecoded = json_decode($decompressed, true);

            return $jsonDecoded ?? $decompressed;
        } catch (Throwable $e) {
            Log::warning('BPJS decryption failed', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            // Return original response if decryption fails
            return $response;
        }
    }

    /**
     * Decompress data (BPJS uses various compression methods).
     */
    protected function decompress(string $data): string
    {
        // Try gzip decompression first
        $decompressed = @gzdecode($data);
        if ($decompressed !== false) {
            return $decompressed;
        }

        // Try zlib decompression
        $decompressed = @zlib_decode($data);
        if ($decompressed !== false) {
            return $decompressed;
        }

        // Return as-is if not compressed
        return $data;
    }

    /**
     * Log request to BpjsLog model.
     */
    protected function logRequest(
        string $endpoint,
        string $method,
        ?array $requestData,
        ?array $responseData,
        int $httpStatus,
        ?string $errorMessage,
        float $executionTime
    ): void {
        try {
            BpjsLog::create([
                'service_type' => $this->serviceName,
                'endpoint' => $endpoint,
                'method' => $method,
                'request_data' => $requestData ? Crypt::encryptString(json_encode($requestData)) : null,
                'response_data' => $responseData ? Crypt::encryptString(json_encode($responseData)) : null,
                'http_status' => $httpStatus,
                'error_message' => $errorMessage,
                'execution_time_ms' => $executionTime,
                'user_id' => auth()->id(),
                'executed_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log BPJS request', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);
        }
    }

    /**
     * Get error message from HTTP status code.
     */
    protected function getErrorMessageFromStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request - Invalid parameters',
            401 => 'Unauthorized - Invalid credentials',
            403 => 'Forbidden - Access denied',
            404 => 'Not Found - Resource not found',
            408 => 'Request Timeout - Server took too long',
            429 => 'Too Many Requests - Rate limit exceeded',
            500 => 'Internal Server Error - BPJS server error',
            502 => 'Bad Gateway - Invalid response from upstream',
            503 => 'Service Unavailable - BPJS service down',
            504 => 'Gateway Timeout - BPJS server timeout',
            default => "HTTP Error {$status}",
        };
    }

    /**
     * Format date to BPJS standard (Y-m-d).
     */
    protected function formatDate(string|DateTimeInterface $date): string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (ctype_digit($date)) {
            return date('Y-m-d', (int) $date);
        }

        $slashFormat = \DateTime::createFromFormat('d/m/Y', $date);
        if ($slashFormat instanceof \DateTime) {
            return $slashFormat->format('Y-m-d');
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            throw new \InvalidArgumentException("Invalid date format: {$date}");
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Get service configuration value.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return config("bpjs.{$this->serviceName}.{$key}", $default);
    }
}
