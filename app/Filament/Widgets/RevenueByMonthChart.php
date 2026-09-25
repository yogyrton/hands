<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BuildsMonthlyFinanceChart;
use Filament\Widgets\ChartWidget;

/**
 * Дашборд: выручка и прибыль по месяцам (выручка = касса по визитам + продажи
 * сертификатов месяца, как в MonthlyFinance). Только админ.
 */
class RevenueByMonthChart extends ChartWidget
{
    use BuildsMonthlyFinanceChart;

    protected ?string $heading = 'Выручка и прибыль по месяцам';

    protected int|string|array $columnSpan = 'full';

    // В самом низу инфопанели — после всех блоков.
    protected static ?int $sort = -3;

    protected ?string $maxHeight = '320px';

    protected function revenueValue(object $finance): float
    {
        return $finance->revenue;
    }

    protected function profitValue(object $finance): float
    {
        return $finance->profit;
    }
}
