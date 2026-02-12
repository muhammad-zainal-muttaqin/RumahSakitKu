<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\ViewRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use BackedEnum;
use UnitEnum;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 2;

    protected static UnitEnum|string|null $navigationGroup = 'Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Role')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Role')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-tag')
                            ->placeholder('contoh: admin, dokter, perawat')
                            ->helperText('Gunakan nama yang deskriptif dan lowercase dengan underscore untuk spasi'),

                        TextInput::make('guard_name')
                            ->label('Guard')
                            ->required()
                            ->default('web')
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-shield-check')
                            ->helperText('Biasanya "web" untuk aplikasi web'),
                    ])
                    ->columns(2),

                Section::make('Permissions')
                    ->icon('heroicon-o-lock-closed')
                    ->collapsible()
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('')
                            ->relationship('permissions', 'name')
                            ->options(fn (): array => Permission::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3)
                            ->gridDirection('row')
                            ->descriptions(fn (): array => Permission::query()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($permission) => [
                                    $permission->id => static::getPermissionDescription($permission->name),
                                ])
                                ->toArray()
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Role')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Jumlah Permission')
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Jumlah User')
                    ->counts('users')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options(fn (): array => Role::query()
                        ->distinct()
                        ->pluck('guard_name', 'guard_name')
                        ->toArray()
                    )
                    ->native(false),

                Filter::make('has_permissions')
                    ->label('Punya Permissions')
                    ->query(fn (Builder $query): Builder => $query->whereHas('permissions'))
                    ->toggle(),

                Filter::make('no_permissions')
                    ->label('Tidak Punya Permissions')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('permissions'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Role?')
                    ->modalDescription('Role yang dihapus tidak dapat dikembalikan. User dengan role ini akan kehilangan akses terkait.')
                    ->hidden(fn (Role $record): bool => in_array($record->name, ['super_admin', 'admin'], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn ($records): bool => $records->contains(fn ($record) => in_array($record->name, ['super_admin', 'admin'], true))),
                ]),
            ])
            ->emptyStateHeading('Belum ada role')
            ->emptyStateDescription('Buat role pertama Anda untuk mengatur hak akses.')
            ->emptyStateIcon('heroicon-o-key');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['permissions', 'users']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) (static::getModel()::count());
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    /**
     * Get human-readable description for a permission.
     */
    protected static function getPermissionDescription(string $permission): string
    {
        $descriptions = [
            // Patient permissions
            'view_patient' => 'Melihat data pasien',
            'create_patient' => 'Mendaftarkan pasien baru',
            'edit_patient' => 'Mengubah data pasien',
            'delete_patient' => 'Menghapus data pasien',
            
            // Visit permissions
            'view_visit' => 'Melihat kunjungan',
            'create_visit' => 'Membuat kunjungan baru',
            'edit_visit' => 'Mengubah kunjungan',
            'delete_visit' => 'Menghapus kunjungan',
            
            // Medical record permissions
            'view_medical_record' => 'Melihat rekam medis',
            'create_medical_record' => 'Membuat rekam medis',
            'edit_medical_record' => 'Mengubah rekam medis',
            
            // Prescription permissions
            'view_prescription' => 'Melihat resep',
            'create_prescription' => 'Membuat resep',
            'edit_prescription' => 'Mengubah resep',
            
            // Medicine permissions
            'view_medicine' => 'Melihat data obat',
            'create_medicine' => 'Menambah data obat',
            'edit_medicine' => 'Mengubah data obat',
            'delete_medicine' => 'Menghapus data obat',
            
            // Employee permissions
            'view_employee' => 'Melihat data pegawai',
            'create_employee' => 'Menambah pegawai',
            'edit_employee' => 'Mengubah data pegawai',
            'delete_employee' => 'Menghapus pegawai',
            
            // User management permissions
            'view_user' => 'Melihat pengguna',
            'create_user' => 'Menambah pengguna',
            'edit_user' => 'Mengubah pengguna',
            'delete_user' => 'Menghapus pengguna',
            
            // Role management permissions
            'view_role' => 'Melihat role',
            'create_role' => 'Membuat role',
            'edit_role' => 'Mengubah role',
            'delete_role' => 'Menghapus role',
            
            // Report permissions
            'view_reports' => 'Melihat laporan',
            'export_reports' => 'Mengekspor laporan',
            
            // Billing permissions
            'view_invoice' => 'Melihat tagihan',
            'create_invoice' => 'Membuat tagihan',
            'edit_invoice' => 'Mengubah tagihan',
            'process_payment' => 'Memproses pembayaran',
            
            // Settings permissions
            'view_settings' => 'Melihat pengaturan',
            'edit_settings' => 'Mengubah pengaturan',
            
            // Audit permissions
            'view_audit_logs' => 'Melihat audit trail',
            
            // Backup permissions
            'manage_backups' => 'Mengelola backup',
            
            // BPJS permissions
            'access_bpjs' => 'Akses BPJS',
            
            // Satu Sehat permissions
            'access_satu_sehat' => 'Akses Satu Sehat',
        ];

        return $descriptions[$permission] ?? 'Permission untuk ' . str_replace('_', ' ', $permission);
    }
}

