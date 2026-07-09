<?php

namespace App\Filament\Resources\Masters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MastersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('main')
                    ->label('Фото')
                    ->collection('main')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Специализация')
                    ->limit(40)
                    ->color('gray'),
                TextColumn::make('experience_label')
                    ->label('Опыт'),
                ToggleColumn::make('is_active')
                    ->label('Активен'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
