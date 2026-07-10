<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\Page\MasterPageData;
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

    /**
     * Данные для публичной страницы мастера.
     */
    public function showPageData(Master $master): MasterPageData;
}
