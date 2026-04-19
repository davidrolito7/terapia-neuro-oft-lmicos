<?php

namespace App\Filament\Resources\ExercisePlans\Pages;

use App\Filament\Resources\ExercisePlans\ExercisePlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExercisePlan extends EditRecord
{
    protected static string $resource = ExercisePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
