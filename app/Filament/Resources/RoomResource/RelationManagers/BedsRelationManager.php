<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoomResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\MasterData\Bed;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class BedsRelationManager extends RelationManager
{
    protected static string $relationship = 'beds';

    protected static ?string $title = 'Tempat Tidur';

    protected static ?string $recordTitleAttribute = 'bed_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('bed_number')
                    ->label('Nomor Tempat Tidur')
                    ->required()
                    ->maxLength(20),

                TextInput::make('bed_name')
                    ->label('Nama Tempat Tidur')
                    ->maxLength(50)
                    ->placeholder('Bed A1'),

                Select::make('bed_type')
                    ->label('Tipe')
                    ->required()
                    ->options([
                        'standard' => 'Standard',
                        'elektrik' => 'Elektrik',
                        'air' => 'Air',
                        'bayi' => 'Bayi',
                        'iso' => 'Isolasi',
                    ])
                    ->default('standard')
                    ->native(false),

                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options([
                        'kosong' => 'Kosong',
                        'terisi' => 'Terisi',
                        'reserved' => 'Dipesan',
                        'maintenance' => 'Maintenance',
                        'cleaning' => 'Dibersihkan',
                    ])
                    ->default('kosong')
                    ->native(false)
                    ->live(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->rows(2),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bed_number')
            ->columns([
                TextColumn::make('bed_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bed_name')
                    ->label('Nama')
                    ->searchable()
                    ->placeholder('-'),

                BadgeColumn::make('bed_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'elektrik' => 'Elektrik',
                        'air' => 'Air',
                        'bayi' => 'Bayi',
                        'iso' => 'Isolasi',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'gray',
                        'elektrik' => 'primary',
                        'air' => 'info',
                        'bayi' => 'warning',
                        'iso' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kosong' => 'Kosong',
                        'terisi' => 'Terisi',
                        'reserved' => 'Dipesan',
                        'maintenance' => 'Maintenance',
                        'cleaning' => 'Dibersihkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kosong' => 'success',
                        'terisi' => 'danger',
                        'reserved' => 'warning',
                        'maintenance' => 'gray',
                        'cleaning' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('currentVisit.patient.name')
                    ->label('Pasien')
                    ->placeholder('-')
                    ->visible(fn (Model $record): bool => $record->status === 'terisi'),

                TextColumn::make('occupied_at')
                    ->label('Dihuni Sejak')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'kosong' => 'Kosong',
                        'terisi' => 'Terisi',
                        'reserved' => 'Dipesan',
                        'maintenance' => 'Maintenance',
                        'cleaning' => 'Dibersihkan',
                    ])
                    ->native(false),

                SelectFilter::make('bed_type')
                    ->label('Tipe')
                    ->options([
                        'standard' => 'Standard',
                        'elektrik' => 'Elektrik',
                        'air' => 'Air',
                        'bayi' => 'Bayi',
                        'iso' => 'Isolasi',
                    ])
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Tempat Tidur'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
