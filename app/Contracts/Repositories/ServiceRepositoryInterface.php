<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryRepositoryInterface<Service>
 */
interface ServiceRepositoryInterface extends BaseQueryRepositoryInterface
{
    /**
     * Активные услуги в порядке sort_order.
     *
     * @return Collection<int, Service>
     */
    public function activeOrdered(): Collection;
}
