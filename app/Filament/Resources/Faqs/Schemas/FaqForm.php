<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')
                ->label('Вопрос')
                ->required()
                ->maxLength(255),
            Textarea::make('answer')
                ->label('Ответ')
                ->rows(4)
                ->required(),
            TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('Активен')
                ->default(true),
        ]);
    }
}
