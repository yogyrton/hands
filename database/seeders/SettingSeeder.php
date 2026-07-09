<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'phone' => '',
            'address' => 'проезд Пожарского 3Б, Могилёв',
            'instagram_url' => 'https://www.instagram.com/hands.mg/',
            'yclients_main' => 'https://n1865142.yclients.com',
            'yandex_map_embed' => 'https://yandex.ru/map-widget/v1/?ll=30.360%2C53.914&z=15&text=%D0%9C%D0%BE%D0%B3%D0%B8%D0%BB%D1%91%D0%B2%2C%20%D0%BF%D1%80%D0%BE%D0%B5%D0%B7%D0%B4%20%D0%9F%D0%BE%D0%B6%D0%B0%D1%80%D1%81%D0%BA%D0%BE%D0%B3%D0%BE%2C%203%D0%91',
            'gift_min_delivery' => '400 р',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
