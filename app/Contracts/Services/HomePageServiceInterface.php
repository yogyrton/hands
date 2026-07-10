<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\Page\HomePageData;

interface HomePageServiceInterface
{
    /**
     * Собрать данные для главной страницы.
     */
    public function pageData(): HomePageData;
}
