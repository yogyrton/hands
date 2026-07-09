<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Models\Master;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/**
 * @extends BaseQueryRepository<Master>
 */
class MasterRepository extends BaseQueryRepository implements MasterRepositoryInterface
{
    /**
     * @return Builder<Master>
     */
    #[Override]
    public function query(): Builder
    {
        return Master::query();
    }

    /**
     * @return Collection<int, Master>
     */
    public function activeOrdered(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
