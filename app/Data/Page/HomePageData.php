<?php

declare(strict_types=1);

namespace App\Data\Page;

use App\Models\Cabinet;
use App\Models\Faq;
use App\Models\Master;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\SiteContent;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;

/**
 * Данные для главной страницы. Разворачивается во вью через ->all()
 * (модели остаются моделями — в Blade нужны media-хелперы и связи).
 */
class HomePageData extends Data
{
    /**
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, Master>  $masters
     * @param  Collection<int, Faq>  $faqs
     * @param  Collection<int, Promotion>  $promotions
     * @param  Collection<int, Cabinet>  $cabinets
     */
    public function __construct(
        public Collection $services,
        public Collection $masters,
        public Collection $faqs,
        public Collection $promotions,
        public Collection $cabinets,
        public SiteContent $site,
    ) {}
}
