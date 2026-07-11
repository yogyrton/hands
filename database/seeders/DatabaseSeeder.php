<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserSeeder сюда НЕ включаем — запускается точечно
        // (php artisan db:seed --class=UserSeeder), чтобы не трогать пароли.
        $this->call([
            ServiceSeeder::class,
            MasterSeeder::class,
            FaqSeeder::class,
            PromotionSeeder::class,
            CabinetSeeder::class,
            SettingSeeder::class,
            SiteContentSeeder::class,
        ]);
    }
}
