<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Master;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryServiceInterface<Master>
 */
interface MasterServiceInterface extends BaseQueryServiceInterface
{
    /**
     * @return Collection<int, Master>
     */
    public function activeOrdered(): Collection;
}
