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
        $revenue = Visit::moneyRevenue($start, (clone $start)->endOfMonth())['total'];

        $journal = 0.0;
        $all = 0.0;
        foreach ($expenses as $expense) {
            $all += (float) $expense->amount;
            if ($expense->in_journal) {
                $journal += (float) $expense->amount;
            }
        }

        $profitJournal = round($revenue - $journal, 2);
        $profitFull = round($revenue - $all, 2);
        $taxRate = (float) Setting::get('income_tax_percent', '20');
        $tax = round(max(0, $profitJournal) * $taxRate / 100, 2);

        return [
            'revenue' => round($revenue, 2),
            'expenses_journal' => round($journal, 2),
            'expenses_all' => round($all, 2),
            'profit_journal' => $profitJournal,
            'profit_full' => $profitFull,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'profit_after_tax' => round($profitFull - $tax, 2),
        ];
    }
}
