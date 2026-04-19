<?php

namespace App\Filament\Resources\ExercisePlans\Pages;

use App\Filament\Resources\ExercisePlans\ExercisePlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExercisePlans extends ListRecords
{
    protected static string $resource = ExercisePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
