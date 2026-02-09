<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\PrescriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrescription extends ViewRecord
{
    protected static string $resource = PrescriptionResource::class;

    protected ?string $heading = 'Detail Resep';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit'),
            // Actions\Action::make('print')
            //     ->label('Cetak')
            //     ->icon('heroicon-o-printer')
            //     ->url(fn ($record): string => route('prescriptions.print', $record))
            //     ->openUrlInNewTab(),
        ];
    }
}
