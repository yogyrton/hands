<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/**
 * @extends BaseQueryRepository<Service>
 */
class ServiceRepository extends BaseQueryRepository implements ServiceRepositoryInterface
{
    /**
     * @return Builder<Service>
     */
    #[Override]
    public function query(): Builder
    {
        return Service::query();
    }

    /**
     * @return Collection<int, Service>
     */
    public function activeOrdered(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
