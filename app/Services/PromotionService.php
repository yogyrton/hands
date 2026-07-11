<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\PromotionRepositoryInterface;
use App\Contracts\Services\PromotionServiceInterface;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryService<Promotion>
 */
class PromotionService extends BaseQueryService implements PromotionServiceInterface
{
    public function __construct(
        protected PromotionRepositoryInterface $promotionRepository,
    ) {
        parent::__construct($promotionRepository);
    }

    /**
     * @return Collection<int, Promotion>
     */
    public function activeOrdered(): Collection
    {
        return $this->promotionRepository->activeOrdered();
    }
}
