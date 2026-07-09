<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryRepositoryInterface<Faq>
 */
interface FaqRepositoryInterface extends BaseQueryRepositoryInterface
{
    /**
     * Активные вопросы в порядке sort_order.
     *
     * @return Collection<int, Faq>
     */
    public function activeOrdered(): Collection;
}
