<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseQueryServiceInterface<Cabinet>
 */
interface CabinetServiceInterface extends BaseQueryServiceInterface
{
    /**
     * @return Collection<int, Cabinet>
     */
    public function activeOrdered(): Collection;
}
