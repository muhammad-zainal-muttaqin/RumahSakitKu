<?php

declare(strict_types=1);

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Patient\Visit;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use BackedEnum;
use UnitEnum;

class VisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'visits';

    protected static ?string $title = 'Riwayat Kunjungan';

    protected static ?string $recordTitleAttribute = 'visit_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('visit_number')
                    ->label('No. Kunjungan')
                    ->required()
                    ->maxLength(20),

                DatePicker::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->required()
                    ->native(false),

                Select::make('visit_type')
                    ->label('Jenis Kunjungan')
                    ->required()
                    ->options([
                        'rawat_jalan' => 'Rawat Jalan',
                        'rawat_inap' => 'Rawat Inap',
                        'igd' => 'IGD',
                        'lab' => 'Laboratorium',
                    ])
                    ->native(false),

                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options([
                        'menunggu' => 'Menunggu',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->native(false),

                Textarea::make('complaint')
                    ->label('Keluhan')
                    ->maxLength(65535)
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('visit_number')
            ->columns([
                TextColumn::make('visit_number')
                    ->label('No. Kunjungan')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('visit_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                BadgeColumn::make('visit_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rawat_jalan' => 'Rawat Jalan',
                        'rawat_inap' => 'Rawat Inap',
                        'igd' => 'IGD',
                        'lab' => 'Laboratorium',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'rawat_jalan' => 'primary',
                        'rawat_inap' => 'success',
                        'igd' => 'danger',
                        'lab' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('polyclinic.name')
                    ->label('Poliklinik')
                    ->placeholder('-'),

                TextColumn::make('doctor.name')
                    ->label('Dokter')
                    ->placeholder('-'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu' => 'Menunggu',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'diproses' => 'primary',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('complaint')
                    ->label('Keluhan')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),

                IconColumn::make('is_completed')
                    ->label('Selesai')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visit_type')
                    ->label('Jenis Kunjungan')
                    ->options([
                        'rawat_jalan' => 'Rawat Jalan',
                        'rawat_inap' => 'Rawat Inap',
                        'igd' => 'IGD',
                        'lab' => 'Laboratorium',
                    ])
                    ->native(false),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->native(false),

                Filter::make('visit_date')
                    ->label('Tanggal Kunjungan')
                    ->schema([
                        DatePicker::make('visit_date_from')
                            ->label('Dari'),
                        DatePicker::make('visit_date_until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['visit_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                            )
                            ->when(
                                $data['visit_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Kunjungan'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('visit_date', 'desc');
    }
}
