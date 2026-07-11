<?php

namespace App\Filament\Resources\Cabinets\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CabinetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->placeholder('Лесной')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            // Подставляем slug из названия только пока он пуст —
                            // ручное значение никогда не перезатираем.
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Код (slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Заполняется из названия, но можно изменить вручную.'),
                    Textarea::make('description')
                        ->label('Описание')
                        ->placeholder('Природные фактуры, приглушённый зелёный свет и мягкие текстуры — как прогулка в тихом лесу.')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Показывать на сайте')
                        ->default(true),
                ]),

            Section::make('Фотографии')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('photos')
                        ->label('Фото кабинета (до 3)')
                        ->collection('photos')
                        ->image()
                        ->multiple()
                        ->maxFiles(3)
                        ->reorderable()
                        ->maxSize(102400)
                        ->helperText('Загрузите до 3 фото — на сайте они листаются каруселью.'),
                ]),
        ]);
    }
}
