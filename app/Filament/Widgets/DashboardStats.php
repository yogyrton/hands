<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\ExpensePeriod;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Сводка по текущему месяцу на главной админки: выручка, две прибыли и налог,
 * визиты, средний чек + напоминание об истекающих сертификатах.
 * Финансовые цифры видит только администратор.
 */
class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        $from = Carbon::now()->startOfMonth();
        $until = Carbon::now()->endOfMonth();

        $visitsCount = $this->visits($from, $until)->count();
        $revenue = Visit::moneyRevenue($from, $until);
        $average = $visitsCount > 0 ? round($revenue['total'] / $visitsCount, 2) : 0.0;

        $period = ExpensePeriod::where('year', $from->year)->where('month', $from->month)->first();
        $pnl = ExpensePeriod::pnlFor($from->year, $from->month, $period?->expenses ?? new Collection);
        $taxLabel = rtrim(rtrim(number_format($pnl['tax_rate'], 2, '.', ''), '0'), '.');

        return [
            Stat::make('Выручка за месяц', $this->money($revenue['total']))
                ->description('нал '.$this->money($revenue['cash']).' · карта '.$this->money($revenue['card']))
                ->color('success'),
            Stat::make('Прибыль по журналу', $this->money($pnl['profit_journal']))
                ->description('налог '.$taxLabel.'%: '.$this->money($pnl['tax']))
                ->color($pnl['profit_journal'] >= 0 ? 'success' : 'danger'),
            Stat::make('Прибыль полная', $this->money($pnl['profit_full']))
                ->description('после налога: '.$this->money($pnl['profit_after_tax']))
                ->color($pnl['profit_full'] >= 0 ? 'success' : 'danger'),
            Stat::make('Визитов за месяц', (string) $visitsCount)
                ->description('средний чек '.$this->money($average)),
            Stat::make('Истекающие сертификаты', (string) $this->expiringCertificates())
                ->description('заканчиваются в течение месяца')
                ->color('danger'),
        ];
    }

    /**
     * @return Builder<Visit>
     */
    private function visits(Carbon $from, Carbon $until): Builder
    {
        return Visit::query()->whereBetween('performed_at', [$from, $until]);
    }

    private function expiringCertificates(): int
    {
        return Certificate::query()
            ->where('status', '!=', CertificateStatus::Used->value)
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addMonth()->toDateString())
            ->count();
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', ' ').' р';
    }
}
