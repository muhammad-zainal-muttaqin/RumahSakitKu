<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadiologyOrderResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\RadiologyOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRadiologyOrder extends ViewRecord
{
    protected static string $resource = RadiologyOrderResource::class;

    protected static ?string $title = 'Detail Order Radiologi';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
