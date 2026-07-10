<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\FaqServiceInterface;
use App\Contracts\Services\HomePageServiceInterface;
use App\Contracts\Services\MasterServiceInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Data\Page\HomePageData;
use App\Models\SiteContent;

class HomePageService implements HomePageServiceInterface
{
    public function __construct(
        protected ServiceServiceInterface $services,
        protected MasterServiceInterface $masters,
        protected FaqServiceInterface $faqs,
    ) {}

    public function pageData(): HomePageData
    {
        return new HomePageData(
            services: $this->services->activeOrdered(),
            masters: $this->masters->activeOrdered(),
            faqs: $this->faqs->activeOrdered(),
            site: SiteContent::current(),
        );
    }
}
