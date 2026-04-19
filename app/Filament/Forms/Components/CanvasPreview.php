<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\View;

class CanvasPreview extends View
{
    public static function make(string $name = 'canvas_preview'): static
    {
        return parent::make('filament.forms.components.canvas-preview');
    }
}
