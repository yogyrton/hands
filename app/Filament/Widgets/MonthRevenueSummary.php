<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Широкий блок выручки за месяц на главной. Показывает:
 *  — полную стоимость услуг деньгами (service_price) — как в Excel;
 *  — реально полученное по кассе (paid_amount);
 *  — разницу и её расшифровку по бартерам (визиты, где по кассе меньше полной цены).
 *
 * Учитываются визиты живыми деньгами (нал/карта). Прибыль/налог считаются
 * отдельно и от кассы (см. DashboardStats) — этот блок только про выручку.
 * Финансы видит только администратор.
 */
class MonthRevenueSummary extends Widget
{
    protected string $view = 'filament.widgets.month-revenue-summary';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function summary(): object
    {
        return static::monthSummary(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Свод по массажу (без сертификатов): полная стоимость услуг, реально по
     * кассе и разница по особым условиям. Продажи сертификатов — отдельный блок.
     *
     * @return object{services: float, cash: float, diff: float, bartes: Collection<int, Visit>}
     */
    public static function monthSummary(\DateTimeInterface $from, \DateTimeInterface $until): object
    {
        // Живые деньги за визиты: нал/карта (визиты по сертификату сюда не входят —
        // деньги за них получены при продаже сертификата).
        $money = fn () => Visit::query()
            ->whereBetween('performed_at', [$from, $until])
            ->whereIn('payment_type', ['cash', 'card']);

        $services = (float) $money()->sum('service_price');   // полная стоимость (как в Excel)
        $cash = (float) $money()->sum('paid_amount');         // реально по кассе за визиты

        // Особые условия: по кассе получено меньше полной стоимости услуги.
        /** @var Collection<int, Visit> $bartes */
        $bartes = $money()
            ->whereColumn('paid_amount', '<', 'service_price')
            ->with(['service', 'master'])
            ->orderBy('performed_at')
            ->get();

        return (object) [
            'services' => round($services, 2),
            'cash' => round($cash, 2),
            'diff' => round($services - $cash, 2),
            'bartes' => $bartes,
        ];
    }

    public function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    public function localTime(Visit $visit): string
    {
        return Carbon::parse($visit->performed_at)
            ->timezone(config('app.display_timezone', 'Europe/Minsk'))
            ->format('d.m H:i');
    }
}
