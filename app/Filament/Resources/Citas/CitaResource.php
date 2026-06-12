<?php

namespace App\Filament\Resources\Citas;

use App\Filament\Resources\Citas\Pages\CreateCita;
use App\Filament\Resources\Citas\Pages\EditCita;
use App\Filament\Resources\Citas\Pages\ListCitas;
use App\Filament\Resources\Citas\Schemas\CitaForm;
use App\Filament\Resources\Citas\Tables\CitasTable;
use App\Models\Cita;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CitaResource extends Resource
{
    protected static ?string $model = Cita::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Citas';

    protected static ?string $modelLabel = 'cita';

    protected static ?string $pluralModelLabel = 'citas';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Agenda';
    }

    public static function form(Schema $schema): Schema
    {
        return CitaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CitasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCitas::route('/'),
            'create' => CreateCita::route('/create'),
            'edit'   => EditCita::route('/{record}/edit'),
        ];
    }
}
