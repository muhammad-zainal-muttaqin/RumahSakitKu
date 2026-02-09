<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected static ?string $title = 'Detail Role';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Role')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Role')
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                            ->icon('heroicon-o-tag'),

                        TextEntry::make('guard_name')
                            ->label('Guard')
                            ->badge()
                            ->icon('heroicon-o-shield-check'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Permissions')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextEntry::make('permissions.name')
                            ->label('')
                            ->badge()
                            ->separator(',')
                            ->placeholder('Tidak ada permission'),
                    ]),

                \Filament\Schemas\Components\Section::make('Statistik')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('permissions_count')
                            ->label('Jumlah Permission')
                            ->state(fn ($record): int => $record->permissions->count())
                            ->icon('heroicon-o-lock-closed'),

                        TextEntry::make('users_count')
                            ->label('Jumlah User dengan Role ini')
                            ->state(fn ($record): int => $record->users->count())
                            ->icon('heroicon-o-users'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->hidden(fn ($record): bool => in_array($record->name, ['super_admin', 'admin'], true)),
        ];
    }
}
