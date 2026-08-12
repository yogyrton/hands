<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Certificate;
use App\Models\ExpensePeriod;
use App\Models\Master;
use App\Models\Visit;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Широкий блок «итоги месяца» на главной: прибыль, деньги в кассу и доля
 * мастеров, а также проданные сертификаты списком (номер, сумма, дата продажи).
 * Объединяет бывшие плашки «Прибыль» и «Продано сертификатов». Только админ.
 */
class MonthProfitSummary extends Widget
{
    protected string $view = 'filament.widgets.month-profit-summary';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function summary(): object
    {
        $from = Carbon::now()->startOfMonth();
        $until = Carbon::now()->endOfMonth();

        $period = ExpensePeriod::where('year', $from->year)->where('month', $from->month)->first();
        $pnl = ExpensePeriod::pnlFor($from->year, $from->month, $period?->expenses ?? new Collection);

        return (object) [
            'profit' => $pnl['profit'],
            'after_tax' => $pnl['profit_after_tax'],
            'tax' => $pnl['tax'],
            'tax_rate' => $pnl['tax_rate'],
            'revenue' => $pnl['revenue'],
            'revenue_visits' => $pnl['revenue_visits'],
            'revenue_certs' => $pnl['revenue_certs'],
            'expenses' => $pnl['expenses_journal'],
            'masters' => static::mastersBreakdown($from, $until),
            'certs' => static::soldCertificates($from, $until),
        ];
    }

    /**
     * Сколько наработал каждый мастер за месяц — полная стоимость его услуг
     * (service_price), без зарплаты. Только мастера с визитами, по сортировке.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function mastersBreakdown(\DateTimeInterface $from, \DateTimeInterface $until): \Illuminate\Support\Collection
    {
        $rows = Visit::query()
            ->whereBetween('performed_at', [$from, $until])
            ->toBase()
            ->selectRaw('master_id, COALESCE(SUM(service_price), 0) as services, COUNT(*) as cnt')
            ->groupBy('master_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $masters = Master::query()->whereIn('id', $rows->pluck('master_id'))->get()->keyBy('id');

        return $rows
            ->map(function (object $row) use ($masters): object {
                $master = $masters->get($row->master_id);

                return (object) [
                    'name' => $master?->name ?? 'Мастер',
                    'amount' => round((float) $row->services, 2),
                    'count' => (int) $row->cnt,
                    'sort' => $master?->sort_order ?? 999,
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    /**
     * Проданные за месяц сертификаты (по дате продажи).
     *
     * @return Collection<int, Certificate>
     */
    public static function soldCertificates(\DateTimeInterface $from, \DateTimeInterface $until): Collection
    {
        return Certificate::query()
            ->whereDate('sold_at', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('sold_at', '<=', Carbon::parse($until)->toDateString())
            ->orderBy('sold_at')
            ->get();
    }

    public function money(float $value): string
    {
        return number_format($value, 2, '.', ' ');
    }

    public function taxLabel(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
    }

    public function soldDate(Certificate $certificate): string
    {
        return Carbon::parse($certificate->sold_at)->format('d.m.Y');
    }
}
