<?php

namespace App\Filament\Resources\Certificates\Widgets;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CertificateStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $used = Certificate::query()->where('status', CertificateStatus::Used->value)->count();

        // Истёк по дате и ещё не использован до конца.
        $expired = Certificate::query()
            ->whereDate('expires_at', '<', now()->toDateString())
            ->where('status', '!=', CertificateStatus::Used->value)
            ->count();

        return [
            Stat::make('Всего', Certificate::query()->count()),
            Stat::make('Активные (не использованы)', Certificate::usable()->count())
                ->color('success'),
            Stat::make('Реализованные', $used)
                ->color('gray'),
            Stat::make('Истёкшие', $expired)
                ->color('danger'),
        ];
    }
}
