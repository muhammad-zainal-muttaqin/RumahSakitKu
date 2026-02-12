<?php

declare(strict_types=1);

namespace App\Jobs\Bpjs;

use App\Models\Patient\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncVisitToBpjsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Visit $visit
    ) {
    }

    public function handle(): void
    {
        $visit = $this->visit->fresh(['patient', 'polyclinic', 'doctor']);

        if (! $visit) {
            return;
        }

        $patient = $visit->patient;

        if (! $patient || $patient->insurance_type !== 'bpjs' || empty($patient->bpjs_number)) {
            return;
        }

        if (! empty($visit->bpjs_sep_number)) {
            return;
        }

        if (empty($patient->bpjs_card_number) && ! empty($patient->bpjs_number)) {
            $patient->update([
                'bpjs_card_number' => $patient->bpjs_number,
            ]);
        }

        CreateSepJob::dispatch($visit->id);

        Log::info('Queued SEP creation for BPJS visit', [
            'visit_id' => $visit->id,
            'visit_number' => $visit->visit_number,
            'patient_id' => $patient->id,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SyncVisitToBpjsJob failed after retries', [
            'visit_id' => $this->visit->id ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}

