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
                ->description('Выручка = оплаченные визиты + продажи сертификатов. Прибыль = выручка − расходы «в журнале». Расходы не в журнале — только для справки, в расчёт не идут.')
                ->columns(3)
                ->schema([
                    TextEntry::make('revenue')
                        ->label('Выручка')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['revenue']))
                        ->helperText(fn (ExpensePeriod $record): string => 'визиты '.self::money($record->pnl()['revenue_visits'])
                            .' + сертификаты '.self::money($record->pnl()['revenue_certs'])),
                    TextEntry::make('expenses_journal')
                        ->label('Расходы (в журнале)')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['expenses_journal']))
                        ->helperText(fn (ExpensePeriod $record): string => 'не в журнале (справочно): '.self::money($record->pnl()['expenses_non_journal'])),
                    TextEntry::make('profit')
                        ->label('Прибыль')
                        ->state(fn (ExpensePeriod $record): string => self::money($record->pnl()['profit']))
                        ->color(fn (ExpensePeriod $record): string => $record->pnl()['profit'] >= 0 ? 'success' : 'danger'),
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
