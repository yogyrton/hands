<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Models\PayrollPeriod;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('year')->orderByDesc('month'))
            ->columns([
                TextColumn::make('label')
                    ->label('Месяц')
                    ->state(fn (PayrollPeriod $record): string => $record->label())
                    ->weight('bold'),
                TextColumn::make('accrued')
                    ->label('Начислено')
                    ->state(fn (PayrollPeriod $record): float => $record->totals()['accrued'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('paid')
                    ->label('Выплачено')
                    ->state(fn (PayrollPeriod $record): float => $record->totals()['paid'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р'),
                TextColumn::make('debt')
                    ->label('Долг')
                    ->state(fn (PayrollPeriod $record): float => $record->totals()['debt'])
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' р')
                    ->color(fn (PayrollPeriod $record): string => $record->totals()['debt'] > 0 ? 'danger' : 'success'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),
            ]);
    }
}
