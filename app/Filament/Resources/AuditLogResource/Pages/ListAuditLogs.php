<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for audit logs
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->badge(AuditLog::query()->count())
                ->badgeColor('gray'),

            'created' => \Filament\Schemas\Components\Tabs\Tab::make('Create')
                ->badge(AuditLog::query()->where('event', 'created')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-plus-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('event', 'created')),

            'updated' => \Filament\Schemas\Components\Tabs\Tab::make('Update')
                ->badge(AuditLog::query()->where('event', 'updated')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('event', 'updated')),

            'deleted' => \Filament\Schemas\Components\Tabs\Tab::make('Delete')
                ->badge(AuditLog::query()->whereIn('event', ['deleted', 'force_deleted'])->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('event', ['deleted', 'force_deleted'])),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'all';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add stats widgets here
        ];
    }
}
