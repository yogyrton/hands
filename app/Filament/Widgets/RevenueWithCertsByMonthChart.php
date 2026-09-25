<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BuildsMonthlyFinanceChart;
use Filament\Widgets\ChartWidget;

/**
 * Дашборд: выручка и прибыль по месяцам с учётом сертификатов. К деньгам по
 * визитам (нал/безнал, без покрытия сертификатом — там касса 0, дублирования
 * нет) прибавляем продажи сертификатов месяца. Только админ.
 */
class RevenueWithCertsByMonthChart extends ChartWidget
{
    use BuildsMonthlyFinanceChart;

    protected ?string $heading = 'Выручка и прибыль по месяцам (с учётом сертификатов)';

    protected int|string|array $columnSpan = 'full';

    // Ниже графика по фактическим массажам.
    protected static ?int $sort = -2;

    protected ?string $maxHeight = '320px';

    protected function revenueValue(object $finance): float
    {
        return round($finance->revenue + $finance->revenue_certs, 2);
    }

    protected function profitValue(object $finance): float
    {
        return round($finance->profit + $finance->revenue_certs, 2);
    }
}
