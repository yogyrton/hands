<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\CabinetRepositoryInterface;
use App\Contracts\Services\CabinetServiceInterface;
use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryService<Cabinet>
 */
class CabinetService extends BaseQueryService implements CabinetServiceInterface
{
    public function __construct(
        protected CabinetRepositoryInterface $cabinetRepository,
    ) {
        parent::__construct($cabinetRepository);
    }

    /**
     * @return Collection<int, Cabinet>
     */
    public function activeOrdered(): Collection
    {
        return $this->cabinetRepository->activeOrdered();
    }
}
