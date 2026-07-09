<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\FaqRepositoryInterface;
use App\Contracts\Services\FaqServiceInterface;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryService<Faq>
 */
class FaqService extends BaseQueryService implements FaqServiceInterface
{
    public function __construct(
        protected FaqRepositoryInterface $faqs,
    ) {
        parent::__construct($faqs);
    }

    /**
     * @return Collection<int, Faq>
     */
    public function activeOrdered(): Collection
    {
        return $this->faqs->activeOrdered();
    }
}
