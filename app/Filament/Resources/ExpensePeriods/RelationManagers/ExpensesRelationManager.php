<?php

namespace App\Filament\Resources\ExpensePeriods\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Расходы';

    public function isReadOnly(): bool
    {
        return false;
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Название')
                ->required()
                ->maxLength(255),
            TextInput::make('amount')
                ->label('Сумма')
                ->numeric()
                ->minValue(0)
                ->required()
                ->suffix(' р'),
            Toggle::make('in_journal')
                ->label('В журнал')
                ->default(true)
                ->helperText('Учитывается в прибыли и налоге. Без галочки — расход только виден (справочно), в расчёт не идёт.'),
            Textarea::make('details')
                ->label('Подробнее')
                ->rows(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                IconColumn::make('in_journal')
                    ->label('В журнале')
                    ->boolean(),
                TextColumn::make('details')
                    ->label('Подробнее')
                    ->placeholder('—')
                    ->wrap()
                    ->lineClamp(3),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить расход')
                    ->visible(fn (): bool => $this->isAdmin()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->isAdmin()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->isAdmin()),
            ]);
    }
}
