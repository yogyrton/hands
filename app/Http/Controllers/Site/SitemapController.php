<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\Service;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    /**
     * Отдаём sitemap.xml динамически — всегда актуальный, без крона и файла.
     */
    public function index(Request $request): Response
    {
        $sitemap = Sitemap::create()
            ->add(
                Url::create(route('home'))
                    ->setLastModificationDate(SiteContent::current()->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(1.0)
            );

        Service::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->each(fn (Service $service) => $sitemap->add(
                Url::create(route('services.show', $service->slug))
                    ->setLastModificationDate($service->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.8)
            ));

        Master::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->each(fn (Master $master) => $sitemap->add(
                Url::create(route('masters.show', $master->slug))
                    ->setLastModificationDate($master->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7)
            ));

        return $sitemap->toResponse($request);
    }
}
