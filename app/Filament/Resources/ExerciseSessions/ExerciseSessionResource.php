<?php

namespace App\Filament\Resources\ExerciseSessions;

use App\Filament\Resources\ExerciseSessions\Pages\CreateExerciseSession;
use App\Filament\Resources\ExerciseSessions\Pages\EditExerciseSession;
use App\Filament\Resources\ExerciseSessions\Pages\ListExerciseSessions;
use App\Filament\Resources\ExerciseSessions\Schemas\ExerciseSessionForm;
use App\Filament\Resources\ExerciseSessions\Tables\ExerciseSessionsTable;
use App\Models\SesionEjercicio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExerciseSessionResource extends Resource
{
    protected static ?string $model = SesionEjercicio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Sesiones';

    protected static ?string $modelLabel = 'sesión';

    protected static ?string $pluralModelLabel = 'sesiones';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ExerciseSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExerciseSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListExerciseSessions::route('/'),
            'create' => CreateExerciseSession::route('/create'),
            'edit'   => EditExerciseSession::route('/{record}/edit'),
        ];
    }
}
