<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\CabinetServiceInterface;
use App\Contracts\Services\FaqServiceInterface;
use App\Contracts\Services\HomePageServiceInterface;
use App\Contracts\Services\MasterServiceInterface;
use App\Contracts\Services\PromotionServiceInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Data\Page\HomePageData;
use App\Models\SiteContent;

class HomePageService implements HomePageServiceInterface
{
    public function __construct(
        protected ServiceServiceInterface $serviceService,
        protected MasterServiceInterface $masterService,
        protected FaqServiceInterface $faqService,
        protected PromotionServiceInterface $promotionService,
        protected CabinetServiceInterface $cabinetService,
    ) {}

    public function pageData(): HomePageData
    {
        return new HomePageData(
            services: $this->serviceService->activeOrdered(),
            masters: $this->masterService->activeOrdered(),
            faqs: $this->faqService->activeOrdered(),
            promotions: $this->promotionService->activeOrdered(),
            cabinets: $this->cabinetService->activeOrdered(),
            site: SiteContent::current(),
        );
    }
}
