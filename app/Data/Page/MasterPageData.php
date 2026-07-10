<?php

declare(strict_types=1);

namespace App\Data\Page;

use App\Models\Master;
use Spatie\LaravelData\Data;

/**
 * Данные для страницы мастера. Разворачивается во вью через ->all().
 */
class MasterPageData extends Data
{
    public function __construct(
        public Master $master,
    ) {}
}
