<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryServiceInterface<Faq>
 */
interface FaqServiceInterface extends BaseQueryServiceInterface
{
    /**
     * @return Collection<int, Faq>
     */
    public function activeOrdered(): Collection;
}
