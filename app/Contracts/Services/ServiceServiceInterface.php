<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\Page\ServicePageData;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryServiceInterface<Service>
 */
interface ServiceServiceInterface extends BaseQueryServiceInterface
{
    /**
     * @return Collection<int, Service>
     */
    public function activeOrdered(): Collection;

    /**
     * Данные для публичной страницы услуги.
     */
    public function showPageData(Service $service): ServicePageData;
}
