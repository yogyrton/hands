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
            ['title' => 'Ранняя пташка', 'description' => 'Сеансы с 9:00 до 12:00 — скидка на любой массаж.', 'discount_percent' => 10],
            ['title' => 'День рождения', 'description' => 'В течение недели до и после вашего дня рождения.', 'discount_percent' => 20],
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
