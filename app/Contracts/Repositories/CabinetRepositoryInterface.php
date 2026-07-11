<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryRepositoryInterface<Cabinet>
 */
interface CabinetRepositoryInterface extends BaseQueryRepositoryInterface
{
    /**
     * Активные кабинеты в порядке sort_order (с загруженными медиа).
     *
     * @return Collection<int, Cabinet>
     */
    public function activeOrdered(): Collection;
}
