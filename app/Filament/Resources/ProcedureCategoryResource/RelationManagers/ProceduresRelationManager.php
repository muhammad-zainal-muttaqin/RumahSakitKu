<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcedureCategoryResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class ProceduresRelationManager extends RelationManager
{
    protected static string $relationship = 'procedures';

    protected static ?string $title = 'Tindakan';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('procedure_code')
                    ->label('Kode Tindakan')
                    ->required()
                    ->maxLength(20)
                    ->placeholder('TND001'),

                TextInput::make('name')
                    ->label('Nama Tindakan')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Pemeriksaan Darah'),

                TextInput::make('base_price')
                    ->label('Harga Dasar')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(0.01),

                TextInput::make('bpjs_tariff')
                    ->label('Tarif BPJS')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(0.01),

                TextInput::make('material_cost')
                    ->label('Biaya Bahan')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->step(0.01),

                Toggle::make('is_bpjs_covered')
                    ->label('Ditanggung BPJS')
                    ->default(true),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('procedure_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('base_price')
                    ->label('Harga Dasar')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('bpjs_tariff')
                    ->label('Tarif BPJS')
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                IconColumn::make('is_bpjs_covered')
                    ->label('BPJS')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->alignCenter()
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                TernaryFilter::make('is_bpjs_covered')
                    ->label('Ditanggung BPJS')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Tindakan'),
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
