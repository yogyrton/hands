<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'early-bird', 'title' => 'Ранняя пташка', 'description' => 'Запишитесь на сеанс массажа с 9:00 до 12:00 и получите скидку 10% на любой сеанс. Идеальный старт дня, чтобы подарить телу заряд бодрости.', 'discount_percent' => 10],
            ['slug' => 'birthday', 'title' => 'Именинный массаж', 'description' => 'В день рождения и в течение 3 дней до и после покажите паспорт и получите скидку 20% на любой массаж. Это наш маленький способ сказать: мы рады, что вы выбрали нас в этот особенный день.', 'discount_percent' => 20],
        ];

        // Ключ — slug. Старые записи (без slug, по названию) подхватываем и
        // проставляем им slug, чтобы не задублировать.
        foreach ($items as $i => $item) {
            $promotion = Promotion::query()->where('slug', $item['slug'])->first()
                ?? Promotion::query()->whereNull('slug')->where('title', $item['title'])->first()
                ?? new Promotion;

            $promotion->fill([
                'slug' => $item['slug'],
                'title' => $item['title'],
                'description' => $item['description'],
                'discount_percent' => $item['discount_percent'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ])->save();
        }
    }
}
