<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Contracts\Services\ServiceServiceInterface;
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
}
