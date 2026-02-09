<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Pages;

use App\Filament\Resources\EmergencyDepartmentResource;
use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateEmergencyDepartment extends CreateRecord
{
    protected static string $resource = EmergencyDepartmentResource::class;

    protected static ?string $title = 'Tambah Pasien IGD';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Handle the creation of the record with assessment data.
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create the visit
            $visit = parent::handleRecordCreation($data);

            // Create medical record
            $medicalRecord = MedicalRecord::create([
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'record_number' => MedicalRecord::generateRecordNumber(),
                'visit_date' => $visit->visit_date,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create triage assessment
            Assessment::create([
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'assessment_type' => 'triage',
                'assessment_date' => now(),
                'chief_complaint' => $data['complaint'] ?? null,
                'vital_signs' => $data['vital_signs'] ?? [],
                'triage_category' => $data['triage_category'] ?? 'green',
                'assessed_by' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            return $visit;
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pasien IGD berhasil didaftarkan';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure visit type is IGD
        $data['visit_type'] = 'igd';
        $data['registration_type'] = 'baru';

        // Generate visit number if not set
        if (empty($data['visit_number'])) {
            $data['visit_number'] = EmergencyDepartmentResource::generateVisitNumber();
        }

        return $data;
    }
}
