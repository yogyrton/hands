<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\Master;
use App\Models\Visit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сводка над списком посещений за выбранный в фильтре период (по умолчанию — сегодня):
 *  — общая заработанная сумма (наличные + безнал + сертификаты) крупно сверху;
 *  — количество посещений помельче;
 *  — разбивка живых денег: наличными / безналом / сертификатами;
 *  — доля мастера «грязными» (стоимость услуг × ставка, по умолчанию 35%).
 *
 * Считается ровно по тому же отфильтрованному запросу, что и таблица, поэтому
 * реагирует на смену периода/мастера/поиска.
 */
class MasterEarningsSummary extends Widget
{
    use InteractsWithPageTable;

    protected string $view = 'filament.resources.visits.widgets.master-earnings-summary';

    protected int|string|array $columnSpan = 'full';

    // Не ленивый — сразу считаем от текущего фильтра и реагируем на его смену.
    protected static bool $isLazy = false;

    protected function getTablePage(): string
    {
        return ListVisits::class;
    }

    /**
     * Данные для представления: считаем по отфильтрованному запросу таблицы.
     */
    public function summary(): object
    {
        return static::summarize($this->getPageTableQuery());
    }

    /**
     * Свод по переданному запросу посещений.
     *
     * @param  Builder<Visit>  $query
     * @return object{total: float, count: int, cash: float, card: float, cert: float, cut: float, masters: Collection<int, object>}
     */
    public static function summarize(Builder $query): object
    {
        $count = (clone $query)->reorder()->count();

        // Доплаты по сертификатам (обычный с доплатой и «старый») — живые деньги,
        // разложенные по способу доплаты.
        $surchargeTypes = ['certificate_surcharge', 'certificate_external'];

        $cash = (float) (clone $query)->where('payment_type', 'cash')->sum('paid_amount')
            + (float) (clone $query)->whereIn('payment_type', $surchargeTypes)
                ->where('surcharge_payment_type', 'cash')->sum('paid_amount');

        $card = (float) (clone $query)->where('payment_type', 'card')->sum('paid_amount')
            + (float) (clone $query)->whereIn('payment_type', $surchargeTypes)
                ->where('surcharge_payment_type', 'card')->sum('paid_amount');

        // Сертификатами: полностью покрытая сертификатом стоимость + часть стоимости,
        // закрытая сертификатом при доплате (итоговая − доплата).
        $cert = (float) (clone $query)->where('payment_type', 'certificate')->sum('service_price')
            + (float) (clone $query)->whereIn('payment_type', $surchargeTypes)
                ->sum(DB::raw('service_price - paid_amount'));

        $masters = static::masterCuts($query);

        return (object) [
            'total' => round($cash + $card + $cert, 2),
            'count' => (int) $count,
            'cash' => round($cash, 2),
            'card' => round($card, 2),
            'cert' => round($cert, 2),
            'cut' => round((float) $masters->sum('earned'), 2),
            'masters' => $masters,
        ];
    }

    /**
     * Доля каждого мастера «грязными»: сумма услуг × его ставка. Только мастера
     * с посещениями в периоде, по порядку сортировки.
     *
     * @param  Builder<Visit>  $query
     * @return Collection<int, object>
     */
    public static function masterCuts(Builder $query): Collection
    {
        $totals = (clone $query)
            ->reorder()
            ->toBase()
            ->selectRaw('master_id, COALESCE(SUM(service_price), 0) as services')
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
                    'name' => $master?->name ?? 'Мастер',
                    'rate' => $rate,
                    'services' => $services,
                    'earned' => round($services * $rate / 100, 2),
                    'sort' => $master?->sort_order ?? 999,
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    public function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    /**
     * Русское склонение слова «визит».
     */
    public function visitsWord(int $count): string
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
