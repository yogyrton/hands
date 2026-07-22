<?php

namespace App\Filament\Resources\PayrollPeriods\Schemas;

use App\Models\PayrollPeriod;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PayrollPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        // Период обычно заводят по завершении месяца (июль — в начале августа),
        // поэтому по умолчанию подставляем предыдущий месяц.
        $previous = Carbon::now()->subMonthNoOverflow();

        return $schema->components([
            Select::make('month')
                ->label('Месяц')
                ->options(PayrollPeriod::monthOptions())
                ->default($previous->month)
                ->required()
                ->rule(fn (Get $get): callable => function (string $attribute, mixed $value, callable $fail) use ($get): void {
                    $exists = PayrollPeriod::query()
                        ->where('year', $get('year'))
                        ->where('month', $value)
                        ->exists();

                    if ($exists) {
                        $fail('Период за этот месяц уже создан.');
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
