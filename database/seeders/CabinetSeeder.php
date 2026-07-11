<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cabinet;
use Illuminate\Database\Seeder;

class CabinetSeeder extends Seeder
{
    public function run(): void
    {
        // Фото загружаются в админке, здесь — только название/описание.
        $items = [
            ['slug' => 'marine', 'name' => 'Морской', 'description' => 'Глубокие синие тона, лёгкость волны и ощущение простора — для тех, кто хочет отпустить суету города.'],
            ['slug' => 'japan', 'name' => 'Японский', 'description' => 'Минимализм, тёплое дерево и тишина — пространство, выстроенное вокруг спокойствия и порядка.'],
            ['slug' => 'forest', 'name' => 'Лесной', 'description' => 'Природные фактуры, приглушённый зелёный свет и мягкие текстуры — как прогулка в тихом лесу.'],
        ];

        // Ключ — slug: повторный запуск обновляет тексты, не задваивая записи.
        foreach ($items as $i => $item) {
            $cabinet = Cabinet::query()->where('slug', $item['slug'])->first() ?? new Cabinet;

            $cabinet->fill([
                'slug' => $item['slug'],
                'name' => $item['name'],
                'description' => $item['description'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ])->save();
        }
    }
}
