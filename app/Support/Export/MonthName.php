<?php

declare(strict_types=1);

namespace App\Support\Export;

use Illuminate\Support\Carbon;

/**
 * Русские названия месяцев для подписей в документах — не полагаемся на локаль
 * Carbon, потому что в табеле нужен именительный падеж («за сентябрь 2026 г.»),
 * а translatedFormat('F') в русской локали отдаёт родительный («сентября»).
 */
class MonthName
{
    /** @var array<int, string> */
    private const NOMINATIVE = [
        1 => 'январь',
        2 => 'февраль',
        3 => 'март',
        4 => 'апрель',
        5 => 'май',
        6 => 'июнь',
        7 => 'июль',
        8 => 'август',
        9 => 'сентябрь',
        10 => 'октябрь',
        11 => 'ноябрь',
        12 => 'декабрь',
    ];

    public static function nominative(Carbon $date): string
    {
        return self::NOMINATIVE[$date->month] ?? (string) $date->month;
    }
}
