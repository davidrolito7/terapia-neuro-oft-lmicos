<?php

namespace App\Filament\Resources\ExercisePlans\Pages;

use App\Filament\Resources\ExercisePlans\ExercisePlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExercisePlan extends CreateRecord
{
    protected static string $resource = ExercisePlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
