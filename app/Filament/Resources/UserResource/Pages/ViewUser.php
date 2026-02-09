<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Detail Pengguna';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Dasar')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Foto Profil')
                            ->circular()
                            ->size(100)
                            ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&size=128')
                            ->columnSpanFull(),

                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Lengkap')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),

                                TextEntry::make('phone')
                                    ->label('Telepon/WhatsApp')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-phone'),

                                TextEntry::make('employee.name')
                                    ->label('Link Pegawai')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-briefcase')
                                    ->url(fn (User $record): ?string => $record->employee_id ? EmployeeResource::getUrl('view', ['record' => $record->employee_id]) : null)
                                    ->openUrlInNewTab(),
                            ]),
                    ]),

                \Filament\Schemas\Components\Section::make('Status & Keamanan')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Status Akun')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                TextEntry::make('roles.name')
                                    ->label('Roles')
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('Tidak ada role'),
                            ]),
                    ]),

                \Filament\Schemas\Components\Section::make('Informasi Login')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('last_login_at')
                                    ->label('Login Terakhir')
                                    ->placeholder('Belum pernah login')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-o-calendar'),

                                TextEntry::make('last_login_ip')
                                    ->label('IP Address Terakhir')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-globe-alt'),

                                TextEntry::make('created_at')
                                    ->label('Akun Dibuat')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-o-calendar-days'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
