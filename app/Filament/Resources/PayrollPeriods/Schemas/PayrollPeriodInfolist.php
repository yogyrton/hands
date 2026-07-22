<?php

namespace App\Filament\Resources\PayrollPeriods\Schemas;

use App\Models\PayrollPeriod;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('label')
                        ->label('Период')
                        ->state(fn (PayrollPeriod $record): string => $record->label())
                        ->weight('bold'),
                    TextEntry::make('accrued')
                        ->label('Начислено всего')
                        ->state(fn (PayrollPeriod $record): string => number_format($record->totals()['accrued'], 2, '.', ' ').' р'),
                    TextEntry::make('paid')
                        ->label('Выплачено')
                        ->state(fn (PayrollPeriod $record): string => number_format($record->totals()['paid'], 2, '.', ' ').' р'),
                    TextEntry::make('debt')
                        ->label('Долг')
                        ->state(fn (PayrollPeriod $record): string => number_format($record->totals()['debt'], 2, '.', ' ').' р')
                        ->color(fn (PayrollPeriod $record): string => $record->totals()['debt'] > 0 ? 'danger' : 'success'),
                ]),
        ]);
    }
}
