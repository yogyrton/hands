<?php

namespace App\Filament\Resources\SiteContents\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('SEO главной страницы')
                ->schema([
                    TextInput::make('seo_title')
                        ->label('SEO title')
                        ->helperText('Заголовок вкладки и в поиске. До ~60 символов.')
                        ->maxLength(255),
                    Textarea::make('seo_description')
                        ->label('SEO description')
                        ->helperText('Описание для поиска и соцсетей. ~150–160 символов.')
                        ->rows(10),
                ]),

            Section::make('Большие фото главной страницы')
                ->columns(2)
                ->schema([
                    SpatieMediaLibraryFileUpload::make('home_hero')
                        ->label('Фото в шапке (до услуг)')
                        ->collection('home_hero')
                        ->image()
                        ->maxSize(102400),
                    SpatieMediaLibraryFileUpload::make('home_about')
                        ->label('Фото в блоке «О студии»')
                        ->collection('home_about')
                        ->image()
                        ->maxSize(102400),
                ]),
        ]);
    }
}
