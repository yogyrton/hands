<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Master;
use App\Models\Service;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Сгенерировать public/sitemap.xml (главная + услуги + мастера)';

    public function handle(): int
    {
        $count = 1;

        $sitemap = Sitemap::create()
            ->add(
                Url::create(route('home'))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(1.0)
            );

        Service::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->each(function (Service $service) use ($sitemap, &$count): void {
                $count++;
                $sitemap->add(
                    Url::create(route('services.show', $service->slug))
                        ->setLastModificationDate($service->updated_at ?? now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.8)
                );
            });

        Master::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->each(function (Master $master) use ($sitemap, &$count): void {
                $count++;
                $sitemap->add(
                    Url::create(route('masters.show', $master->slug))
                        ->setLastModificationDate($master->updated_at ?? now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                        ->setPriority(0.7)
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('sitemap.xml сгенерирован: '.$count.' URL.');

        return self::SUCCESS;
    }
}
