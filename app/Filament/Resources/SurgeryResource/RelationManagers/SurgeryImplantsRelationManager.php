<?php

declare(strict_types=1);

namespace App\Filament\Resources\SurgeryResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Clinical\SurgeryImplant;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class SurgeryImplantsRelationManager extends RelationManager
{
    protected static string $relationship = 'implants';

    protected static ?string $title = 'Implant & BHP';

    protected static ?string $modelLabel = 'Implant/BHP';

    protected static ?string $pluralModelLabel = 'Implant & BHP';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('implant_type')
                    ->label('Jenis Implant/BHP')
                    ->required()
                    ->options(SurgeryImplant::getImplantTypes())
                    ->native(false)
                    ->prefixIcon('heroicon-m-tag')
                    ->searchable(),

                TextInput::make('implant_name')
                    ->label('Nama Implant/BHP')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Hip Prosthesis, Mesh Hernia')
                    ->prefixIcon('heroicon-m-cube'),

                TextInput::make('manufacturer')
                    ->label('Pabrik/Manufaktur')
                    ->maxLength(255)
                    ->placeholder('Contoh: Johnson & Johnson, Stryker')
                    ->prefixIcon('heroicon-m-building-office'),

                TextInput::make('serial_number')
                    ->label('Nomor Seri')
                    ->maxLength(100)
                    ->placeholder('Nomor seri implant')
                    ->prefixIcon('heroicon-m-hashtag'),

                TextInput::make('batch_number')
                    ->label('Nomor Batch')
                    ->maxLength(100)
                    ->placeholder('Nomor batch produksi')
                    ->prefixIcon('heroicon-m-hashtag'),

                Grid::make(3)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->prefixIcon('heroicon-m-calculator'),

                        Select::make('unit')
                            ->label('Satuan')
                            ->required()
                            ->options(SurgeryImplant::getUnits())
                            ->native(false)
                            ->default('pcs'),

                        DatePicker::make('expiry_date')
                            ->label('Tanggal Kadaluarsa')
                            ->native(false)
                            ->prefixIcon('heroicon-m-calendar'),
                    ]),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->placeholder('Catatan tambahan mengenai implant...')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('implant_name')
            ->columns([
                TextColumn::make('implant_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                BadgeColumn::make('implant_type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string =>
                        SurgeryImplant::getImplantTypes()[$state] ?? ucfirst($state)
                    )
                    ->color(fn (string $state): string => match ($state) {
                        'prosthetic', 'orthopedic' => 'primary',
                        'cardiac', 'vascular' => 'danger',
                        'neurosurgery' => 'purple',
                        'ophthalmic' => 'info',
                        'dental' => 'success',
                        'surgical_mesh', 'bone_cement' => 'warning',
                        'plate_screw', 'stent' => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('manufacturer')
                    ->label('Manufaktur')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('serial_number')
                    ->label('No. Seri')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->alignCenter(),

                TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter(),

                TextColumn::make('expiry_date')
                    ->label('Kadaluarsa')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->color(fn ($record) => $record->is_expired ? 'danger' : ($record->is_expiring_soon ? 'warning' : null))
                    ->icon(fn ($record) => $record->is_expired ? 'heroicon-m-exclamation-circle' : null),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('implant_type')
                    ->label('Jenis')
                    ->options(SurgeryImplant::getImplantTypes())
                    ->native(false)
                    ->multiple(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Implant/BHP'),
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
            ->emptyStateHeading('Belum ada implant/BHP')
            ->emptyStateDescription('Tambahkan implant atau BHP yang digunakan dalam operasi ini.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
