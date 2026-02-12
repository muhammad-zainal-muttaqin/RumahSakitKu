<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Clinical\MedicalRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ArchiveMedicalRecordJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public MedicalRecord $medicalRecord
    ) {
    }

    public function handle(): void
    {
        $record = $this->medicalRecord->fresh([
            'patient:id,medical_record_number,name,nik,birth_date,gender',
            'visit:id,visit_number,registration_date,visit_type,visit_status,payment_type,bpjs_sep_number',
            'cppts:id,medical_record_id,cppt_date,is_verified,created_by',
            'assessments:id,medical_record_id,assessment_type,assessment_date,created_by',
            'prescriptions:id,medical_record_id,prescription_number,status,prescription_date,created_by',
        ]);

        if (! $record) {
            return;
        }

        $recordNumber = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($record->record_number ?: $record->id));
        $archivePath = 'archives/medical-records/' . now()->format('Y/m') . '/' . $recordNumber . '-' . $record->id . '.json';

        $payload = [
            'archived_at' => now()->toIso8601String(),
            'record' => $record->only([
                'id',
                'record_number',
                'patient_id',
                'visit_id',
                'visit_date',
                'subjective',
                'objective',
                'assessment',
                'plan',
                'diagnosis_primary',
                'diagnosis_secondary',
                'icd10_code',
                'icd10_description',
                'procedure_code',
                'procedure_description',
                'notes',
                'is_finalized',
                'finalized_at',
                'finalized_by',
                'created_at',
                'updated_at',
            ]),
            'patient' => $record->patient?->toArray(),
            'visit' => $record->visit?->toArray(),
            'cppts' => $record->cppts->toArray(),
            'assessments' => $record->assessments->toArray(),
            'prescriptions' => $record->prescriptions->toArray(),
        ];

        Storage::disk('local')->put(
            $archivePath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        Log::info('Medical record archived', [
            'record_id' => $record->id,
            'record_number' => $record->record_number,
            'path' => $archivePath,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ArchiveMedicalRecordJob failed after retries', [
            'record_id' => $this->medicalRecord->id ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}

