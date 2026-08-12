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
            'masters_total' => static::mastersSalary($from, $until),
            'certs' => static::soldCertificates($from, $until),
        ];
    }

    /**
     * Доля мастеров за месяц «грязными»: сумма услуг × ставка каждого.
     */
    public static function mastersSalary(\DateTimeInterface $from, \DateTimeInterface $until): float
    {
        $rows = Visit::query()
            ->whereBetween('performed_at', [$from, $until])
            ->toBase()
            ->selectRaw('master_id, COALESCE(SUM(service_price), 0) as services')
            ->groupBy('master_id')
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $masters = Master::query()->whereIn('id', $rows->pluck('master_id'))->get()->keyBy('id');

        $total = 0.0;
        foreach ($rows as $row) {
            $rate = (float) ($masters->get($row->master_id)?->salary_rate ?? 0);
            $total += (float) $row->services * $rate / 100;
        }

        return round($total, 2);
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
