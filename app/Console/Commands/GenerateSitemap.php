<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Master;
use App\Models\Service;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Сгенерировать public/sitemap.xml (главная + услуги + мастера)';

    public function handle(): int
    {
        $urls = [route('home')];

        Service::query()->where('is_active', true)->orderBy('sort_order')
            ->pluck('slug')
            ->each(function (string $slug) use (&$urls): void {
                $urls[] = route('services.show', $slug);
            });

        Master::query()->where('is_active', true)->orderBy('sort_order')
            ->pluck('slug')
            ->each(function (string $slug) use (&$urls): void {
                $urls[] = route('masters.show', $slug);
            });

        $today = now()->toDateString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'."\n"
                .'    <loc>'.htmlspecialchars($url, ENT_XML1).'</loc>'."\n"
                .'    <lastmod>'.$today.'</lastmod>'."\n"
                .'  </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('sitemap.xml сгенерирован: '.count($urls).' URL.');

        return self::SUCCESS;
    }
}
