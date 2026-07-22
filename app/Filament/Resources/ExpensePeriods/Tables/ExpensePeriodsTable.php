<?php

namespace App\Filament\Resources\ExpensePeriods\Tables;

use App\Models\ExpensePeriod;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensePeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('year')->orderByDesc('month'))
            ->columns([
                TextColumn::make('label')
                    ->label('Месяц')
                    ->state(fn (ExpensePeriod $record): string => $record->label())
                    ->weight('bold'),
                TextColumn::make('revenue')
                    ->label('Выручка (услуги)')
                    ->state(fn (ExpensePeriod $record): float => $record->pnl()['revenue'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('expenses')
                    ->label('Расходы (журнал)')
                    ->state(fn (ExpensePeriod $record): float => $record->pnl()['expenses_journal'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('profit_journal')
                    ->label('Прибыль по журналу')
                    ->state(fn (ExpensePeriod $record): float => $record->pnl()['profit_journal'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->color(fn (ExpensePeriod $record): string => $record->pnl()['profit_journal'] >= 0 ? 'success' : 'danger'),
                TextColumn::make('tax')
                    ->label('Налог')
                    ->state(fn (ExpensePeriod $record): float => $record->pnl()['tax'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->color('warning'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),
            ]);
    }
}
