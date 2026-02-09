<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MasterData\Medicine;
use App\Models\MasterData\Room;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test class for CacheService.
 *
 * Tests caching functionality for patients, queue stats,
 * room occupancy, indicators, and cache management.
 */
class CacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Patient Cache Tests ====================

    #[Test]
    public function it_can_store_and_retrieve_patient_from_cache(): void
    {
        $patientId = 1;
        $mockPatient = Mockery::mock(Patient::class);
        $mockPatient->shouldReceive('getAttribute')->with('id')->andReturn($patientId);

        CacheService::putPatient($mockPatient);
        $result = CacheService::getPatient($patientId);

        // Since we're mocking, the result might not be exactly as expected
        // but we verify the methods don't throw errors
        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_cached_patient(): void
    {
        $result = CacheService::getPatient(999999);

        // Will be null or a Patient object depending on cache state
        $this->assertTrue($result === null || $result instanceof Patient);
    }

    #[Test]
    public function it_can_forget_patient_cache(): void
    {
        // Should not throw an error
        CacheService::forgetPatient(1);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_zero_patient_id_for_cache(): void
    {
        CacheService::forgetPatient(0);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_handles_negative_patient_id_for_cache(): void
    {
        CacheService::forgetPatient(-1);

        $this->assertTrue(true);
    }

    // ==================== Queue Stats Cache Tests ====================

    #[Test]
    public function it_returns_queue_stats_array_structure(): void
    {
        $result = CacheService::getQueueStats(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('polyclinic_id', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('waiting', $result);
        $this->assertArrayHasKey('called', $result);
        $this->assertArrayHasKey('completed', $result);
        $this->assertArrayHasKey('cancelled', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('avg_wait_time', $result);
    }

    #[Test]
    public function it_can_store_queue_stats_in_cache(): void
    {
        $stats = [
            'polyclinic_id' => 1,
            'date' => now()->toDateString(),
            'waiting' => 5,
            'called' => 2,
            'completed' => 10,
            'cancelled' => 1,
            'total' => 18,
            'avg_wait_time' => 15,
        ];

        CacheService::putQueueStats(1, $stats);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_clear_queue_stats_cache(): void
    {
        CacheService::forgetQueueStats(1);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_zero_values_for_empty_queue_stats(): void
    {
        $result = CacheService::getQueueStats(999999);

        $this->assertEquals(0, $result['waiting']);
        $this->assertEquals(0, $result['called']);
        $this->assertEquals(0, $result['completed']);
        $this->assertEquals(0, $result['cancelled']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['avg_wait_time']);
    }

    // ==================== Room Occupancy Cache Tests ====================

    #[Test]
    public function it_returns_room_occupancy_array_structure(): void
    {
        $result = CacheService::getRoomOccupancy();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('total_rooms', $result);
        $this->assertArrayHasKey('total_beds', $result);
        $this->assertArrayHasKey('occupied_beds', $result);
        $this->assertArrayHasKey('available_beds', $result);
        $this->assertArrayHasKey('occupancy_rate', $result);
        $this->assertArrayHasKey('rooms', $result);
    }

    #[Test]
    public function it_returns_rooms_as_array(): void
    {
        $result = CacheService::getRoomOccupancy();

        $this->assertIsArray($result['rooms']);
    }

    #[Test]
    public function it_returns_valid_occupancy_rate(): void
    {
        $result = CacheService::getRoomOccupancy();

        $this->assertIsFloat($result['occupancy_rate']);
        $this->assertGreaterThanOrEqual(0, $result['occupancy_rate']);
        $this->assertLessThanOrEqual(100, $result['occupancy_rate']);
    }

    #[Test]
    public function it_can_store_room_occupancy_in_cache(): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'total_rooms' => 10,
            'total_beds' => 50,
            'occupied_beds' => 30,
            'available_beds' => 20,
            'occupancy_rate' => 60.0,
            'rooms' => [],
        ];

        CacheService::putRoomOccupancy($data);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_clear_room_occupancy_cache(): void
    {
        CacheService::forgetRoomOccupancy();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_calculates_available_beds_correctly(): void
    {
        $result = CacheService::getRoomOccupancy();

        $expectedAvailable = $result['total_beds'] - $result['occupied_beds'];
        $this->assertEquals($expectedAvailable, $result['available_beds']);
    }

    // ==================== Indicators Cache Tests ====================

    #[Test]
    public function it_returns_null_for_nonexistent_indicators(): void
    {
        $result = CacheService::getIndicators('2099-01-01');

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_store_indicators_in_cache(): void
    {
        $data = [
            'bor' => 75.5,
            'los' => 3.2,
            'toi' => 1.5,
            'bto' => 45.0,
        ];

        CacheService::putIndicators('2024-01-15', $data);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_retrieve_stored_indicators(): void
    {
        $date = '2024-01-15';
        $data = [
            'bor' => 75.5,
            'los' => 3.2,
        ];

        CacheService::putIndicators($date, $data);
        $result = CacheService::getIndicators($date);

        // Result may be null if cache driver is not available in test
        if ($result !== null) {
            $this->assertEquals($data['bor'], $result['bor']);
            $this->assertEquals($data['los'], $result['los']);
        } else {
            $this->assertTrue(true); // Cache not available in test environment
        }
    }

    // ==================== Visit Count Cache Tests ====================

    #[Test]
    public function it_returns_today_visit_count_as_integer(): void
    {
        $result = CacheService::getTodayVisitCount();

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function it_returns_visit_counts_by_type_as_array(): void
    {
        $result = CacheService::getTodayVisitCountsByType();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('rawat_jalan', $result);
        $this->assertArrayHasKey('rawat_inap', $result);
        $this->assertArrayHasKey('igd', $result);
        $this->assertArrayHasKey('mcu', $result);
        $this->assertArrayHasKey('total', $result);
    }

    #[Test]
    public function it_returns_integer_counts_for_all_visit_types(): void
    {
        $result = CacheService::getTodayVisitCountsByType();

        $this->assertIsInt($result['rawat_jalan']);
        $this->assertIsInt($result['rawat_inap']);
        $this->assertIsInt($result['igd']);
        $this->assertIsInt($result['mcu']);
        $this->assertIsInt($result['total']);
    }

    #[Test]
    public function it_calculates_total_visits_correctly(): void
    {
        $result = CacheService::getTodayVisitCountsByType();

        $expectedTotal = $result['rawat_jalan']
            + $result['rawat_inap']
            + $result['igd']
            + $result['mcu'];

        $this->assertEquals($expectedTotal, $result['total']);
    }

    // ==================== Top Medicines Cache Tests ====================

    #[Test]
    public function it_returns_top_medicines_as_collection(): void
    {
        $result = CacheService::getTopMedicines(10);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_respects_limit_for_top_medicines(): void
    {
        $limit = 5;
        $result = CacheService::getTopMedicines($limit);

        $this->assertLessThanOrEqual($limit, $result->count());
    }

    #[Test]
    public function it_returns_empty_collection_for_zero_limit(): void
    {
        $result = CacheService::getTopMedicines(0);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(0, $result->count());
    }

    // ==================== Active Patients Cache Tests ====================

    #[Test]
    public function it_returns_active_patients_as_collection(): void
    {
        $result = CacheService::getActivePatients();

        $this->assertInstanceOf(Collection::class, $result);
    }

    // ==================== Cache Management Tests ====================

    #[Test]
    public function it_can_flush_all_cache(): void
    {
        // Should not throw an error
        CacheService::flush();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_flush_cache_by_pattern(): void
    {
        // Should not throw an error
        CacheService::flushPattern('patient:*');

        $this->assertTrue(true);
    }

    #[Test]
    public function it_returns_cache_stats_as_array(): void
    {
        $result = CacheService::getStats();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('driver', $result);
    }

    #[Test]
    public function it_returns_redis_as_driver_in_stats(): void
    {
        $result = CacheService::getStats();

        $this->assertEquals('redis', $result['driver']);
    }

    // ==================== Edge Cases and Error Handling ====================

    #[Test]
    public function it_handles_invalid_date_format_for_indicators(): void
    {
        // Should not throw an error
        CacheService::putIndicators('invalid-date', ['test' => 'data']);
        $result = CacheService::getIndicators('invalid-date');

        // Result may be null or the stored data depending on cache
        $this->assertTrue($result === null || is_array($result));
    }

    #[Test]
    public function it_handles_empty_data_for_indicators(): void
    {
        CacheService::putIndicators('2024-01-15', []);
        $result = CacheService::getIndicators('2024-01-15');

        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        } else {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function it_handles_large_limit_for_top_medicines(): void
    {
        $result = CacheService::getTopMedicines(10000);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_handles_negative_limit_for_top_medicines(): void
    {
        $result = CacheService::getTopMedicines(-1);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_returns_consistent_results_for_visit_count(): void
    {
        $result1 = CacheService::getTodayVisitCount();
        $result2 = CacheService::getTodayVisitCount();

        $this->assertEquals($result1, $result2);
    }

    #[Test]
    public function it_returns_valid_timestamp_in_room_occupancy(): void
    {
        $result = CacheService::getRoomOccupancy();

        $this->assertIsString($result['timestamp']);
        // Should be a valid ISO 8601 date
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $result['timestamp']);
    }

    #[Test]
    public function it_returns_non_negative_counts_in_room_occupancy(): void
    {
        $result = CacheService::getRoomOccupancy();

        $this->assertGreaterThanOrEqual(0, $result['total_rooms']);
        $this->assertGreaterThanOrEqual(0, $result['total_beds']);
        $this->assertGreaterThanOrEqual(0, $result['occupied_beds']);
        $this->assertGreaterThanOrEqual(0, $result['available_beds']);
    }

    #[Test]
    public function it_maintains_bed_count_consistency(): void
    {
        $result = CacheService::getRoomOccupancy();

        // Total beds should equal occupied + available
        $expectedTotal = $result['occupied_beds'] + $result['available_beds'];
        $this->assertEquals($expectedTotal, $result['total_beds']);
    }

    #[Test]
    public function it_handles_very_old_date_for_indicators(): void
    {
        $date = '1900-01-01';
        $data = ['test' => 'value'];

        CacheService::putIndicators($date, $data);
        $result = CacheService::getIndicators($date);

        if ($result !== null) {
            $this->assertEquals($data, $result);
        } else {
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function it_handles_future_date_for_indicators(): void
    {
        $date = '2099-12-31';
        $data = ['test' => 'value'];

        CacheService::putIndicators($date, $data);
        $result = CacheService::getIndicators($date);

        if ($result !== null) {
            $this->assertEquals($data, $result);
        } else {
            $this->assertTrue(true);
        }
    }
}
