<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Расчётный период зарплаты — календарный месяц. Внутри — записи по мастерам
 * (аванс + окончательный расчёт). «Заработано» тянется из посещений.
 *
 * @property int $id
 * @property int $year
 * @property int $month
 */
class PayrollPeriod extends Model
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
     * @return HasMany<MasterPayout>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(MasterPayout::class);
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

    /**
     * Итоги по всем мастерам периода: начислено / выплачено / долг.
     *
     * @return array<string, float>
     */
    public function totals(): array
    {
        $this->loadMissing('payouts.master');

        $accrued = 0.0;
        $paid = 0.0;

        foreach ($this->payouts as $payout) {
            $accrued += $payout->accrued();
            $paid += $payout->paid();
        }

        return [
            'accrued' => round($accrued, 2),
            'paid' => round($paid, 2),
            'debt' => round($accrued - $paid, 2),
        ];
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
     * Дата зарплаты по умолчанию: 10-е число следующего месяца.
     */
    public function defaultSalaryDate(): Carbon
    {
        return $this->startOfMonth()->addMonthNoOverflow()->day(10);
    }
}
