<?php

namespace App\Filament\Resources\Prices\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class PricesRelationManager extends RelationManager
{
    protected static string $relationship = 'prices';

    protected static ?string $title = 'Прайс — цены по длительности';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('duration_minutes')
                ->label('Длительность')
                ->numeric()
                ->minValue(1)
                ->suffix('мин')
                ->required()
                // Одна длительность на услугу.
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('service_id', $this->getOwnerRecord()->getKey()),
                ),
            TextInput::make('price_master')
                ->label('Цена — мастер')
                ->numeric()
                ->minValue(0)
                ->suffix('р')
                ->required(),
            TextInput::make('price_pro')
                ->label('Цена — про мастер')
                ->numeric()
                ->minValue(0)
                ->suffix('р')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('duration_minutes')
            ->columns([
                TextColumn::make('duration_minutes')
                    ->label('Длительность')
                    ->suffix(' мин')
                    ->sortable(),
                TextColumn::make('price_master')
                    ->label('Мастер')
                    ->suffix(' р'),
                TextColumn::make('price_pro')
                    ->label('Про мастер')
                    ->suffix(' р'),
            ])
            ->headerActions([
                CreateAction::make()->label('Добавить длительность'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Пока нет цен')
            ->emptyStateDescription('Добавьте длительность и цены для мастера и про-мастера.');
    }
}
