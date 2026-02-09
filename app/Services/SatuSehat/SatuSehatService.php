<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use Exception;
use InvalidArgumentException;
use App\Models\SatuSehatLog;

/**
 * SatuSehat Service
 * 
 * Base service for SatuSehat (FHIR) API integration.
 * Handles authentication, FHIR requests, and data synchronization.
 * 
 * @package App\Services\SatuSehat
 */

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatuSehatService
{
    protected string $mode;
    protected string $authUrl;
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $organizationId;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;
    protected bool $cacheToken;
    protected string $tokenCacheKey;
    protected int $tokenExpiresIn;

    public function __construct()
    {
        $this->mode = config('satusehat.mode', 'development');
        $config = config("satusehat.{$this->mode}");

        $this->authUrl = $config['auth_url'];
        $this->baseUrl = $config['base_url'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->organizationId = $config['organization_id'];

        $this->timeout = config('satusehat.timeout', 60);
        $this->retryTimes = config('satusehat.retry_times', 3);
        $this->retrySleep = config('satusehat.retry_sleep', 1000);
        $this->cacheToken = config('satusehat.cache_token', true);
        $this->tokenCacheKey = config('satusehat.token_cache_key', 'satusehat_access_token');
        $this->tokenExpiresIn = config('satusehat.token_expires_in', 3500);
    }

    /**
     * Get access token using OAuth2 client credentials flow.
     *
     * @return array{access_token: string, expires_in: int}
     * @throws Exception
     */
    public function getAccessToken(): array
    {
        if ($this->cacheToken) {
            $cachedToken = Cache::get($this->tokenCacheKey);
            if ($cachedToken) {
                return ['access_token' => $cachedToken, 'expires_in' => Cache::get("{$this->tokenCacheKey}_expires", 3600)];
            }
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->asForm()
                ->post("{$this->authUrl}/access_token", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;

                if ($this->cacheToken) {
                    Cache::put($this->tokenCacheKey, $accessToken, $this->tokenExpiresIn);
                    Cache::put("{$this->tokenCacheKey}_expires", $expiresIn, $this->tokenExpiresIn);
                }

                Log::info('SatuSehat access token obtained successfully');

                return [
                    'access_token' => $accessToken,
                    'expires_in' => $expiresIn,
                ];
            }

            $error = $response->json();
            Log::error('Failed to obtain SatuSehat access token', [
                'status' => $response->status(),
                'error' => $error,
            ]);

            throw new Exception('Failed to obtain access token: ' . ($error['error_description'] ?? $response->body()));
        } catch (RequestException $e) {
            Log::error('SatuSehat token request exception', [
                'message' => $e->getMessage(),
                'response' => $e->response?->body(),
            ]);
            throw new Exception('Token request failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh access token.
     *
     * @return array{access_token: string, expires_in: int}
     * @throws Exception
     */
    public function refreshToken(): array
    {
        Cache::forget($this->tokenCacheKey);
        Cache::forget("{$this->tokenCacheKey}_expires");

        return $this->getAccessToken();
    }

    /**
     * Get request headers with Bearer token.
     *
     * @return array<string, string>
     * @throws Exception
     */
    public function getHeaders(): array
    {
        $token = $this->getAccessToken();

        return [
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Base FHIR request method.
     *
     * @param string $resourceType The FHIR resource type
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param array<string, mixed>|null $data Request data
     * @param string|null $resourceId Resource ID for specific operations
     * @return array<string, mixed> Response data
     * @throws Exception
     */
    public function request(string $resourceType, string $method, ?array $data = null, ?string $resourceId = null): array
    {
        $url = $this->buildUrl($resourceType, $resourceId);
        $headers = $this->getHeaders();
        $localType = $data['local_type'] ?? null;
        $localId = $data['local_id'] ?? null;

        // Remove internal tracking fields from data
        unset($data['local_type'], $data['local_id']);

        $log = $this->logRequest($resourceType, $localType, $localId, $method, $data);

        try {
            $send = function (array $requestHeaders) use ($method, $url, $data) {
                $http = Http::timeout($this->timeout)
                    ->retry(
                        $this->retryTimes,
                        $this->retrySleep,
                        function ($exception) {
                            return $exception instanceof ConnectionException;
                        },
                        false
                    )
                    ->withHeaders($requestHeaders);

                return match (strtoupper($method)) {
                    'GET' => $http->get($url),
                    'POST' => $http->post($url, $data),
                    'PUT' => $http->put($url, $data),
                    'PATCH' => $http->patch($url, $data),
                    'DELETE' => $http->delete($url),
                    default => throw new InvalidArgumentException("Unsupported HTTP method: {$method}"),
                };
            };

            $response = $send($headers);

            if ($response->status() === 401) {
                $this->refreshToken();
                $response = $send($this->getHeaders());
            }

            if ($response->successful()) {
                $responseData = $response->json() ?? [];
                $this->logResponse($log, $responseData, 'success');

                return [
                    'success' => true,
                    'data' => $responseData,
                    'status' => $response->status(),
                ];
            }

            $errorData = $response->json();
            $errorMessage = $this->extractErrorMessage($errorData);

            $this->logResponse($log, $errorData, 'failed', $errorMessage);

            return [
                'success' => false,
                'error' => $errorMessage,
                'data' => $errorData,
                'status' => $response->status(),
            ];
        } catch (RequestException $e) {
            $errorMessage = $e->response?->json()['message'] ?? $e->getMessage();
            $this->logResponse($log, ['exception' => $e->getMessage()], 'failed', $errorMessage);

            return [
                'success' => false,
                'error' => $errorMessage,
                'exception' => $e->getMessage(),
                'status' => $e->response?->status() ?? 0,
            ];
        } catch (InvalidArgumentException $e) {
            // Re-throw validation exceptions for unsupported methods
            throw $e;
        } catch (Exception $e) {
            $this->logResponse($log, ['exception' => $e->getMessage()], 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 0,
            ];
        }
    }

    /**
     * Search FHIR resources.
     *
     * @param string $resourceType The FHIR resource type
     * @param array<string, mixed> $params Search parameters
     * @return array<string, mixed> Search results
     */
    public function search(string $resourceType, array $params): array
    {
        $url = "{$this->baseUrl}/{$resourceType}";
        $headers = $this->getHeaders();

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withHeaders($headers)
                ->get($url, $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 0,
            ];
        }
    }

    /**
     * Validate FHIR resource against basic rules.
     *
     * @param string $resourceType The FHIR resource type
     * @param array<string, mixed> $data Resource data
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validateResource(string $resourceType, array $data): array
    {
        $errors = [];

        // Basic FHIR validation
        if (empty($data['resourceType'])) {
            $errors[] = 'Resource type is required';
        } elseif ($data['resourceType'] !== $resourceType) {
            $errors[] = "Resource type must be {$resourceType}";
        }

        // Resource-specific validation
        $errors = array_merge($errors, $this->validateResourceSpecific($resourceType, $data));

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Build FHIR URL.
     *
     * @param string $resourceType
     * @param string|null $resourceId
     * @return string
     */
    protected function buildUrl(string $resourceType, ?string $resourceId = null): string
    {
        $url = "{$this->baseUrl}/{$resourceType}";

        if ($resourceId) {
            $url .= "/{$resourceId}";
        }

        return $url;
    }

    /**
     * Log FHIR request.
     *
     * @param string $resourceType
     * @param string|null $localType
     * @param int|null $localId
     * @param string $action
     * @param array<string, mixed>|null $requestData
     * @return SatuSehatLog
     */
    protected function logRequest(string $resourceType, ?string $localType, ?int $localId, string $action, ?array $requestData = null): SatuSehatLog
    {
        return SatuSehatLog::create([
            'resource_type' => $resourceType,
            'local_type' => $localType,
            'local_id' => $localId,
            'action' => $action,
            'request_data' => $requestData,
            'response_data' => null,
            'status' => 'pending',
            'error_message' => null,
        ]);
    }

    /**
     * Log FHIR response.
     *
     * @param SatuSehatLog $log
     * @param array<string, mixed> $responseData
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    protected function logResponse(SatuSehatLog $log, array $responseData, string $status, ?string $errorMessage = null): void
    {
        $fhirId = $responseData['id'] ?? null;

        $log->update([
            'fhir_id' => $fhirId,
            'response_data' => $responseData,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Extract error message from response.
     *
     * @param array<string, mixed> $errorData
     * @return string
     */
    protected function extractErrorMessage(array $errorData): string
    {
        if (isset($errorData['issue']) && is_array($errorData['issue'])) {
            $messages = [];
            foreach ($errorData['issue'] as $issue) {
                $messages[] = $issue['diagnostics'] ?? $issue['details']['text'] ?? 'Unknown error';
            }
            return implode(', ', $messages);
        }

        return $errorData['message'] ?? json_encode($errorData);
    }

    /**
     * Resource-specific validation.
     *
     * @param string $resourceType
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    protected function validateResourceSpecific(string $resourceType, array $data): array
    {
        $errors = [];

        switch ($resourceType) {
            case 'Patient':
                if (empty($data['identifier']) || !is_array($data['identifier'])) {
                    $errors[] = 'Patient identifier is required';
                }
                if (empty($data['name']) || !is_array($data['name'])) {
                    $errors[] = 'Patient name is required';
                }
                break;

            case 'Encounter':
                if (empty($data['status'])) {
                    $errors[] = 'Encounter status is required';
                }
                if (empty($data['class'])) {
                    $errors[] = 'Encounter class is required';
                }
                if (empty($data['subject'])) {
                    $errors[] = 'Encounter subject (patient) is required';
                }
                break;

            case 'Observation':
                if (empty($data['status'])) {
                    $errors[] = 'Observation status is required';
                }
                if (empty($data['code'])) {
                    $errors[] = 'Observation code is required';
                }
                break;

            case 'Condition':
                if (empty($data['code'])) {
                    $errors[] = 'Condition code is required';
                }
                if (empty($data['subject'])) {
                    $errors[] = 'Condition subject (patient) is required';
                }
                break;

            case 'Medication':
                if (empty($data['code'])) {
                    $errors[] = 'Medication code is required';
                }
                break;

            case 'MedicationRequest':
                if (empty($data['status'])) {
                    $errors[] = 'MedicationRequest status is required';
                }
                if (empty($data['intent'])) {
                    $errors[] = 'MedicationRequest intent is required';
                }
                if (empty($data['medicationCodeableConcept']) && empty($data['medicationReference'])) {
                    $errors[] = 'MedicationRequest medication is required';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get organization ID.
     *
     * @return string
     */
    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    /**
     * Get base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
