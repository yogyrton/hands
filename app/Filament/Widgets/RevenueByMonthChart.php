<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

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

        // Последние 12 месяцев по возрастанию (слева старые, справа текущий).
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);
            $finance = MonthlyFinance::for($month->year, $month->month);

            $labels[] = self::MONTHS_SHORT[$month->month].' '.substr((string) $month->year, -2);
            $revenue[] = $finance->revenue;
            $profit[] = $finance->profit;
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
