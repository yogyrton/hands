<?php

namespace App\Filament\Resources\Visits\Widgets;

use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\Visit;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Сводка над списком посещений за выбранный в фильтре период (по умолчанию — сегодня):
 *  — общая заработанная сумма и рядом количество заказов;
 *  — отдельно разбивка: наличными / безналом / сертификатами.
 *
 * Считается ровно по тому же отфильтрованному запросу, что и таблица, поэтому
 * реагирует на смену периода/мастера/поиска.
 */
class MasterEarningsSummary extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    // Не ленивый — сразу считаем от текущего фильтра и реагируем на его смену.
    protected static bool $isLazy = false;

    // Заработано + заказы в первой строке, нал/безнал/серт — во второй.
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getTablePage(): string
    {
        return ListVisits::class;
    }

    protected function getStats(): array
    {
        $s = static::summarize($this->getPageTableQuery());

        return [
            Stat::make('Заработано за период', static::money($s->total).' р')
                ->description($s->count.' '.static::ordersWord($s->count))
                ->color('success'),
            Stat::make('Заказов', (string) $s->count)
                ->description('за выбранный период')
                ->color('gray'),
            Stat::make('Наличными', static::money($s->cash).' р')
                ->color('gray'),
            Stat::make('Безналом', static::money($s->card).' р')
                ->color('gray'),
            Stat::make('Сертификатами', static::money($s->cert).' р')
                ->color('gray'),
        ];
    }

    /**
     * Свод по переданному запросу посещений: общая сумма, количество и разбивка
     * живых денег по способу оплаты (наличные / безнал / сертификаты).
     *
     * @param  Builder<Visit>  $query
     * @return object{total: float, count: int, cash: float, card: float, cert: float}
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

        return (object) [
            'total' => round($cash + $card + $cert, 2),
            'count' => (int) $count,
            'cash' => round($cash, 2),
            'card' => round($card, 2),
            'cert' => round($cert, 2),
        ];
    }

    public static function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    /**
     * Русское склонение слова «заказ».
     */
    public static function ordersWord(int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'заказ';
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 'заказа';
        }

        return 'заказов';
    }
}
