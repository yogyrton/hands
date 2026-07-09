<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\VisitRepositoryInterface;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * @extends BaseQueryRepository<Visit>
 */
class VisitRepository extends BaseQueryRepository implements VisitRepositoryInterface
{
    /**
     * @return Builder<Visit>
     */
    #[Override]
    public function query(): Builder
    {
        return Visit::query();
    }
}
