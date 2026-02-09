<?php

declare(strict_types=1);

namespace App\Filament\Resources\InpatientResource\Pages;

use App\Models\Patient\Visit;
use App\Filament\Resources\InpatientResource;
use App\Services\InpatientService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInpatient extends CreateRecord
{
    protected static string $resource = InpatientResource::class;

    protected static ?string $title = 'Daftar Rawat Inap Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set visit type to rawat_inap
        $data['visit_type'] = 'rawat_inap';
        $data['status'] = 'registered';
        $data['inpatient_status'] = 'registered';
        $data['visit_date'] = $data['visit_date'] ?? now()->toDateString();
        
        // Generate visit number
        $data['visit_number'] = $this->generateVisitNumber();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Admit patient to bed if room and bed are selected
        $data = $this->form->getState();
        
        if (!empty($data['bed_id'])) {
            $inpatientService = app(InpatientService::class);
            $inpatientService->admitPatient($this->record->id, $data['room_id'], $data['bed_id']);
            
            Notification::make()
                ->title('Pasien berhasil didaftarkan dan ditempatkan di kamar')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Pasien berhasil didaftarkan')
                ->success()
                ->body('Silakan pilih kamar untuk menempatkan pasien.')
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Generate a unique visit number for inpatient.
     * Format: RI-YYYYMMDD-XXXX
     */
    private function generateVisitNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "RI-{$date}-";
        
        $lastVisit = Visit::where('visit_number', 'like', "{$prefix}%")
            ->orderBy('visit_number', 'desc')
            ->first();
        
        if ($lastVisit) {
            $lastNumber = (int) substr($lastVisit->visit_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
