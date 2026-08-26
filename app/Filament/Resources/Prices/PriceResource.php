<?php

namespace App\Filament\Resources\Prices;

use App\Filament\Resources\Prices\Pages\EditPrice;
use App\Filament\Resources\Prices\Pages\ListPrices;
use App\Filament\Resources\Prices\RelationManagers\PricesRelationManager;
use App\Models\Service;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * «Прайс» — отдельный пункт меню: список услуг, при переходе в услугу
 * управляем её ценами (длительности + цена мастера / про-мастера).
 * Модель та же (Service), но здесь редактируем только прайс, не саму услугу.
 */
class PriceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Сайт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Прайс';
    }

    public static function getModelLabel(): string
    {
        return 'прайс услуги';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Прайс';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('name')
                ->label('Услуга')
                ->content(fn (Service $record): string => $record->name),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->recordUrl(fn (Service $record): string => EditPrice::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Услуга')
                    ->searchable(),
                TextColumn::make('prices_count')
                    ->label('Строк прайса')
                    ->counts('prices')
                    ->badge(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PricesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrices::route('/'),
            'edit' => EditPrice::route('/{record}/edit'),
        ];
    }
}
