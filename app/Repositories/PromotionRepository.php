<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\PromotionRepositoryInterface;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/**
 * @extends BaseQueryRepository<Promotion>
 */
class PromotionRepository extends BaseQueryRepository implements PromotionRepositoryInterface
{
    /**
     * @return Builder<Promotion>
     */
    #[Override]
    public function query(): Builder
    {
        return Promotion::query();
    }

    /**
     * @return Collection<int, Promotion>
     */
    public function activeOrdered(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
