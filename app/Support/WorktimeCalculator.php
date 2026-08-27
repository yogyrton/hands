<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\MasterTier;
use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

/**
 * Расчёт рабочего времени по посещениям: сумма длительностей массажей + надбавка
 * на подготовку кабинета к каждому массажу. Длительность берётся из посещения,
 * а если её нет — определяется из прайса по услуге и базовой цене.
 */
class WorktimeCalculator
{
    /** Надбавка на подготовку кабинета к каждому массажу, минут. */
    public const PREP_MINUTES = 15;

    /**
     * Итоги по переданному запросу посещений.
     *
     * @param  Builder<Visit>  $query
     * @return object{visits: int, massage_minutes: int, prep_minutes: int, total_minutes: int}
     */
    public static function forQuery(Builder $query): object
    {
        $rows = (clone $query)
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, service_id, duration_minutes, base_price, COUNT(*) as cnt')
            ->groupBy('master_id', 'service_id', 'duration_minutes', 'base_price')
            ->get();

        if ($rows->isEmpty()) {
            return (object) ['visits' => 0, 'massage_minutes' => 0, 'prep_minutes' => 0, 'total_minutes' => 0];
        }

        $masters = Master::query()->whereIn('id', $rows->pluck('master_id'))->get()->keyBy('id');
        $services = Service::query()->with('prices')->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');

        $visits = 0;
        $massage = 0;
        foreach ($rows as $row) {
            $count = (int) $row->cnt;
            $visits += $count;

            $duration = $row->duration_minutes !== null
                ? (int) $row->duration_minutes
                : self::inferDuration(
                    $services->get($row->service_id),
                    (float) $row->base_price,
                    $masters->get($row->master_id)?->tier,
                );

            $massage += (int) ($duration ?? 0) * $count;
        }

        $prep = $visits * self::PREP_MINUTES;

        return (object) [
            'visits' => $visits,
            'massage_minutes' => $massage,
            'prep_minutes' => $prep,
            'total_minutes' => $massage + $prep,
        ];
    }

    /**
     * Определить длительность из прайса по цене: строка услуги, где цена по
     * должности мастера совпадает с базовой ценой. Если по должности нет — любая
     * цена (мастер/про). null — не нашли или неоднозначно.
     */
    public static function inferDuration(?Service $service, float $basePrice, ?MasterTier $tier): ?int
    {
        if (! $service) {
            return null;
        }

        if ($tier !== null) {
            $byTier = $service->prices->filter(fn ($p): bool => abs($p->priceForTier($tier) - $basePrice) < 0.001);
            if ($byTier->count() === 1) {
                return (int) $byTier->first()->duration_minutes;
            }
        }

        $any = $service->prices->filter(
            fn ($p): bool => abs((float) $p->price_master - $basePrice) < 0.001
                || abs((float) $p->price_pro - $basePrice) < 0.001,
        );

        return $any->count() === 1 ? (int) $any->first()->duration_minutes : null;
    }

    /**
     * Минуты в «5 ч 45 мин».
     */
    public static function hm(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return $h.' ч '.$m.' мин';
        }

        if ($h > 0) {
            return $h.' ч';
        }

        return $m.' мин';
    }
}
