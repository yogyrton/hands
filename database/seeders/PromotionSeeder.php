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
            ['title' => 'Ранняя пташка', 'description' => 'Запишитесь на сеанс массажа с 9:00 до 12:00 и получите скидку 10% на любой сеанс. Идеальный старт дня, чтобы подарить телу заряд бодрости.', 'discount_percent' => 10],
            ['title' => 'Именинный массаж', 'description' => 'В день рождения и в течение 3 дней до и после покажите паспорт и получите скидку 20% на любой массаж. Это наш маленький способ сказать: мы рады, что вы выбрали нас в этот особенный день.', 'discount_percent' => 20],
        ];

        // Идемпотентно: по названию — есть обновляем, нет создаём.
        foreach ($items as $i => $item) {
            Promotion::updateOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'discount_percent' => $item['discount_percent'],
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
