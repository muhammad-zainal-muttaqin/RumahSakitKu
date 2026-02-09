<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;

/**
 * User Resource
 * 
 * Filament resource for managing system users.
 * 
 * @package App\Filament\Resources
 */

use App\Models\MasterData\Employee;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?int $navigationSort = 1;

    protected static UnitEnum|string|null $navigationGroup = 'Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->image()
                            ->directory('user-avatars')
                            ->maxSize(1024)
                            ->imageEditor()
                            ->circleCropper()
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-user'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-envelope'),

                        TextInput::make('phone')
                            ->label('Telepon/WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-m-phone'),

                        Select::make('employee_id')
                            ->label('Link ke Pegawai')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Hubungkan user dengan data pegawai untuk akses fitur medis')
                            ->placeholder('Pilih pegawai (opsional)'),
                    ])
                    ->columns(2),

                Section::make('Keamanan')
                    ->icon('heroicon-o-shield-exclamation')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->helperText('Minimal 8 karakter')
                            ->prefixIcon('heroicon-m-lock-closed'),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-lock-closed'),
                    ])
                    ->columns(2),

                Section::make('Status & Peran')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->helperText('Nonaktifkan untuk menonaktifkan akses user')
                            ->default(true)
                            ->live(),

                        Placeholder::make('status_info')
                            ->label('Status')
                            ->content(fn (Get $get): string => $get('is_active') ? '✅ Akun Aktif' : '❌ Akun Nonaktif')
                            ->visible(fn (string $context): bool => $context === 'edit'),

                        Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->options(fn (): array => Role::query()->pluck('name', 'id')->toArray())
                            ->helperText('Pilih peran untuk user ini'),
                    ])
                    ->columns(2),

                Section::make('Informasi Login Terakhir')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->schema([
                        Placeholder::make('last_login_at')
                            ->label('Login Terakhir')
                            ->content(fn (?User $record): string => $record?->last_login_at?->diffForHumans() ?? 'Belum pernah login'),

                        Placeholder::make('last_login_ip')
                            ->label('IP Terakhir')
                            ->content(fn (?User $record): string => $record?->last_login_ip ?? '-'),
                    ])
                    ->columns(2)
                    ->visible(fn (string $context): bool => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->url(fn (User $record): ?string => $record->employee_id ? EmployeeResource::getUrl('view', ['record' => $record->employee_id]) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label('Login Terakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum login')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status Akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),

                Filter::make('has_employee')
                    ->label('Punya Link Pegawai')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('employee_id'))
                    ->toggle(),

                Filter::make('never_logged_in')
                    ->label('Belum Pernah Login')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? 'Nonaktifkan User?' : 'Aktifkan User?')
                    ->modalDescription(fn (User $record): string => $record->is_active 
                        ? 'User tidak akan bisa login setelah dinonaktifkan.' 
                        : 'User akan dapat login kembali.')
                    ->action(fn (User $record) => $record->update(['is_active' => !$record->is_active])),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User?')
                    ->modalDescription('User akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Buat pengguna pertama Anda untuk memulai.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'roles']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
