<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Master;
use App\Models\Visit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Сводка по текущему месяцу на главной админки: выручка, начисление мастерам,
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
        $revenue = $this->revenue($from, $until);
        $accrued = $this->accruedToMasters($from->year, $from->month);
        $average = $visitsCount > 0 ? round($revenue['total'] / $visitsCount, 2) : 0.0;

        return [
            Stat::make('Выручка за месяц', $this->money($revenue['total']))
                ->description('нал '.$this->money($revenue['cash']).' · карта '.$this->money($revenue['card']))
                ->color('success'),
            Stat::make('Начислено мастерам', $this->money($accrued))
                ->description('доля мастеров за визиты месяца')
                ->color('warning'),
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

    /**
     * Выручка деньгами за период (та же логика, что в Отчётах).
     *
     * @return array<string, float>
     */
    private function revenue(Carbon $from, Carbon $until): array
    {
        $cash = (float) $this->visits($from, $until)->where('payment_type', 'cash')->sum('paid_amount');
        $card = (float) $this->visits($from, $until)->where('payment_type', 'card')->sum('paid_amount');

        $surchargeTypes = ['certificate_surcharge', 'certificate_external'];
        $cash += (float) $this->visits($from, $until)
            ->whereIn('payment_type', $surchargeTypes)
            ->where('surcharge_payment_type', 'cash')
            ->sum('paid_amount');
        $card += (float) $this->visits($from, $until)
            ->whereIn('payment_type', $surchargeTypes)
            ->where('surcharge_payment_type', 'card')
            ->sum('paid_amount');

        return ['cash' => $cash, 'card' => $card, 'total' => $cash + $card];
    }

    private function accruedToMasters(int $year, int $month): float
    {
        return round(Master::query()->get()->sum(fn (Master $master): float => $master->accruedInMonth($year, $month)), 2);
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
