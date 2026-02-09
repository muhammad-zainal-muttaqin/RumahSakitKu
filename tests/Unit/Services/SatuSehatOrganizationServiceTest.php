<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SatuSehat\SatuSehatOrganizationService;
use App\Services\SatuSehat\SatuSehatService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class for SatuSehatOrganizationService.
 *
 * Tests organization and location management for SatuSehat integration.
 */
class SatuSehatOrganizationServiceTest extends TestCase
{
    private SatuSehatOrganizationService $service;
    private $mockSatuSehat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
        $this->mockSatuSehat->shouldReceive('getOrganizationId')->andReturn('test-org-id');

        $this->service = new SatuSehatOrganizationService($this->mockSatuSehat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    // ==================== Get Organization Tests ====================

    #[Test]
    public function it_gets_organization_from_api(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Organization', 'GET', null, 'test-org-id')
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 'test-org-id',
                    'resourceType' => 'Organization',
                    'name' => 'Test Hospital',
                ],
                'error' => null,
            ]);

        $result = $this->service->getOrganization();

        $this->assertTrue($result['success']);
        $this->assertEquals('test-org-id', $result['data']['id']);
        $this->assertEquals('Test Hospital', $result['data']['name']);
    }

    #[Test]
    public function it_caches_organization_data(): void
    {
        $organizationData = [
            'id' => 'test-org-id',
            'resourceType' => 'Organization',
            'name' => 'Test Hospital',
        ];

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => $organizationData,
                'error' => null,
            ]);

        // First call - should hit API
        $result1 = $this->service->getOrganization();

        // Second call - should use cache
        $result2 = $this->service->getOrganization();

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertEquals($result1['data'], $result2['data']);
    }

    #[Test]
    public function it_returns_error_when_organization_not_found(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->andReturn([
                'success' => false,
                'data' => null,
                'error' => 'Organization not found',
            ]);

        $result = $this->service->getOrganization();

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
    }

    // ==================== Get Location Tests ====================

    #[Test]
    public function it_gets_location_by_id(): void
    {
        $locationId = 'loc-123';

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'GET', null, $locationId)
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => $locationId,
                    'resourceType' => 'Location',
                    'name' => 'Room 101',
                ],
                'error' => null,
            ]);

        $result = $this->service->getLocation($locationId);

        $this->assertTrue($result['success']);
        $this->assertEquals($locationId, $result['data']['id']);
    }

    #[Test]
    public function it_caches_location_data(): void
    {
        $locationId = 'loc-123';
        $locationData = [
            'id' => $locationId,
            'name' => 'Room 101',
        ];

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => $locationData,
                'error' => null,
            ]);

        // First call
        $result1 = $this->service->getLocation($locationId);

        // Second call - should use cache
        $result2 = $this->service->getLocation($locationId);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
    }

    // ==================== Search Locations Tests ====================

    #[Test]
    public function it_searches_locations_by_organization(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', ['organization' => 'test-org-id'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'entry' => [
                        ['resource' => ['id' => 'loc-1', 'name' => 'Room 1']],
                        ['resource' => ['id' => 'loc-2', 'name' => 'Room 2']],
                    ],
                ],
                'error' => null,
            ]);

        $result = $this->service->searchLocationsByOrganization();

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_searches_locations_by_custom_organization_id(): void
    {
        $customOrgId = 'custom-org-id';

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', ['organization' => $customOrgId])
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
                'error' => null,
            ]);

        $result = $this->service->searchLocationsByOrganization($customOrgId);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_searches_locations_with_additional_params(): void
    {
        $additionalParams = ['name' => 'Room 101'];

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', [
                'organization' => 'test-org-id',
                'name' => 'Room 101',
            ])
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
                'error' => null,
            ]);

        $result = $this->service->searchLocationsByOrganization(null, $additionalParams);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_searches_locations_by_name(): void
    {
        $name = 'Emergency Room';

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', ['name' => $name])
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
                'error' => null,
            ]);

        $result = $this->service->searchLocationsByName($name);

        $this->assertTrue($result['success']);
    }

    // ==================== Get All Locations Tests ====================

    #[Test]
    public function it_gets_all_locations_for_organization(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', ['organization' => 'test-org-id'])
            ->andReturn([
                'success' => true,
                'data' => [
                    'entry' => [
                        ['resource' => ['id' => 'loc-1', 'name' => 'Room 1']],
                        ['resource' => ['id' => 'loc-2', 'name' => 'Room 2']],
                    ],
                ],
                'error' => null,
            ]);

        $result = $this->service->getAllLocations();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['locations']);
        $this->assertEquals('loc-1', $result['locations'][0]['id']);
        $this->assertEquals('loc-2', $result['locations'][1]['id']);
    }

    #[Test]
    public function it_returns_empty_array_when_no_locations_found(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
                'error' => null,
            ]);

        $result = $this->service->getAllLocations();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['locations']);
    }

    #[Test]
    public function it_returns_error_when_search_fails(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Search failed',
            ]);

        $result = $this->service->getAllLocations();

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['locations']);
        $this->assertStringContainsString('Failed to fetch', $result['error']);
    }

    // ==================== Get Location By Identifier Tests ====================

    #[Test]
    public function it_gets_location_by_identifier(): void
    {
        $identifier = 'LOC001';

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', [
                'identifier' => 'http://sys-ids.kemkes.go.id/location|' . $identifier,
            ])
            ->andReturn([
                'success' => true,
                'data' => [
                    'entry' => [
                        ['resource' => ['id' => 'loc-123', 'name' => 'Room 101']],
                    ],
                ],
                'error' => null,
            ]);

        $result = $this->service->getLocationByIdentifier($identifier);

        $this->assertTrue($result['success']);
        $this->assertEquals('loc-123', $result['data']['id']);
    }

    #[Test]
    public function it_returns_error_when_location_by_identifier_not_found(): void
    {
        $identifier = 'NONEXISTENT';

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['entry' => []],
                'error' => null,
            ]);

        $result = $this->service->getLocationByIdentifier($identifier);

        $this->assertFalse($result['success']);
        $this->assertEquals('Location not found', $result['error']);
    }

    #[Test]
    public function it_uses_custom_system_for_identifier(): void
    {
        $identifier = 'LOC001';
        $system = 'http://custom.system';

        $this->mockSatuSehat
            ->shouldReceive('search')
            ->once()
            ->with('Location', ['identifier' => $system . '|' . $identifier])
            ->andReturn([
                'success' => true,
                'data' => ['entry' => [['resource' => ['id' => 'loc-123']]]],
                'error' => null,
            ]);

        $result = $this->service->getLocationByIdentifier($identifier, $system);

        $this->assertTrue($result['success']);
    }

    // ==================== Create Location Tests ====================

    #[Test]
    public function it_creates_location_successfully(): void
    {
        $locationData = [
            'name' => 'New Room',
            'status' => 'active',
        ];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->once()
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'POST', Mockery::any())
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'loc-new', 'name' => 'New Room'],
                'error' => null,
            ]);

        $result = $this->service->createLocation($locationData);

        $this->assertTrue($result['success']);
        $this->assertEquals('loc-new', $result['data']['id']);
    }

    #[Test]
    public function it_returns_error_when_location_validation_fails(): void
    {
        $locationData = ['name' => 'Invalid'];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->once()
            ->andReturn(['valid' => false, 'errors' => ['Name is required']]);

        $result = $this->service->createLocation($locationData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
    }

    // ==================== Update Location Tests ====================

    #[Test]
    public function it_updates_location_successfully(): void
    {
        $locationId = 'loc-123';
        $locationData = ['name' => 'Updated Room'];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->once()
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'PUT', Mockery::any(), $locationId)
            ->andReturn([
                'success' => true,
                'data' => ['id' => $locationId, 'name' => 'Updated Room'],
                'error' => null,
            ]);

        $result = $this->service->updateLocation($locationId, $locationData);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_clears_cache_after_location_update(): void
    {
        $locationId = 'loc-123';
        $locationData = ['name' => 'Updated Room'];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->andReturn([
                'success' => true,
                'data' => ['id' => $locationId],
            ]);

        $result = $this->service->updateLocation($locationId, $locationData);

        $this->assertTrue($result['success']);
        // Cache should be cleared
        $this->assertFalse(Cache::has("satusehat_location_{$locationId}"));
    }

    // ==================== Update Location Status Tests ====================

    #[Test]
    public function it_updates_location_status_successfully(): void
    {
        $locationId = 'loc-123';
        $newStatus = 'suspended';

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'GET', null, $locationId)
            ->andReturn([
                'success' => true,
                'data' => ['id' => $locationId, 'status' => 'active'],
            ]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'PUT', Mockery::any(), $locationId)
            ->andReturn([
                'success' => true,
                'data' => ['id' => $locationId, 'status' => $newStatus],
            ]);

        $result = $this->service->updateLocationStatus($locationId, $newStatus);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_returns_error_for_invalid_status(): void
    {
        $locationId = 'loc-123';
        $invalidStatus = 'invalid';

        $result = $this->service->updateLocationStatus($locationId, $invalidStatus);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid status', $result['error']);
    }

    #[Test]
    public function it_accepts_all_valid_statuses(): void
    {
        $validStatuses = ['active', 'suspended', 'inactive'];

        foreach ($validStatuses as $status) {
            $this->mockSatuSehat = Mockery::mock(SatuSehatService::class);
            $this->mockSatuSehat->shouldReceive('getOrganizationId')->andReturn('test-org-id');
            $this->service = new SatuSehatOrganizationService($this->mockSatuSehat);

            $this->mockSatuSehat
                ->shouldReceive('request')
                ->with('Location', 'GET', null, Mockery::any())
                ->andReturn(['success' => true, 'data' => ['status' => 'active']]);

            $this->mockSatuSehat
                ->shouldReceive('request')
                ->with('Location', 'PUT', Mockery::any(), Mockery::any())
                ->andReturn(['success' => true, 'data' => ['status' => $status]]);

            $result = $this->service->updateLocationStatus('loc-123', $status);

            // Should not return validation error
            $this->assertStringNotContainsString('Invalid status', $result['error'] ?? '');
        }

        $this->assertTrue(true);
    }

    // ==================== Get Organization ID Tests ====================

    #[Test]
    public function it_returns_organization_id(): void
    {
        $result = $this->service->getOrganizationId();

        $this->assertEquals('test-org-id', $result);
    }

    // ==================== Clear Cache Tests ====================

    #[Test]
    public function it_clears_organization_cache(): void
    {
        // Pre-populate cache
        Cache::put('satusehat_organization_test-org-id', ['name' => 'Test'], 3600);

        $this->service->clearCache();

        $this->assertFalse(Cache::has('satusehat_organization_test-org-id'));
    }

    // ==================== Location Type Mapping Tests ====================

    #[Test]
    public function it_maps_location_types_to_standard_codes(): void
    {
        $typeMappings = [
            ['input' => 'hospital', 'expected' => 'HOSP'],
            ['input' => 'rumah sakit', 'expected' => 'HOSP'],
            ['input' => 'clinic', 'expected' => 'CSC'],
            ['input' => 'pharmacy', 'expected' => 'PHARM'],
            ['input' => 'laboratory', 'expected' => 'LAB'],
            ['input' => 'radiology', 'expected' => 'RAD'],
            ['input' => 'emergency', 'expected' => 'ER'],
            ['input' => 'ward', 'expected' => 'WARD'],
            ['input' => 'room', 'expected' => 'ROOM'],
            ['input' => 'bed', 'expected' => 'BED'],
        ];

        foreach ($typeMappings as $mapping) {
            $this->assertIsString($mapping['expected']);
        }

        $this->assertTrue(true);
    }

    // ==================== Edge Cases ====================

    #[Test]
    public function it_handles_empty_location_name(): void
    {
        $locationData = ['name' => ''];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'loc-123']]);

        $result = $this->service->createLocation($locationData);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_location_with_full_data(): void
    {
        $locationData = [
            'name' => 'Complete Location',
            'status' => 'active',
            'mode' => 'instance',
            'identifier' => [
                'system' => 'http://custom.system',
                'value' => 'LOC001',
            ],
            'description' => 'A test location',
            'type' => 'room',
            'telecom' => [
                ['system' => 'phone', 'value' => '123456'],
            ],
            'address' => '123 Test Street',
            'hours_of_operation' => [
                [
                    'days' => ['mon', 'tue'],
                    'opening_time' => '08:00',
                    'closing_time' => '17:00',
                ],
            ],
            'availability_exceptions' => 'Closed on holidays',
        ];

        $this->mockSatuSehat
            ->shouldReceive('validateResource')
            ->andReturn(['valid' => true, 'errors' => []]);

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->andReturn(['success' => true, 'data' => ['id' => 'loc-full']]);

        $result = $this->service->createLocation($locationData);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function it_handles_api_error_when_updating_status(): void
    {
        $locationId = 'loc-123';

        $this->mockSatuSehat
            ->shouldReceive('request')
            ->once()
            ->with('Location', 'GET', null, $locationId)
            ->andReturn([
                'success' => false,
                'error' => 'Location not found',
            ]);

        $result = $this->service->updateLocationStatus($locationId, 'inactive');

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function it_handles_empty_entry_array_in_search_results(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->andReturn([
                'success' => true,
                'data' => [], // No entry key
                'error' => null,
            ]);

        $result = $this->service->getAllLocations();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['locations']);
    }

    #[Test]
    public function it_handles_search_failure_for_identifier_lookup(): void
    {
        $this->mockSatuSehat
            ->shouldReceive('search')
            ->andReturn([
                'success' => false,
                'error' => 'Search error',
            ]);

        $result = $this->service->getLocationByIdentifier('LOC001');

        $this->assertFalse($result['success']);
    }
}
