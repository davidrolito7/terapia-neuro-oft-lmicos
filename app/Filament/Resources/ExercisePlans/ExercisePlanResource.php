<?php

namespace App\Filament\Resources\ExercisePlans;

use App\Filament\Resources\ExercisePlans\Pages\CreateExercisePlan;
use App\Filament\Resources\ExercisePlans\Pages\EditExercisePlan;
use App\Filament\Resources\ExercisePlans\Pages\ListExercisePlans;
use App\Filament\Resources\ExercisePlans\Schemas\ExercisePlanForm;
use App\Filament\Resources\ExercisePlans\Tables\ExercisePlansTable;
use App\Models\PlanEjercicio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExercisePlanResource extends Resource
{
    protected static ?string $model = PlanEjercicio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Planes de ejercicio';

    protected static ?string $modelLabel = 'plan de ejercicio';

    protected static ?string $pluralModelLabel = 'planes de ejercicio';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ExercisePlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExercisePlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListExercisePlans::route('/'),
            'create' => CreateExercisePlan::route('/create'),
            'edit'   => EditExercisePlan::route('/{record}/edit'),
        ];
    }
}
