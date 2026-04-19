<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $paciente = $this->record;

        $user = User::create([
            'name'     => $paciente->nombre_completo,
            'email'    => "paciente_{$paciente->id}@app.local",
            'password' => bcrypt(Str::random(32)),
        ]);

        $paciente->update(['paciente_user_id' => $user->id]);
    }
}
