<?php

declare(strict_types=1);

namespace App\Filament\Resources\AssessmentResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssessment extends EditRecord
{
    protected static string $resource = AssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Lihat'),
            DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Asesmen berhasil diperbarui';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure vital_signs is properly structured
        if (!isset($data['vital_signs'])) {
            $data['vital_signs'] = [];
        }

        // Ensure physical_examination is properly structured
        if (!isset($data['physical_examination'])) {
            $data['physical_examination'] = [];
        }

        // Calculate and store BMI if weight and height are provided
        if (!empty($data['vital_signs']['weight_kg']) && !empty($data['vital_signs']['height_cm'])) {
            $weight = (float) $data['vital_signs']['weight_kg'];
            $height = (float) $data['vital_signs']['height_cm'] / 100; // Convert to meters
            if ($height > 0) {
                $data['vital_signs']['bmi'] = round($weight / ($height * $height), 2);
            }
        }

        return $data;
    }
}
