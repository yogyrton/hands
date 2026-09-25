<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Concerns;

use App\Models\Visit;
use App\Support\MonthlyFinance;
use Illuminate\Support\Carbon;

/**
 * Общая сборка данных для помесячных графиков «выручка/прибыль». Показывает
 * только месяцы, где есть визиты (от первого до текущего). Конкретный виджет
 * задаёт, как из MonthlyFinance взять выручку и прибыль (с сертификатами или
 * без) — через revenueValue()/profitValue().
 */
trait BuildsMonthlyFinanceChart
{
    private const MONTHS_SHORT = [
        1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'май', 6 => 'июн',
        7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек',
    ];

    abstract protected function revenueValue(object $finance): float;

    abstract protected function profitValue(object $finance): float;

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $labels = [];
        $revenue = [];
        $profit = [];

        $first = Visit::query()->min('performed_at');

        if ($first !== null) {
            $cursor = Carbon::parse($first)->startOfMonth();
            $end = Carbon::now()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($end)) {
                $hasVisits = Visit::query()
                    ->whereBetween('performed_at', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
                    ->exists();

                if ($hasVisits) {
                    $finance = MonthlyFinance::for($cursor->year, $cursor->month);
                    $labels[] = self::MONTHS_SHORT[$cursor->month].' '.substr((string) $cursor->year, -2);
                    $revenue[] = $this->revenueValue($finance);
                    $profit[] = $this->profitValue($finance);
                }

                $cursor->addMonthNoOverflow();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Выручка',
                    'data' => $revenue,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.55)',
                    'borderColor' => '#22c55e',
                ],
                [
                    'label' => 'Прибыль',
                    'data' => $profit,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.55)',
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
