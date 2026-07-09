<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\FaqRepositoryInterface;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Override;

/**
 * @extends BaseQueryRepository<Faq>
 */
class FaqRepository extends BaseQueryRepository implements FaqRepositoryInterface
{
    /**
     * @return Builder<Faq>
     */
    #[Override]
    public function query(): Builder
    {
        return Faq::query();
    }

    /**
     * @return Collection<int, Faq>
     */
    public function activeOrdered(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
