<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // Эталонные SEO главной (перезаписываем — сидер это «основа»).
        // Фото главной (media) сидер не трогает — они грузятся в админке.
        SiteContent::current()->fill([
            'seo_title' => 'Массажная студия HANDS в Могилёве — запись онлайн',
            'seo_description' => 'Массажная студия HANDS в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. От 50 р. Запись онлайн.',
        ])->save();
    }
}
