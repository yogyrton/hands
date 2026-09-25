<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Support\MonthlyFinance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Дашборд: выручка и прибыль по месяцам за последний год — столбиками.
 * Считается тем же MonthlyFinance, что и блок текущего месяца. Только админ
 * (инфопанель мастеру недоступна).
 */
class RevenueByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Выручка и прибыль по месяцам';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -5;

    protected ?string $maxHeight = '320px';

    private const MONTHS_SHORT = [
        1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'май', 6 => 'июн',
        7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек',
    ];

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

            // Только реальные месяцы: где есть хотя бы один визит. Пустые (в т.ч.
            // до открытия студии) пропускаем; новый месяц появится сам с данными.
            while ($cursor->lessThanOrEqualTo($end)) {
                $hasVisits = Visit::query()
                    ->whereBetween('performed_at', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
                    ->exists();

                if ($hasVisits) {
                    $finance = MonthlyFinance::for($cursor->year, $cursor->month);
                    $labels[] = self::MONTHS_SHORT[$cursor->month].' '.substr((string) $cursor->year, -2);
                    $revenue[] = $finance->revenue;
                    $profit[] = $finance->profit;
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
