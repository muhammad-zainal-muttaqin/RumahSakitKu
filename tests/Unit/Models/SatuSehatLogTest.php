<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Patient\Patient;
use App\Models\SatuSehatLog;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SatuSehatLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $satuSehatLog = new SatuSehatLog();

        $expectedFillable = [
            'resource_type',
            'fhir_id',
            'local_type',
            'local_id',
            'action',
            'request_data',
            'response_data',
            'status',
            'error_message',
            'response_time_ms',
            'retry_count',
        ];

        $this->assertEquals($expectedFillable, $satuSehatLog->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $satuSehatLog = new SatuSehatLog();
        $casts = $satuSehatLog->getCasts();

        $this->assertArrayHasKey('request_data', $casts);
        $this->assertArrayHasKey('response_data', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertEquals('array', $casts['request_data']);
        $this->assertEquals('array', $casts['response_data']);
    }

    #[Test]
    public function it_has_local_morph_relationship(): void
    {
        $satuSehatLog = new SatuSehatLog();
        $relation = $satuSehatLog->local();

        $this->assertInstanceOf(MorphTo::class, $relation);
    }

    #[Test]
    public function it_has_for_resource_type_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'UPDATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Encounter',
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $results = SatuSehatLog::forResourceType('Patient')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->resource_type === 'Patient'));
    }

    #[Test]
    public function it_has_for_fhir_id_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-123',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-456',
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $results = SatuSehatLog::forFhirId('fhir-123')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('fhir-123', $results->first()->fhir_id);
    }

    #[Test]
    public function it_has_for_local_scope(): void
    {
        $patient = Patient::factory()->create();

        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'local_type' => Patient::class,
            'local_id' => $patient->id,
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'local_type' => Patient::class,
            'local_id' => $patient->id + 1,
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $results = SatuSehatLog::forLocal(Patient::class, $patient->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($patient->id, $results->first()->local_id);
    }

    #[Test]
    public function it_has_with_status_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'pending',
        ]);

        $results = SatuSehatLog::withStatus('success')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('success', $results->first()->status);
    }

    #[Test]
    public function it_has_successful_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
        ]);

        $results = SatuSehatLog::successful()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->status === 'success'));
    }

    #[Test]
    public function it_has_failed_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
        ]);

        $results = SatuSehatLog::failed()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->status === 'failed'));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'pending',
        ]);

        $results = SatuSehatLog::pending()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('pending', $results->first()->status);
    }

    #[Test]
    public function it_has_for_action_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'UPDATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $results = SatuSehatLog::forAction('CREATE')->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($log) => $log->action === 'CREATE'));
    }

    #[Test]
    public function it_has_in_date_range_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now()->subDays(5),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now()->subDays(3),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $results = SatuSehatLog::inDateRange(
            now()->subDays(4)->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        )->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_recent_scope(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now()->subDays(2),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now()->subDay(),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $results = SatuSehatLog::recent()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->first()->created_at >= $results->last()->created_at);
    }

    #[Test]
    public function it_can_get_fhir_id_for_local_model(): void
    {
        $patient = Patient::factory()->create();

        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-123',
            'local_type' => Patient::class,
            'local_id' => $patient->id,
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $fhirId = SatuSehatLog::getFhirId(Patient::class, $patient->id);

        $this->assertEquals('fhir-123', $fhirId);
    }

    #[Test]
    public function it_returns_null_fhir_id_when_not_found(): void
    {
        $fhirId = SatuSehatLog::getFhirId(Patient::class, 99999);

        $this->assertNull($fhirId);
    }

    #[Test]
    public function it_can_get_fhir_id_with_resource_type_filter(): void
    {
        $patient = Patient::factory()->create();

        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-patient',
            'local_type' => Patient::class,
            'local_id' => $patient->id,
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $fhirId = SatuSehatLog::getFhirId(Patient::class, $patient->id, 'Patient');

        $this->assertEquals('fhir-patient', $fhirId);
    }

    #[Test]
    public function it_can_get_local_id_from_fhir_id(): void
    {
        $patient = Patient::factory()->create();

        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-123',
            'local_type' => Patient::class,
            'local_id' => $patient->id,
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $local = SatuSehatLog::getLocalId('fhir-123');

        $this->assertIsArray($local);
        $this->assertArrayHasKey('type', $local);
        $this->assertArrayHasKey('id', $local);
        $this->assertEquals(Patient::class, $local['type']);
        $this->assertEquals($patient->id, $local['id']);
    }

    #[Test]
    public function it_returns_null_local_id_when_not_found(): void
    {
        $local = SatuSehatLog::getLocalId('non-existent-fhir-id');

        $this->assertIsArray($local);
        $this->assertNull($local['type']);
        $this->assertNull($local['id']);
    }

    #[Test]
    public function it_can_log_fhir_request(): void
    {
        $patient = Patient::factory()->create();

        $log = SatuSehatLog::log(
            'Patient',
            Patient::class,
            $patient->id,
            'CREATE',
            ['name' => 'Test Patient'],
            ['id' => 'fhir-123', 'resourceType' => 'Patient'],
            'success',
            null,
            1500
        );

        $this->assertInstanceOf(SatuSehatLog::class, $log);
        $this->assertEquals('Patient', $log->resource_type);
        $this->assertEquals('fhir-123', $log->fhir_id);
        $this->assertEquals(Patient::class, $log->local_type);
        $this->assertEquals($patient->id, $log->local_id);
        $this->assertEquals('CREATE', $log->action);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(1500, $log->response_time_ms);
    }

    #[Test]
    public function it_extracts_fhir_id_from_response_data(): void
    {
        $log = SatuSehatLog::log(
            'Patient',
            null,
            null,
            'CREATE',
            null,
            ['id' => 'auto-extracted-id'],
            'success'
        );

        $this->assertEquals('auto-extracted-id', $log->fhir_id);
    }

    #[Test]
    public function it_returns_statistics(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'pending',
        ]);

        $stats = SatuSehatLog::getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('successful', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('success_rate', $stats);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['successful']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(50.0, $stats['success_rate']);
    }

    #[Test]
    public function it_returns_statistics_with_resource_type_filter(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Encounter',
            'action' => 'CREATE',
            'status' => 'success',
        ]);

        $stats = SatuSehatLog::getStatistics('Patient');

        $this->assertEquals(1, $stats['total']);
    }

    #[Test]
    public function it_returns_statistics_with_date_range(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now()->subDays(5),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'created_at' => now(),
        ]);

        $stats = SatuSehatLog::getStatistics(
            null,
            now()->subDays(2)->format('Y-m-d'),
            now()->addDay()->format('Y-m-d')
        );

        $this->assertEquals(1, $stats['total']);
    }

    #[Test]
    public function it_returns_retry_candidates(): void
    {
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
            'retry_count' => 0,
            'created_at' => now()->subDay(),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
            'retry_count' => 2,
            'created_at' => now()->subDay(),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
            'retry_count' => 5,
            'created_at' => now()->subDay(),
        ]);
        SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'success',
            'retry_count' => 0,
            'created_at' => now()->subDay(),
        ]);

        $candidates = SatuSehatLog::getRetryCandidates();

        $this->assertCount(2, $candidates);
    }

    #[Test]
    public function it_increments_retry_count(): void
    {
        $log = SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'failed',
            'retry_count' => 0,
        ]);

        $log->incrementRetry();

        $this->assertEquals(1, $log->fresh()->retry_count);
    }

    #[Test]
    public function it_marks_as_successful(): void
    {
        $log = SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'pending',
            'error_message' => 'Previous error',
        ]);

        $log->markAsSuccessful(['id' => 'new-fhir-id', 'data' => 'test']);

        $freshLog = $log->fresh();
        $this->assertEquals('success', $freshLog->status);
        $this->assertEquals('new-fhir-id', $freshLog->fhir_id);
        $this->assertNull($freshLog->error_message);
    }

    #[Test]
    public function it_marks_as_failed(): void
    {
        $log = SatuSehatLog::create([
            'resource_type' => 'Patient',
            'action' => 'CREATE',
            'status' => 'pending',
        ]);

        $log->markAsFailed('Error occurred', ['error' => 'details']);

        $freshLog = $log->fresh();
        $this->assertEquals('failed', $freshLog->status);
        $this->assertEquals('Error occurred', $freshLog->error_message);
    }

    #[Test]
    public function it_returns_fhir_url(): void
    {
        config(['satusehat.mode' => 'staging']);
        config(['satusehat.staging.base_url' => 'https://api.staging.example.com']);

        $log = new SatuSehatLog([
            'resource_type' => 'Patient',
            'fhir_id' => 'fhir-123',
        ]);

        $url = $log->getFhirUrl();

        $this->assertEquals('https://api.staging.example.com/Patient/fhir-123', $url);
    }

    #[Test]
    public function it_returns_null_fhir_url_when_missing_data(): void
    {
        $log = new SatuSehatLog([
            'resource_type' => null,
            'fhir_id' => 'fhir-123',
        ]);

        $this->assertNull($log->getFhirUrl());

        $log = new SatuSehatLog([
            'resource_type' => 'Patient',
            'fhir_id' => null,
        ]);

        $this->assertNull($log->getFhirUrl());
    }

    #[Test]
    public function it_checks_has_successful_mapping(): void
    {
        $successfulLog = new SatuSehatLog([
            'status' => 'success',
            'fhir_id' => 'fhir-123',
        ]);
        $failedLog = new SatuSehatLog([
            'status' => 'failed',
            'fhir_id' => 'fhir-123',
        ]);
        $noFhirIdLog = new SatuSehatLog([
            'status' => 'success',
            'fhir_id' => null,
        ]);

        $this->assertTrue($successfulLog->hasSuccessfulMapping());
        $this->assertFalse($failedLog->hasSuccessfulMapping());
        $this->assertFalse($noFhirIdLog->hasSuccessfulMapping());
    }
}
