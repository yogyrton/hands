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
            ['slug' => 'marine', 'name' => 'Морской', 'description' => 'Большая волна на стене, синие огни-бра и мягкий полумрак — кабинет, в котором словно слышно дыхание моря. Здесь городская суета отступает, как вода от берега, а тело и мысли отпускает само собой.'],
            ['slug' => 'georgian', 'name' => 'Грузинский', 'description' => 'Тёплая терракота, этнические узоры и лампы с ручной росписью — пространство с южным характером и радушием старого дома, где встречают как дорогого гостя. Здесь тело согревается вместе с душой, и никуда не хочется спешить.'],
            ['slug' => 'soviet', 'name' => 'Советский', 'description' => 'Глубокие тёплые тона, бархат и винтажные детали — уютная роскошь в духе ушедшей эпохи, обстоятельная и настоящая. Обстановка, в которой хочется выдохнуть, замедлиться и остаться подольше.'],
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
