<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('card')
                    ->label('Фото')
                    ->collection('card')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Проработка')
                    ->formatStateUsing(fn (int $state): string => "{$state}/5")
                    ->badge(),
                TextColumn::make('price_label')
                    ->label('Цена'),
                TextColumn::make('slug')
                    ->label('URL')
                    ->color('gray'),
                ToggleColumn::make('is_active')
                    ->label('Активна'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
