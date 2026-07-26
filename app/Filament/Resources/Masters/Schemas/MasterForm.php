<?php

namespace App\Filament\Resources\Masters\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
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
                        ->helperText('Адрес страницы: /masters/{slug}'),
                    TextInput::make('name_dative')
                        ->label('Имя в дательном падеже')
                        ->required()
                        ->helperText('Для «Записаться к …»: Дмитрию, Анне, Андрею'),
                    TextInput::make('role')
                        ->label('Специализация')
                        ->required()
                        ->helperText('Например: Массажист · спортивный и классический массаж'),
                    TextInput::make('experience_label')
                        ->label('Опыт')
                        ->helperText('Например: 8 лет'),
                    TextInput::make('salary_rate')
                        ->label('Ставка зарплаты')
                        ->helperText('% от стоимости услуг (грязными)')
                        ->numeric()
                        ->default(35)
                        ->suffix('%')
                        ->required(),
                    TextInput::make('yclients_url')
                        ->label('Ссылка записи (YClients)')
                        ->url()
                        ->required(),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]),

            Section::make('Биография')
                ->schema([
                    Textarea::make('bio1')->label('Абзац 1')->rows(3)->required(),
                    Textarea::make('bio2')->label('Абзац 2')->rows(3)->required(),
                ]),

            Section::make('Подход в работе')
                ->schema([
                    Repeater::make('principles')
                        ->label('Принципы')
                        ->schema([
                            TextInput::make('title')->label('Заголовок')->required(),
                            Textarea::make('description')->label('Описание')->rows(2)->required(),
                        ])
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),

            Section::make('Услуги мастера')
                ->schema([
                    Select::make('services')
                        ->label('Оказывает услуги')
                        ->relationship('services', 'name')
                        ->multiple()
                        ->preload(),
                ]),

            Section::make('SEO')
                ->schema([
                    TextInput::make('seo_title')
                        ->label('SEO title')
                        ->maxLength(255)
                        ->helperText('Если пусто — берётся «Имя — мастер студии HANDS, Могилёв»'),
                    Textarea::make('seo_description')
                        ->label('SEO description')
                        ->rows(4)
                        ->maxLength(255)
                        ->helperText('Если пусто — собирается из имени, специализации и биографии. Заполните вручную, чтобы у мастеров не было одинаковых описаний.'),
                ]),

            Section::make('Фотографии')
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('main')
                        ->label('Главное фото (главная + шапка)')
                        ->collection('main')
                        ->image()
                        ->maxSize(102400),
                    SpatieMediaLibraryFileUpload::make('gallery')
                        ->label('Галерея (до 3 фото)')
                        ->collection('gallery')
                        ->image()
                        ->multiple()
                        ->maxFiles(3)
                        ->reorderable()
                        ->maxSize(102400),
                ]),
        ]);
    }
}
