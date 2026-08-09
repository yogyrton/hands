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
 * Сводка над списком посещений: сколько заработал каждый мастер за выбранный
 * в фильтре период (по умолчанию — сегодня). Показываются только мастера,
 * у которых есть посещения в этом периоде: добавилось посещение нового мастера —
 * появилась и его строка.
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
        // Тот же отфильтрованный запрос, что и у таблицы (дата/период, мастер, поиск).
        return static::summarize($this->getPageTableQuery())
            ->map(fn (object $row): Stat => Stat::make(
                $row->name,
                static::money($row->earned).' р',
            )
                ->description($row->count.' '.static::visitsWord($row->count).' · услуг на '.static::money($row->services).' р')
                ->color('success'))
            ->all();
    }

    /**
     * Заработок мастеров по переданному запросу посещений: группировка по мастеру,
     * его доля = сумма услуг × ставка. Только мастера с посещениями, по порядку сортировки.
     *
     * @param  Builder<Visit>  $query
     * @return Collection<int, object>
     */
    public static function summarize(Builder $query): Collection
    {
        $totals = $query
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, COUNT(*) as cnt, COALESCE(SUM(service_price), 0) as services')
            ->groupBy('master_id')
            ->get();

        if ($totals->isEmpty()) {
            return collect();
        }

        $masters = Master::query()
            ->whereIn('id', $totals->pluck('master_id'))
            ->get()
            ->keyBy('id');

        return $totals
            ->map(function (object $row) use ($masters): object {
                /** @var Master|null $master */
                $master = $masters->get($row->master_id);
                $rate = (float) ($master?->salary_rate ?? 0);
                $services = (float) $row->services;

                return (object) [
                    'master_id' => $row->master_id,
                    'name' => $master?->name ?? 'Мастер',
                    'count' => (int) $row->cnt,
                    'services' => $services,
                    'earned' => round($services * $rate / 100, 2),
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
