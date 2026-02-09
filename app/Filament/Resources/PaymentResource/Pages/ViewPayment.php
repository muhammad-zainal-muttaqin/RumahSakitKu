<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn ($record) => !$record->is_refunded),
            Action::make('print')
                ->label('Cetak Kwitansi')
                ->icon('heroicon-m-printer')
                ->color('info')
                ->url(fn ($record): string => route('payments.print', $record))
                ->openUrlInNewTab(),
        ];
    }
}
