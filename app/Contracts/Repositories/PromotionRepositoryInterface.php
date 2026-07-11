<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryRepositoryInterface<Promotion>
 */
interface PromotionRepositoryInterface extends BaseQueryRepositoryInterface
{
    /**
     * Активные акции в порядке sort_order.
     *
     * @return Collection<int, Promotion>
     */
    public function activeOrdered(): Collection;
}
