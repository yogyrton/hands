<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryServiceInterface<Promotion>
 */
interface PromotionServiceInterface extends BaseQueryServiceInterface
{
    /**
     * @return Collection<int, Promotion>
     */
    public function activeOrdered(): Collection;
}
