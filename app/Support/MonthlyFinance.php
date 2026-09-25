<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Certificate;
use App\Models\Master;
use App\Models\Setting;
use App\Models\Visit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Автоматический финансовый расчёт за календарный месяц — единый источник для
 * дашборда. Ничего не хранится и не создаётся вручную: всё считается на лету
 * из посещений, продаж сертификатов и постоянных расходов (из настроек).
 *
 * Модель прибыли (упрощённая, «примерно»):
 *   Прибыль = Выручка − Зарплата мастеров − Постоянные расходы
 *   Выручка = визиты по кассе + продажи сертификатов
 *   Зарплата мастера ≈ половина наработанного им (service_price ÷ 2) — сюда
 *   «зашиты» его процент, ФСЗН и налоги, детально не разбиваем.
 *   Постоянные расходы = аренда + квартплата + бухгалтер + прочие + мой ФСЗН.
 */
class MonthlyFinance
{
    private const MONTHS = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
    ];

    public static function for(int $year, int $month): object
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        // Выручка деньгами: визиты по кассе + проданные сертификаты.
        $revenueVisits = round(Visit::moneyRevenue($start, $end)['total'], 2);
        $revenueCerts = round(Certificate::soldTotal($start, $end), 2);
        $revenue = round($revenueVisits + $revenueCerts, 2);

        $masters = self::masters($start, $end);
        $salaryTotal = round((float) $masters->sum('cost'), 2);

        $fixedLines = self::fixedExpenses();
        $fixedTotal = round((float) collect($fixedLines)->sum('amount'), 2);

        $costsTotal = round($salaryTotal + $fixedTotal, 2);
        $profit = round($revenue - $costsTotal, 2);

        return (object) [
            'year' => $year,
            'month' => $month,
            'label' => (self::MONTHS[$month] ?? (string) $month).' '.$year,
            'revenue' => $revenue,
            'revenue_visits' => $revenueVisits,
            'revenue_certs' => $revenueCerts,
            'masters' => $masters,
            'salary_total' => $salaryTotal,
            'fixed_lines' => $fixedLines,
            'fixed_total' => $fixedTotal,
            'costs_total' => $costsTotal,
            'profit' => $profit,
            'certs' => self::soldCertificates($start, $end),
        ];
    }

    /**
     * Наработка каждого мастера за месяц (полная стоимость услуг) и затраты на
     * него ≈ половина. Только мастера с визитами, по сортировке.
     *
     * @return Collection<int, object>
     */
    private static function masters(Carbon $start, Carbon $end): Collection
    {
        $rows = Visit::query()
            ->whereBetween('performed_at', [$start, $end])
            ->toBase()
            ->selectRaw('master_id, COALESCE(SUM(service_price), 0) as earned, COUNT(*) as cnt')
            ->groupBy('master_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $masters = Master::query()->whereIn('id', $rows->pluck('master_id'))->get()->keyBy('id');

        return $rows
            ->map(function (object $row) use ($masters): object {
                $master = $masters->get($row->master_id);
                $earned = round((float) $row->earned, 2);

                return (object) [
                    'name' => $master?->name ?? 'Мастер',
                    'earned' => $earned,
                    'cost' => round($earned / 2, 2),
                    'count' => (int) $row->cnt,
                    'active' => (bool) ($master?->is_active ?? false),
                    'sort' => $master?->sort_order ?? 999,
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    /**
     * Постоянные расходы месяца из настроек студии.
     *
     * @return array<int, array{title: string, amount: float}>
     */
    private static function fixedExpenses(): array
    {
        return [
            ['title' => 'Аренда', 'amount' => (float) Setting::get('expense_rent', '1880')],
            ['title' => 'Квартплата', 'amount' => (float) Setting::get('expense_utilities', '200')],
            ['title' => 'Услуги бухгалтера', 'amount' => (float) Setting::get('expense_accountant', '250')],
            ['title' => 'Прочие траты', 'amount' => (float) Setting::get('other_expenses', '600')],
            ['title' => 'Мой ФСЗН', 'amount' => (float) Setting::get('owner_fszn', '300')],
        ];
    }

    /**
     * Проданные за месяц сертификаты (по дате продажи).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Certificate>
     */
    private static function soldCertificates(Carbon $start, Carbon $end): \Illuminate\Database\Eloquent\Collection
    {
        return Certificate::query()
            ->whereDate('sold_at', '>=', $start->toDateString())
            ->whereDate('sold_at', '<=', $end->toDateString())
            ->orderBy('sold_at')
            ->get();
    }
}
