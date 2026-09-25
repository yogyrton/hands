<?php

namespace App\Filament\Resources\Certificates\Widgets;

use App\Enums\CertificateStatus;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CertificateStats extends StatsOverviewWidget
{
    // Все плашки в один ряд (на узких экранах — авто-перенос).
    protected function getColumns(): int
    {
        return 5;
    }

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
                ->url($this->url([])),
            Stat::make('Активные', $active)
                ->description('действуют, срок не близко')
                ->color('success')
                ->url($this->url(['status' => 'unused', 'condition' => 'active'])),
            Stat::make('Заканчиваются', $ending)
                ->description('ещё активны, срок в течение месяца')
                ->color('warning')
                ->url($this->url(['status' => 'unused', 'condition' => 'ending'])),
            Stat::make('Использованные', $used)
                ->description('остаток израсходован')
                ->color('gray')
                ->url($this->url(['status' => 'used'])),
            Stat::make('Сгорели неиспользованными', $burned)
                ->description('истёк срок, остаток пропал')
                ->color('danger')
                ->url($this->url(['status' => 'unused', 'condition' => 'expired'])),
        ];
    }

    /**
     * Ссылка на список с теми же фильтрами, что и в панели фильтров таблицы
     * (URL-ключ Filament — filters[<имя>][value]=…).
     *
     * @param  array<string, string>  $filters
     */
    private function url(array $filters): string
    {
        if ($filters === []) {
            return CertificateResource::getUrl('index');
        }

        $query = [];
        foreach ($filters as $name => $value) {
            $query[$name] = ['value' => $value];
        }

        return CertificateResource::getUrl('index', ['filters' => $query]);
    }
}
