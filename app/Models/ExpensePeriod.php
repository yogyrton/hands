<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Месяц расходов. Внутри — записи расходов (аренда, зарплата+взносы и т.п.).
 * Считает P&L за месяц: выручку, расходы и две прибыли + налог.
 *
 * @property int $id
 * @property int $year
 * @property int $month
 */
class ExpensePeriod extends Model
{
    private const MONTHS = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
    ];

    protected $fillable = [
        'year',
        'month',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    /**
     * @return HasMany<Expense>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        return self::MONTHS;
    }

    public function label(): string
    {
        return (self::MONTHS[$this->month] ?? (string) $this->month).' '.$this->year;
    }

    public function startOfMonth(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfMonth();
    }

    public function endOfMonth(): Carbon
    {
        return $this->startOfMonth()->endOfMonth();
    }

    /**
     * P&L месяца: выручка, расходы (в журнале / все) и две прибыли + налог.
     * «В журнале» = официально задекларировано (чекбокс). Налог берётся с
     * официальной прибыли (по журналу).
     *
     * @return array<string, float>
     */
    public function pnl(): array
    {
        return self::pnlFor($this->year, $this->month, $this->loadMissing('expenses')->expenses);
    }

    /**
     * Тот же расчёт для произвольного месяца и набора расходов (для дашборда).
     *
     * @param  iterable<Expense>  $expenses
     * @return array<string, float>
     */
    public static function pnlFor(int $year, int $month, iterable $expenses): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        // Выручка по факту денег: оплаченные визиты (нал/карта/доплаты) +
        // продажи сертификатов за месяц. Визит по сертификату денег не приносит
        // (оплачен при продаже) — он идёт только в зарплату мастера.
        $revenueVisits = Visit::moneyRevenue($start, $end)['total'];
        $revenueCerts = Certificate::soldTotal($start, $end);
        $revenue = $revenueVisits + $revenueCerts;

        // В прибыли участвуют только расходы «в журнале». Прочие (расходники и
        // т.п.) показываем для справки, в расчёт не берём.
        $journal = 0.0;
        $nonJournal = 0.0;
        foreach ($expenses as $expense) {
            if ($expense->in_journal) {
                $journal += (float) $expense->amount;
            } else {
                $nonJournal += (float) $expense->amount;
            }
        }

        $profit = round($revenue - $journal, 2);
        $taxRate = (float) Setting::get('income_tax_percent', '20');
        $tax = round(max(0, $profit) * $taxRate / 100, 2);

        return [
            'revenue' => round($revenue, 2),
            'revenue_visits' => round($revenueVisits, 2),
            'revenue_certs' => round($revenueCerts, 2),
            'expenses_journal' => round($journal, 2),
            'expenses_non_journal' => round($nonJournal, 2),
            'profit' => $profit,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'profit_after_tax' => round($profit - $tax, 2),
        ];
    }
}
