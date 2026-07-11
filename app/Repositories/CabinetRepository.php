<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CabinetRepositoryInterface;
use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/**
 * @extends BaseQueryRepository<Cabinet>
 */
class CabinetRepository extends BaseQueryRepository implements CabinetRepositoryInterface
{
    /**
     * @return Builder<Cabinet>
     */
    #[Override]
    public function query(): Builder
    {
        return Cabinet::query();
    }

    /**
     * @return Collection<int, Cabinet>
     */
    public function activeOrdered(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with('media')
            ->get();
    }
}
