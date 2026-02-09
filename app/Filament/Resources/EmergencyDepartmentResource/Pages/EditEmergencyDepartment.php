<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\EmergencyDepartmentResource;
use App\Models\Clinical\Assessment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditEmergencyDepartment extends EditRecord
{
    protected static string $resource = EmergencyDepartmentResource::class;

    protected static ?string $title = 'Edit Pasien IGD';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data pasien IGD berhasil diperbarui';
    }

    /**
     * Handle the update of the record with assessment data.
     *
     * @param Model $record
     * @param array<string, mixed> $data
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update the visit
            $record->update($data);

            // Update or create triage assessment
            $medicalRecord = $record->medicalRecord;

            if ($medicalRecord) {
                $assessment = Assessment::where('medical_record_id', $medicalRecord->id)
                    ->where('assessment_type', 'triage')
                    ->first();

                if ($assessment) {
                    $assessment->update([
                        'chief_complaint' => $data['complaint'] ?? $assessment->chief_complaint,
                        'vital_signs' => $data['vital_signs'] ?? $assessment->vital_signs,
                        'triage_category' => $data['triage_category'] ?? $assessment->triage_category,
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    Assessment::create([
                        'medical_record_id' => $medicalRecord->id,
                        'patient_id' => $record->patient_id,
                        'visit_id' => $record->id,
                        'assessment_type' => 'triage',
                        'assessment_date' => now(),
                        'chief_complaint' => $data['complaint'] ?? null,
                        'vital_signs' => $data['vital_signs'] ?? [],
                        'triage_category' => $data['triage_category'] ?? 'green',
                        'assessed_by' => auth()->id(),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            return $record;
        });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure visit type remains IGD
        $data['visit_type'] = 'igd';

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing triage data
        $record = $this->getRecord();
        $medicalRecord = $record->medicalRecord;

        if ($medicalRecord) {
            $assessment = Assessment::where('medical_record_id', $medicalRecord->id)
                ->where('assessment_type', 'triage')
                ->first();

            if ($assessment) {
                $data['vital_signs'] = $assessment->vital_signs ?? [];
                $data['triage_category'] = $assessment->triage_category ?? 'green';
            }
        }

        return $data;
    }
}
