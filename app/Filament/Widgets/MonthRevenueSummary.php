<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Широкий блок «полная стоимость визитов за месяц» — база для расчёта зарплаты
 * мастеров (service_price по ВСЕМ визитам, включая по сертификату). Раскладывается
 * ровно на три части, которые в сумме дают полную:
 *  — по кассе (реально полученные деньги за визиты);
 *  — бартер / особые условия (недобор по кассе на денежных визитах) + расшифровка;
 *  — визиты по сертификату (стоимость, покрытая сертификатом; деньги — при продаже).
 *
 * Это НЕ выручка студии (выручка = деньги = касса + продажи сертификатов, см.
 * блок «Итоги месяца»). Финансы видит только администратор.
 */
class MonthRevenueSummary extends Widget
{
    protected string $view = 'filament.widgets.month-revenue-summary';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -5;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function summary(): object
    {
        return static::monthSummary(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Полная стоимость визитов за месяц (база зарплаты) с разложением на кассу,
     * бартер и сертификаты. Считаем два подсчёта: по активным мастерам (основной)
     * и с учётом ушедших, кто отработал в этом месяце (итог). Инвариант в каждом:
     * cash + barter + cert = services.
     *
     * @return object{active: object, inactive: object, total: object, bartes: Collection<int, Visit>}
     */
    public static function monthSummary(\DateTimeInterface $from, \DateTimeInterface $until): object
    {
        $active = static::breakdown($from, $until, true);
        $inactive = static::breakdown($from, $until, false);

        // Расшифровка бартеров: денежные визиты, где по кассе меньше полной стоимости.
        /** @var Collection<int, Visit> $bartes */
        $bartes = Visit::query()
            ->whereBetween('performed_at', [$from, $until])
            ->whereIn('payment_type', ['cash', 'card'])
            ->whereColumn('paid_amount', '<', 'service_price')
            ->with(['service', 'master'])
            ->orderBy('performed_at')
            ->get();

        return (object) [
            'active' => $active,
            'inactive' => $inactive,
            'total' => (object) [
                'services' => round($active->services + $inactive->services, 2),
                'cash' => round($active->cash + $inactive->cash, 2),
                'barter' => round($active->barter + $inactive->barter, 2),
                'cert' => round($active->cert + $inactive->cert, 2),
            ],
            'bartes' => $bartes,
        ];
    }

    /**
     * Разложение полной стоимости на кассу/бартер/сертификаты для мастеров
     * с заданным статусом активности.
     *
     * @return object{services: float, cash: float, barter: float, cert: float}
     */
    private static function breakdown(\DateTimeInterface $from, \DateTimeInterface $until, bool $active): object
    {
        $all = fn () => Visit::query()
            ->whereBetween('performed_at', [$from, $until])
            ->whereHas('master', fn ($q) => $q->where('is_active', $active));
        $certTypes = ['certificate', 'certificate_external', 'certificate_surcharge'];

        return (object) [
            'services' => round((float) $all()->sum('service_price'), 2),
            'cash' => round((float) $all()->sum('paid_amount'), 2),
            'barter' => round((float) $all()
                ->whereIn('payment_type', ['cash', 'card'])
                ->sum(DB::raw('service_price - paid_amount')), 2),
            'cert' => round((float) $all()
                ->whereIn('payment_type', $certTypes)
                ->sum(DB::raw('service_price - paid_amount')), 2),
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
