<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Master;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryRepositoryInterface<Master>
 */
interface MasterRepositoryInterface extends BaseQueryRepositoryInterface
{
    /**
     * Активные мастера в порядке sort_order.
     *
     * @return Collection<int, Master>
     */
    public function activeOrdered(): Collection;
}
