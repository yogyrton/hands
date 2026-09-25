<?php

namespace App\Filament\Resources\Certificates\Widgets;

use App\Enums\CertificateStatus;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CertificateStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $monthAhead = now()->addMonth()->toDateString();
        $today = now()->toDateString();

        // Действуют и до конца срока больше месяца.
        $active = Certificate::usable()->whereDate('expires_at', '>', $monthAhead)->count();
        // Ещё действуют, но срок кончается в течение месяца.
        $ending = Certificate::usable()->whereDate('expires_at', '<=', $monthAhead)->count();
        // Использованы полностью (остаток 0).
        $used = Certificate::query()->where('status', CertificateStatus::Used->value)->count();
        // Истёк срок при непустом остатке — сгорели.
        $burned = Certificate::query()
            ->where('status', '!=', CertificateStatus::Used->value)
            ->whereDate('expires_at', '<', $today)
            ->count();

        return [
            Stat::make('Всего', Certificate::query()->count())
                ->url($this->url(null)),
            Stat::make('Активные', $active)
                ->description('действуют, срок не близко')
                ->color('success')
                ->url($this->url('active')),
            Stat::make('Заканчиваются', $ending)
                ->description('ещё активны, срок в течение месяца')
                ->color('warning')
                ->url($this->url('ending')),
            Stat::make('Использованные', $used)
                ->description('остаток израсходован')
                ->color('gray')
                ->url($this->url('used')),
            Stat::make('Сгорели неиспользованными', $burned)
                ->description('истёк срок, остаток пропал')
                ->color('danger')
                ->url($this->url('burned')),
        ];
    }

    /**
     * Ссылка на список сертификатов с выбранной категорией (или без фильтра).
     */
    private function url(?string $category): string
    {
        return CertificateResource::getUrl('index', $category === null ? [] : ['category' => $category]);
    }
}
