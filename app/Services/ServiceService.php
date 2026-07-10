<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Data\Page\ServicePageData;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryService<Service>
 */
class ServiceService extends BaseQueryService implements ServiceServiceInterface
{
    public function __construct(
        protected ServiceRepositoryInterface $services,
    ) {
        parent::__construct($services);
    }

    /**
     * @return Collection<int, Service>
     */
    public function activeOrdered(): Collection
    {
        return $this->services->activeOrdered();
    }

    /**
     * Данные для публичной страницы услуги: сама услуга с активными мастерами
     * и остальные активные услуги для блока «Другие виды массажа».
     */
    public function showPageData(Service $service): ServicePageData
    {
        $service->load('activeMasters');

        $others = $this->activeOrdered()
            ->reject(fn (Service $item): bool => $item->id === $service->id)
            ->values();

        return new ServicePageData($service, $others);
    }
}
