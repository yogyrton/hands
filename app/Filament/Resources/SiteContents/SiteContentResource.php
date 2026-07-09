<?php

namespace App\Filament\Resources\SiteContents;

use App\Filament\Resources\SiteContents\Pages\EditSiteContent;
use App\Filament\Resources\SiteContents\Pages\ListSiteContents;
use App\Filament\Resources\SiteContents\Schemas\SiteContentForm;
use App\Filament\Resources\SiteContents\Tables\SiteContentsTable;
use App\Models\SiteContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SiteContentResource extends Resource
{
    protected static ?string $model = SiteContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Фото главной';
    }

    public static function getModelLabel(): string
    {
        return 'фото главной';
    }

    public static function getPluralModelLabel(): string
    {
        return 'фото главной';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SiteContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteContentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteContents::route('/'),
            'edit' => EditSiteContent::route('/{record}/edit'),
        ];
    }
}
