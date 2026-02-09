<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\BpjsLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BpjsLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $bpjsLog = new BpjsLog();

        $expectedFillable = [
            'service_type',
            'endpoint',
            'method',
            'request_data',
            'response_data',
            'http_status',
            'error_message',
            'execution_time_ms',
            'user_id',
            'executed_at',
        ];

        $this->assertEquals($expectedFillable, $bpjsLog->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $bpjsLog = new BpjsLog();
        $casts = $bpjsLog->getCasts();

        $this->assertArrayHasKey('executed_at', $casts);
        $this->assertArrayHasKey('execution_time_ms', $casts);
        $this->assertArrayHasKey('http_status', $casts);
        $this->assertArrayHasKey('user_id', $casts);
        $this->assertEquals('datetime', $casts['executed_at']);
        $this->assertEquals('float', $casts['execution_time_ms']);
        $this->assertEquals('integer', $casts['http_status']);
        $this->assertEquals('integer', $casts['user_id']);
    }

    #[Test]
    public function it_has_hidden_attributes(): void
    {
        $bpjsLog = new BpjsLog();

        $expectedHidden = [
            'request_data',
            'response_data',
        ];

        $this->assertEquals($expectedHidden, $bpjsLog->getHidden());
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $bpjsLog = new BpjsLog();
        $relation = $bpjsLog->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    #[Test]
    public function it_sets_executed_at_on_creating(): void
    {
        $bpjsLog = BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test',
            'method' => 'GET',
        ]);

        $this->assertNotNull($bpjsLog->executed_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $bpjsLog->executed_at);
    }

    #[Test]
    public function it_preserves_executed_at_when_provided(): void
    {
        $customDate = now()->subDays(5);
        $bpjsLog = BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test',
            'method' => 'GET',
            'executed_at' => $customDate,
        ]);

        $this->assertEquals($customDate->format('Y-m-d'), $bpjsLog->executed_at->format('Y-m-d'));
    }

    #[Test]
    public function it_has_by_service_scope(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test1', 'method' => 'GET']);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test2', 'method' => 'GET']);
        BpjsLog::create(['service_type' => 'pcare', 'endpoint' => '/test3', 'method' => 'GET']);

        $results = BpjsLog::byService('vclaim')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->service_type === 'vclaim'));
    }

    #[Test]
    public function it_has_by_endpoint_scope(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/peserta/123', 'method' => 'GET']);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/peserta/456', 'method' => 'GET']);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/sep', 'method' => 'POST']);

        $results = BpjsLog::byEndpoint('peserta')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_by_status_scope(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test1', 'method' => 'GET', 'http_status' => 200]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test2', 'method' => 'GET', 'http_status' => 200]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test3', 'method' => 'GET', 'http_status' => 500]);

        $results = BpjsLog::byStatus(200)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->http_status === 200));
    }

    #[Test]
    public function it_has_successful_scope(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test1', 'method' => 'GET', 'http_status' => 200]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test2', 'method' => 'GET', 'http_status' => 201]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test3', 'method' => 'GET', 'http_status' => 500]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test4', 'method' => 'GET', 'http_status' => 400]);

        $results = BpjsLog::successful()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->http_status >= 200 && $log->http_status < 300));
    }

    #[Test]
    public function it_has_failed_scope(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test1', 'method' => 'GET', 'http_status' => 200]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test2', 'method' => 'GET', 'http_status' => 500]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test3', 'method' => 'GET', 'http_status' => 400]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test4', 'method' => 'GET', 'http_status' => 200, 'error_message' => 'Some error']);

        $results = BpjsLog::failed()->get();

        $this->assertCount(3, $results);
    }

    #[Test]
    public function it_has_between_dates_scope(): void
    {
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test1',
            'method' => 'GET',
            'executed_at' => now()->subDays(5),
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test2',
            'method' => 'GET',
            'executed_at' => now()->subDays(3),
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test3',
            'method' => 'GET',
            'executed_at' => now(),
        ]);

        $results = BpjsLog::betweenDates(
            now()->subDays(4)->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        )->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_recent_scope(): void
    {
        for ($i = 0; $i < 150; $i++) {
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => "/test{$i}",
                'method' => 'GET',
                'executed_at' => now()->subMinutes($i),
            ]);
        }

        $results = BpjsLog::recent()->get();

        $this->assertCount(100, $results);
    }

    #[Test]
    public function it_has_recent_scope_with_custom_limit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => "/test{$i}",
                'method' => 'GET',
                'executed_at' => now()->subMinutes($i),
            ]);
        }

        $results = BpjsLog::recent(10)->get();

        $this->assertCount(10, $results);
    }

    #[Test]
    public function it_has_slow_scope(): void
    {
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test1',
            'method' => 'GET',
            'execution_time_ms' => 3000,
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test2',
            'method' => 'GET',
            'execution_time_ms' => 6000,
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test3',
            'method' => 'GET',
            'execution_time_ms' => 10000,
        ]);

        $results = BpjsLog::slow()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->execution_time_ms > 5000));
    }

    #[Test]
    public function it_has_slow_scope_with_custom_threshold(): void
    {
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test1',
            'method' => 'GET',
            'execution_time_ms' => 2000,
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test2',
            'method' => 'GET',
            'execution_time_ms' => 3000,
        ]);

        $results = BpjsLog::slow(2500)->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_returns_is_successful_true_for_2xx_status(): void
    {
        $bpjsLog = new BpjsLog(['http_status' => 200]);
        $this->assertTrue($bpjsLog->isSuccessful());

        $bpjsLog = new BpjsLog(['http_status' => 201]);
        $this->assertTrue($bpjsLog->isSuccessful());

        $bpjsLog = new BpjsLog(['http_status' => 204]);
        $this->assertTrue($bpjsLog->isSuccessful());
    }

    #[Test]
    public function it_returns_is_successful_false_for_non_2xx_status(): void
    {
        $bpjsLog = new BpjsLog(['http_status' => 400]);
        $this->assertFalse($bpjsLog->isSuccessful());

        $bpjsLog = new BpjsLog(['http_status' => 500]);
        $this->assertFalse($bpjsLog->isSuccessful());
    }

    #[Test]
    public function it_returns_is_successful_false_when_has_error_message(): void
    {
        $bpjsLog = new BpjsLog(['http_status' => 200, 'error_message' => 'Some error']);
        $this->assertFalse($bpjsLog->isSuccessful());
    }

    #[Test]
    public function it_returns_is_failed_opposite_of_is_successful(): void
    {
        $successfulLog = new BpjsLog(['http_status' => 200]);
        $failedLog = new BpjsLog(['http_status' => 500]);

        $this->assertFalse($successfulLog->isFailed());
        $this->assertTrue($failedLog->isFailed());
    }

    #[Test]
    public function it_returns_execution_time_in_seconds(): void
    {
        $bpjsLog = new BpjsLog(['execution_time_ms' => 5000]);

        $this->assertEquals(5.0, $bpjsLog->getExecutionTimeInSeconds());
    }

    #[Test]
    public function it_returns_summary(): void
    {
        $bpjsLog = BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/peserta',
            'method' => 'GET',
            'http_status' => 200,
            'execution_time_ms' => 1500,
            'error_message' => null,
        ]);

        $summary = $bpjsLog->getSummary();

        $this->assertArrayHasKey('id', $summary);
        $this->assertArrayHasKey('service_type', $summary);
        $this->assertArrayHasKey('endpoint', $summary);
        $this->assertArrayHasKey('method', $summary);
        $this->assertArrayHasKey('http_status', $summary);
        $this->assertArrayHasKey('success', $summary);
        $this->assertArrayHasKey('execution_time_ms', $summary);
        $this->assertArrayHasKey('executed_at', $summary);
        $this->assertArrayHasKey('user_id', $summary);
        $this->assertArrayHasKey('has_error', $summary);

        $this->assertEquals('vclaim', $summary['service_type']);
        $this->assertEquals('/peserta', $summary['endpoint']);
        $this->assertEquals('GET', $summary['method']);
        $this->assertEquals(200, $summary['http_status']);
        $this->assertTrue($summary['success']);
        $this->assertEquals(1500, $summary['execution_time_ms']);
        $this->assertFalse($summary['has_error']);
    }

    #[Test]
    public function it_returns_detailed_log(): void
    {
        $bpjsLog = BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/peserta',
            'method' => 'GET',
            'request_data' => json_encode(['param' => 'value']),
            'response_data' => json_encode(['data' => 'result']),
            'http_status' => 200,
            'error_message' => null,
            'execution_time_ms' => 1500,
        ]);

        $detailed = $bpjsLog->getDetailedLog();

        $this->assertArrayHasKey('id', $detailed);
        $this->assertArrayHasKey('service_type', $detailed);
        $this->assertArrayHasKey('endpoint', $detailed);
        $this->assertArrayHasKey('method', $detailed);
        $this->assertArrayHasKey('request_data', $detailed);
        $this->assertArrayHasKey('response_data', $detailed);
        $this->assertArrayHasKey('http_status', $detailed);
        $this->assertArrayHasKey('error_message', $detailed);
        $this->assertArrayHasKey('execution_time_ms', $detailed);
        $this->assertArrayHasKey('executed_at', $detailed);
        $this->assertArrayHasKey('user', $detailed);
    }

    #[Test]
    public function it_returns_statistics(): void
    {
        // Create test data
        for ($i = 0; $i < 10; $i++) {
            BpjsLog::create([
                'service_type' => 'vclaim',
                'endpoint' => '/test',
                'method' => 'GET',
                'http_status' => $i < 7 ? 200 : 500,
                'execution_time_ms' => 1000 + ($i * 100),
                'executed_at' => now(),
            ]);
        }

        $stats = BpjsLog::getStatistics();

        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('successful', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
        $this->assertArrayHasKey('average_execution_time_ms', $stats);
        $this->assertArrayHasKey('period_days', $stats);
        $this->assertArrayHasKey('service_type', $stats);

        $this->assertEquals(10, $stats['total_requests']);
        $this->assertEquals(7, $stats['successful']);
        $this->assertEquals(3, $stats['failed']);
        $this->assertEquals(70.0, $stats['success_rate']);
        $this->assertEquals('all', $stats['service_type']);
    }

    #[Test]
    public function it_returns_statistics_for_specific_service(): void
    {
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test', 'method' => 'GET', 'http_status' => 200, 'executed_at' => now()]);
        BpjsLog::create(['service_type' => 'vclaim', 'endpoint' => '/test', 'method' => 'GET', 'http_status' => 200, 'executed_at' => now()]);
        BpjsLog::create(['service_type' => 'pcare', 'endpoint' => '/test', 'method' => 'GET', 'http_status' => 200, 'executed_at' => now()]);

        $stats = BpjsLog::getStatistics('vclaim');

        $this->assertEquals(2, $stats['total_requests']);
        $this->assertEquals('vclaim', $stats['service_type']);
    }

    #[Test]
    public function it_cleans_old_logs(): void
    {
        // Create old logs
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test1',
            'method' => 'GET',
            'executed_at' => now()->subDays(100),
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test2',
            'method' => 'GET',
            'executed_at' => now()->subDays(100),
        ]);
        BpjsLog::create([
            'service_type' => 'vclaim',
            'endpoint' => '/test3',
            'method' => 'GET',
            'executed_at' => now(),
        ]);

        $deletedCount = BpjsLog::cleanOldLogs(90);

        $this->assertEquals(2, $deletedCount);
        $this->assertEquals(1, BpjsLog::count());
    }

    #[Test]
    public function it_sets_request_data_attribute(): void
    {
        $bpjsLog = new BpjsLog();
        $bpjsLog->setRequestDataAttribute('encrypted_request_data');

        $this->assertEquals('encrypted_request_data', $bpjsLog->attributes['request_data']);
    }

    #[Test]
    public function it_sets_response_data_attribute(): void
    {
        $bpjsLog = new BpjsLog();
        $bpjsLog->setResponseDataAttribute('encrypted_response_data');

        $this->assertEquals('encrypted_response_data', $bpjsLog->attributes['response_data']);
    }

    #[Test]
    public function it_returns_null_decrypted_request_data_when_empty(): void
    {
        $bpjsLog = new BpjsLog(['request_data' => null]);

        $this->assertNull($bpjsLog->getDecryptedRequestData());
    }

    #[Test]
    public function it_returns_null_decrypted_response_data_when_empty(): void
    {
        $bpjsLog = new BpjsLog(['response_data' => null]);

        $this->assertNull($bpjsLog->getDecryptedResponseData());
    }

    #[Test]
    public function it_handles_decryption_error_for_request_data(): void
    {
        $bpjsLog = new BpjsLog(['request_data' => 'invalid_encrypted_data']);

        $result = $bpjsLog->getDecryptedRequestData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('raw', $result);
    }

    #[Test]
    public function it_handles_decryption_error_for_response_data(): void
    {
        $bpjsLog = new BpjsLog(['response_data' => 'invalid_encrypted_data']);

        $result = $bpjsLog->getDecryptedResponseData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('raw', $result);
    }
}
