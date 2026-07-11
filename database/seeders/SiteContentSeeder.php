<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = SiteContent::current();

        // Дефолтные SEO главной, если ещё не заданы в админке.
        $content->fill(array_filter([
            'seo_title' => $content->seo_title ?: 'Массажная студия HANDS в Могилёве — запись онлайн',
            'seo_description' => $content->seo_description ?: 'Массажная студия HANDS в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. От 50 р. Запись онлайн.',
        ]))->save();
    }
}
