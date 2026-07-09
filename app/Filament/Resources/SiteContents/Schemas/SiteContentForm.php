<?php

namespace App\Filament\Resources\SiteContents\Schemas;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
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
