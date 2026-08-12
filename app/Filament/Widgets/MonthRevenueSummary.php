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
     * Полная стоимость визитов за месяц (база зарплаты) и её разложение на кассу,
     * бартер и сертификаты. Инвариант: cash + barter + cert = services.
     *
     * @return object{services: float, cash: float, barter: float, cert: float, bartes: Collection<int, Visit>}
     */
    public static function monthSummary(\DateTimeInterface $from, \DateTimeInterface $until): object
    {
        // Считаем всех мастеров, кто делал визиты в периоде: работа сделана —
        // зарплата положена, даже если мастер потом стал неактивным.
        $all = fn () => Visit::query()->whereBetween('performed_at', [$from, $until]);
        $certTypes = ['certificate', 'certificate_external', 'certificate_surcharge'];

        // Полная стоимость всех визитов — база зарплаты мастеров.
        $services = (float) $all()->sum('service_price');

        // Реально пришедшие за визиты деньги (нал/карта + доплаты).
        $cash = (float) $all()->sum('paid_amount');

        // Недобор по денежным визитам (бартер/особые условия).
        $barter = (float) $all()
            ->whereIn('payment_type', ['cash', 'card'])
            ->sum(DB::raw('service_price - paid_amount'));

        // Стоимость, покрытая сертификатами (визиты по сертификату).
        $cert = (float) $all()
            ->whereIn('payment_type', $certTypes)
            ->sum(DB::raw('service_price - paid_amount'));

        // Расшифровка бартеров: денежные визиты, где по кассе меньше полной стоимости.
        /** @var Collection<int, Visit> $bartes */
        $bartes = $all()
            ->whereIn('payment_type', ['cash', 'card'])
            ->whereColumn('paid_amount', '<', 'service_price')
            ->with(['service', 'master'])
            ->orderBy('performed_at')
            ->get();

        return (object) [
            'services' => round($services, 2),
            'cash' => round($cash, 2),
            'barter' => round($barter, 2),
            'cert' => round($cert, 2),
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
