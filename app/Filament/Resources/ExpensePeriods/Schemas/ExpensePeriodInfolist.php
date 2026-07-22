<?php

namespace App\Filament\Resources\ExpensePeriods\Schemas;

use App\Models\ExpensePeriod;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpensePeriodInfolist
{
    private static function money(float $value): string
    {
        return number_format($value, 2, '.', ' ').' р';
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Итоги за месяц')
                ->description('«По журналу» — только официальные расходы (с ним считается налог). «Полная» — с учётом всех расходов.')
                ->columns(4)
                ->schema([
                    TextEntry::make('revenue')
                        ->label('Выручка')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['revenue'])),
                    TextEntry::make('expenses')
                        ->label('Расходы (журнал / все)')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['expenses_journal'])
                            .' / '.self::money($record->pnl()['expenses_all'])),
                    TextEntry::make('profit_journal')
                        ->label('Прибыль по журналу')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['profit_journal']))
                        ->color(fn (ExpensePeriod $record): string => $record->pnl()['profit_journal'] >= 0 ? 'success' : 'danger'),
                    TextEntry::make('profit_full')
                        ->label('Прибыль полная')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['profit_full']))
                        ->color(fn (ExpensePeriod $record): string => $record->pnl()['profit_full'] >= 0 ? 'success' : 'danger'),
                    TextEntry::make('tax')
                        ->label(fn (ExpensePeriod $record): string => 'Налог ('.rtrim(rtrim(number_format($record->pnl()['tax_rate'], 2, '.', ''), '0'), '.').'%)')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['tax']))
                        ->color('warning'),
                    TextEntry::make('profit_after_tax')
                        ->label('Прибыль после налога')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['profit_after_tax']))
                        ->weight('bold')
                        ->color(fn (ExpensePeriod $record): string => $record->pnl()['profit_after_tax'] >= 0 ? 'success' : 'danger'),
                ]),
        ]);
    }
}
