<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                            if ($operation === 'create') {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('URL (slug)')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Адрес страницы: /services/{slug}'),
                    Select::make('level')
                        ->label('Проработка')
                        ->options([1 => '1/5', 2 => '2/5', 3 => '3/5', 4 => '4/5', 5 => '5/5'])
                        ->default(3)
                        ->required(),
                    TextInput::make('base_price')
                        ->label('Базовая цена')
                        ->numeric()
                        ->default(0)
                        ->suffix('р')
                        ->required(),
                    TextInput::make('duration_label')
                        ->label('Подпись длительности')
                        ->default('от 60 мин')
                        ->required(),
                    TextInput::make('price_label')
                        ->label('Подпись цены')
                        ->default('от 50 р')
                        ->required(),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                ]),

            Section::make('Тексты')
                ->schema([
                    Textarea::make('lead')
                        ->label('Вводный абзац (lead)')
                        ->rows(3)
                        ->required(),
                    TextInput::make('ideal')
                        ->label('Идеально, если хочется'),
                    TextInput::make('request_lead')
                        ->label('Подзаголовок «Работаем по запросу»'),
                ]),

            Section::make('Что входит')
                ->schema([
                    Repeater::make('includes')
                        ->label('Пункты')
                        ->columns(3)
                        ->schema([
                            TextInput::make('n')->label('№')->numeric(),
                            TextInput::make('title')->label('Заголовок')->required(),
                            Textarea::make('description')->label('Описание')->rows(2),
                        ])
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            Section::make('Работаем по запросу')
                ->schema([
                    TagsInput::make('requests')
                        ->label('Чипы')
                        ->placeholder('Добавить и Enter'),
                ]),

            Section::make('Подробно об услуге')
                ->schema([
                    Repeater::make('details')
                        ->label('Блоки')
                        ->schema([
                            TextInput::make('title')->label('Заголовок')->required(),
                            Textarea::make('body')->label('Текст')->rows(3)->required(),
                        ])
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            Section::make('Фото услуги')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('card')
                        ->label('Фото услуги')
                        ->helperText('Используется в карточке на главной и в шапке страницы услуги')
                        ->collection('card')
                        ->image()
                        ->maxSize(102400),
                ]),

            Section::make('SEO')
                ->schema([
                    TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                    Textarea::make('seo_description')->label('SEO description')->rows(10),
                ]),
        ]);
    }
}
