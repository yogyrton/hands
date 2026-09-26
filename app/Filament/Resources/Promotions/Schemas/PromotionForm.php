<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Название')
                ->placeholder('Ранняя пташка')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label('Условие / описание')
                ->placeholder('Сеансы с 9:00 до 12:00')
                ->rows(3),
            TextInput::make('discount_percent')
                ->label('Скидка')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(10)
                ->suffix('%')
                ->required(),
            TextInput::make('sort_order')
                ->label('Порядок')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('Активна')
                ->helperText('Доступна для выбора при оформлении посещения (скидка считается автоматически).')
                ->default(true),
            Toggle::make('show_on_site')
                ->label('Показывать на сайте')
                ->helperText('Выключите для внутренних скидок (например, «Постоянный клиент»): в учёте останется, на витрине сайта не появится.')
                ->default(true),
        ]);
    }
}
