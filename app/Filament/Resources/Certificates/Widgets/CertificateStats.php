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
        $used = Certificate::query()->where('status', CertificateStatus::Used->value)->count();

        // Истёк срок, но остаток не использован — деньги/посещения сгорели.
        $burned = Certificate::query()
            ->where('status', '!=', CertificateStatus::Used->value)
            ->whereDate('expires_at', '<', now()->toDateString())
            ->count();

        return [
            Stat::make('Всего', Certificate::query()->count())
                ->url($this->url(null)),
            Stat::make('Активные (не использованы)', Certificate::usable()->count())
                ->color('success')
                ->url($this->url('active')),
            Stat::make('Реализованные', $used)
                ->color('gray')
                ->url($this->url('realized')),
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
        $parameters = $category === null
            ? []
            : ['tableFilters' => ['category' => ['value' => $category]]];

        return CertificateResource::getUrl('index', $parameters);
    }
}
