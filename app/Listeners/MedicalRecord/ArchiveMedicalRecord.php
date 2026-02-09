<?php

declare(strict_types=1);

namespace App\Listeners\MedicalRecord;

use App\Events\MedicalRecord\MedicalRecordFinalized;
use App\Jobs\ArchiveMedicalRecordJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ArchiveMedicalRecord implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public function handle(MedicalRecordFinalized $event): void
    {
        // Dispatch job to archive the medical record
        ArchiveMedicalRecordJob::dispatch($event->medicalRecord);
        
        // Log archive initiation
        activity()
            ->performedOn($event->medicalRecord)
            ->log('medical_record_archive_initiated');
    }
}
