<?php

declare(strict_types=1);

namespace App\Services\SatuSehat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SatuSehatOrganizationService
{
    protected SatuSehatService $satuSehat;

    public function __construct(SatuSehatService $satuSehat)
    {
        $this->satuSehat = $satuSehat;
    }

    /**
     * Get organization details from SatuSehat.
     *
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getOrganization(): array
    {
        $organizationId = $this->satuSehat->getOrganizationId();
        $cacheKey = "satusehat_organization_{$organizationId}";

        // Check cache first
        if (Cache::has($cacheKey)) {
            return [
                'success' => true,
                'data' => Cache::get($cacheKey),
                'error' => null,
            ];
        }

        $result = $this->satuSehat->request('Organization', 'GET', null, $organizationId);

        if ($result['success']) {
            // Cache for 24 hours
            Cache::put($cacheKey, $result['data'], now()->addHours(24));
        }

        return $result;
    }

    /**
     * Get location by ID.
     *
     * @param string $locationId
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getLocation(string $locationId): array
    {
        $cacheKey = "satusehat_location_{$locationId}";

        // Check cache first
        if (Cache::has($cacheKey)) {
            return [
                'success' => true,
                'data' => Cache::get($cacheKey),
                'error' => null,
            ];
        }

        $result = $this->satuSehat->request('Location', 'GET', null, $locationId);

        if ($result['success']) {
            // Cache for 24 hours
            Cache::put($cacheKey, $result['data'], now()->addHours(24));
        }

        return $result;
    }

    /**
     * Search locations by organization.
     *
     * @param string|null $organizationId
     * @param array<string, mixed> $additionalParams
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchLocationsByOrganization(?string $organizationId = null, array $additionalParams = []): array
    {
        $orgId = $organizationId ?? $this->satuSehat->getOrganizationId();

        $params = array_merge([
            'organization' => $orgId,
        ], $additionalParams);

        return $this->satuSehat->search('Location', $params);
    }

    /**
     * Search locations by name.
     *
     * @param string $name
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function searchLocationsByName(string $name): array
    {
        return $this->satuSehat->search('Location', [
            'name' => $name,
        ]);
    }

    /**
     * Get all locations for the organization.
     *
     * @return array{success: bool, locations: array, error: string|null}
     */
    public function getAllLocations(): array
    {
        $result = $this->searchLocationsByOrganization();

        if (!$result['success']) {
            return [
                'success' => false,
                'locations' => [],
                'error' => 'Failed to fetch locations' . (!empty($result['error']) ? ': ' . $result['error'] : ''),
            ];
        }

        $locations = $result['data']['entry'] ?? [];

        // Extract resource from each entry
        $formattedLocations = [];
        foreach ($locations as $entry) {
            if (isset($entry['resource'])) {
                $formattedLocations[] = $entry['resource'];
            }
        }

        return [
            'success' => true,
            'locations' => $formattedLocations,
            'error' => null,
        ];
    }

    /**
     * Get location by identifier.
     *
     * @param string $identifier
     * @param string $system
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function getLocationByIdentifier(string $identifier, string $system = 'http://sys-ids.kemkes.go.id/location'): array
    {
        $result = $this->satuSehat->search('Location', [
            'identifier' => $system . '|' . $identifier,
        ]);

        if (!$result['success']) {
            return $result;
        }

        $entries = $result['data']['entry'] ?? [];

        if (empty($entries)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Location not found',
            ];
        }

        return [
            'success' => true,
            'data' => $entries[0]['resource'],
            'error' => null,
        ];
    }

    /**
     * Create a new location.
     *
     * @param array<string, mixed> $locationData
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function createLocation(array $locationData): array
    {
        $fhirLocation = $this->buildLocationResource($locationData);

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Location', $fhirLocation);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $result = $this->satuSehat->request('Location', 'POST', $fhirLocation);

        if ($result['success']) {
            Log::info('Location created in SatuSehat', [
                'location_name' => $locationData['name'] ?? 'Unknown',
                'fhir_id' => $result['data']['id'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Update an existing location.
     *
     * @param string $locationId
     * @param array<string, mixed> $locationData
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateLocation(string $locationId, array $locationData): array
    {
        $fhirLocation = $this->buildLocationResource($locationData);
        $fhirLocation['id'] = $locationId;

        // Validate resource before sending
        $validation = $this->satuSehat->validateResource('Location', $fhirLocation);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Validation failed: ' . implode(', ', $validation['errors']),
            ];
        }

        $result = $this->satuSehat->request('Location', 'PUT', $fhirLocation, $locationId);

        if ($result['success']) {
            // Clear cache
            Cache::forget("satusehat_location_{$locationId}");

            Log::info('Location updated in SatuSehat', [
                'location_id' => $locationId,
                'location_name' => $locationData['name'] ?? 'Unknown',
            ]);
        }

        return $result;
    }

    /**
     * Update location status.
     *
     * @param string $locationId
     * @param string $status 'active', 'suspended', or 'inactive'
     * @return array{success: bool, data: array|null, error: string|null}
     */
    public function updateLocationStatus(string $locationId, string $status): array
    {
        $validStatuses = ['active', 'suspended', 'inactive'];

        if (!in_array($status, $validStatuses, true)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
            ];
        }

        // First get the current location
        $currentResult = $this->getLocation($locationId);

        if (!$currentResult['success']) {
            return $currentResult;
        }

        $fhirLocation = $currentResult['data'];
        $fhirLocation['status'] = $status;

        $result = $this->satuSehat->request('Location', 'PUT', $fhirLocation, $locationId);

        if ($result['success']) {
            // Clear cache
            Cache::forget("satusehat_location_{$locationId}");

            Log::info('Location status updated', [
                'location_id' => $locationId,
                'new_status' => $status,
            ]);
        }

        return $result;
    }

    /**
     * Build FHIR Location resource.
     *
     * @param array<string, mixed> $locationData
     * @return array<string, mixed>
     */
    protected function buildLocationResource(array $locationData): array
    {
        $resource = [
            'resourceType' => 'Location',
            'status' => $locationData['status'] ?? 'active',
            'name' => $locationData['name'],
            'mode' => $locationData['mode'] ?? 'instance',
        ];

        // Add identifier if available
        if (!empty($locationData['identifier'])) {
            $resource['identifier'] = [
                [
                    'system' => $locationData['identifier']['system'] ?? 'http://sys-ids.kemkes.go.id/location',
                    'value' => $locationData['identifier']['value'],
                ],
            ];
        }

        // Add description if available
        if (!empty($locationData['description'])) {
            $resource['description'] = $locationData['description'];
        }

        // Add type if available
        if (!empty($locationData['type'])) {
            $resource['type'] = [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/v3-RoleCode',
                            'code' => $this->mapLocationType($locationData['type']),
                            'display' => $locationData['type'],
                        ],
                    ],
                ],
            ];
        }

        // Add telecom if available
        if (!empty($locationData['telecom'])) {
            $resource['telecom'] = [];
            foreach ($locationData['telecom'] as $telecom) {
                $resource['telecom'][] = [
                    'system' => $telecom['system'],
                    'value' => $telecom['value'],
                    'use' => $telecom['use'] ?? 'work',
                ];
            }
        }

        // Add address if available
        if (!empty($locationData['address'])) {
            $resource['address'] = [
                'use' => 'work',
                'type' => 'both',
                'text' => $locationData['address'],
                'country' => 'ID',
            ];
        }

        // Add managing organization
        $resource['managingOrganization'] = [
            'reference' => 'Organization/' . $this->satuSehat->getOrganizationId(),
        ];

        // Add hours of operation if available
        if (!empty($locationData['hours_of_operation'])) {
            $resource['hoursOfOperation'] = [];
            foreach ($locationData['hours_of_operation'] as $hours) {
                $resource['hoursOfOperation'][] = [
                    'daysOfWeek' => $hours['days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'],
                    'allDay' => $hours['all_day'] ?? false,
                    'openingTime' => $hours['opening_time'] ?? null,
                    'closingTime' => $hours['closing_time'] ?? null,
                ];
            }
        }

        // Add availability exceptions if available
        if (!empty($locationData['availability_exceptions'])) {
            $resource['availabilityExceptions'] = $locationData['availability_exceptions'];
        }

        return $resource;
    }

    /**
     * Map location type to standard code.
     *
     * @param string|null $type
     * @return string
     */
    protected function mapLocationType(?string $type): string
    {
        return match (strtolower($type ?? '')) {
            'hospital', 'rumah sakit', 'rs' => 'HOSP',
            'clinic', 'klinik', 'puskesmas' => 'CSC',
            'pharmacy', 'apotek', 'farmasi' => 'PHARM',
            'laboratory', 'lab', 'laboratorium' => 'LAB',
            'radiology', 'radiologi' => 'RAD',
            'emergency', 'igd', 'gawat darurat' => 'ER',
            'ward', 'bangsal', 'ruang rawat' => 'WARD',
            'room', 'kamar' => 'ROOM',
            'bed' => 'BED',
            'site' => 'SITE',
            'area' => 'AREA',
            'jurisdiction' => 'JURISDICTION',
            'house' => 'HOUSE',
            'dialysis unit' => 'D',
            'diabetes clinic' => 'D',
            default => 'SITE',
        };
    }

    /**
     * Get organization ID.
     *
     * @return string
     */
    public function getOrganizationId(): string
    {
        return $this->satuSehat->getOrganizationId();
    }

    /**
     * Clear organization cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $organizationId = $this->satuSehat->getOrganizationId();
        Cache::forget("satusehat_organization_{$organizationId}");

        // Clear all location caches
        // Note: In production, you might want to use cache tags or a different strategy
        Log::info('SatuSehat organization cache cleared');
    }
}
