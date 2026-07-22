<?php

namespace App\Filament\Resources\ExpensePeriods\Schemas;

use App\Models\ExpensePeriod;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ExpensePeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        // Месяц расходов обычно заводят по его завершении — по умолчанию прошлый.
        $previous = Carbon::now()->subMonthNoOverflow();

        return $schema->components([
            Select::make('month')
                ->label('Месяц')
                ->options(ExpensePeriod::monthOptions())
                ->default($previous->month)
                ->required()
                ->rule(fn (Get $get): callable => function (string $attribute, mixed $value, callable $fail) use ($get): void {
                    $exists = ExpensePeriod::query()
                        ->where('year', $get('year'))
                        ->where('month', $value)
                        ->exists();

                    if ($exists) {
                        $fail('Месяц расходов уже создан.');
                    }
                }),
            Select::make('year')
                ->label('Год')
                ->options(collect(range($previous->year - 1, $previous->year + 1))
                    ->mapWithKeys(fn (int $y): array => [$y => (string) $y])
                    ->all())
                ->default($previous->year)
                ->required(),
        ]);
    }
}
