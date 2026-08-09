<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\Master;
use App\Models\Visit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Сводка над списком посещений за выбранный в фильтре период (по умолчанию — сегодня):
 * по карточке на каждого мастера с посещениями. В карточке:
 *  — общая заработанная сумма (наличные + безнал + сертификаты) сверху;
 *  — количество визитов;
 *  — снизу разбивка: наличными / безналом / сертификатами.
 *
 * Считается ровно по тому же отфильтрованному запросу, что и таблица, поэтому
 * реагирует на смену периода/мастера/поиска.
 */
class MasterEarningsSummary extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    // Не ленивый — сразу считаем от текущего фильтра и реагируем на его смену.
    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListVisits::class;
    }

    protected function getStats(): array
    {
        $rows = static::summarize($this->getPageTableQuery());

        if ($rows->isEmpty()) {
            return [
                Stat::make('Заработано за период', '0.00 р')
                    ->description('0 визитов')
                    ->color('gray'),
            ];
        }

        return $rows
            ->map(fn (object $row): Stat => Stat::make(
                $row->name,
                // 1-й ряд: общая сумма, справа — количество визитов.
                static::money($row->total).' р · '.$row->count.' '.static::visitsWord($row->count),
            )
                // 2-й ряд: разбивка живых денег.
                ->description('Нал '.static::money($row->cash).' · Безнал '.static::money($row->card).' · Серт '.static::money($row->cert))
                ->color('success'))
            ->all();
    }

    /**
     * Свод по каждому мастеру: общая сумма и разбивка по способу оплаты
     * (наличные / безнал / сертификаты). Только мастера с посещениями,
     * по порядку сортировки.
     *
     * Считаем от ИТОГОВОЙ стоимости услуги (service_price), а не от суммы по
     * кассе (paid_amount): при «особых условиях» (бартер, бесплатно клиенту,
     * владелец платит только долю мастера) по кассе идёт другая сумма, но
     * заработок мастера считается от полной итоговой. Доплата по сертификату —
     * живые деньги (нал/безнал), остаток итоговой закрывается сертификатом.
     *
     * @param  Builder<Visit>  $query
     * @return Collection<int, object>
     */
    public static function summarize(Builder $query): Collection
    {
        $rows = (clone $query)
            ->reorder()
            ->toBase()
            ->selectRaw('master_id')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'cash' THEN service_price ELSE 0 END), 0) as cash_direct")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'card' THEN service_price ELSE 0 END), 0) as card_direct")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type IN ('certificate_surcharge', 'certificate_external') AND surcharge_payment_type = 'cash' THEN paid_amount ELSE 0 END), 0) as cash_sur")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type IN ('certificate_surcharge', 'certificate_external') AND surcharge_payment_type = 'card' THEN paid_amount ELSE 0 END), 0) as card_sur")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'certificate' THEN service_price ELSE 0 END), 0) as cert_full")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type IN ('certificate_surcharge', 'certificate_external') THEN service_price - paid_amount ELSE 0 END), 0) as cert_sur")
            ->groupBy('master_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $masters = Master::query()
            ->whereIn('id', $rows->pluck('master_id'))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (object $row) use ($masters): object {
                /** @var Master|null $master */
                $master = $masters->get($row->master_id);

                $cash = round((float) $row->cash_direct + (float) $row->cash_sur, 2);
                $card = round((float) $row->card_direct + (float) $row->card_sur, 2);
                $cert = round((float) $row->cert_full + (float) $row->cert_sur, 2);

                return (object) [
                    'name' => $master?->name ?? 'Мастер',
                    'count' => (int) $row->cnt,
                    'cash' => $cash,
                    'card' => $card,
                    'cert' => $cert,
                    'total' => round($cash + $card + $cert, 2),
                    'sort' => $master?->sort_order ?? 999,
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    public static function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    /**
     * Русское склонение слова «визит».
     */
    public static function visitsWord(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'визит';
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 'визита';
        }

        return 'визитов';
    }
}
