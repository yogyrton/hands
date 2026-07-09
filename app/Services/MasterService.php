<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Contracts\Services\MasterServiceInterface;
use App\Models\Master;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryService<Master>
 */
class MasterService extends BaseQueryService implements MasterServiceInterface
{
    public function __construct(
        protected MasterRepositoryInterface $masters,
    ) {
        parent::__construct($masters);
    }

    /**
     * @return Collection<int, Master>
     */
    public function activeOrdered(): Collection
    {
        return $this->masters->activeOrdered();
    }
}
