<?php

declare(strict_types=1);

namespace App\Data\Page;

use App\Models\Service;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Данные для страницы услуги. Разворачивается во вью через ->all().
 */
class ServicePageData extends Data
{
    /**
     * @param  Collection<int, Service>  $others
     */
    public function __construct(
        public Service $service,
        public Collection $others,
    ) {}
}
