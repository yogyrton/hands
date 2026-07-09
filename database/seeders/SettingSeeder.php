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
            'address' => 'переулок Пожарный, 3Б, Могилёв',
            'instagram_url' => 'https://www.instagram.com/hands.mg/',
            'yclients_main' => 'https://n1865142.yclients.com',
            'yandex_map_embed' => 'https://yandex.ru/map-widget/v1/?ll=30.334796%2C53.899016&z=17&text=%D0%9C%D0%BE%D0%B3%D0%B8%D0%BB%D1%91%D0%B2%2C%20%D0%BF%D0%B5%D1%80%D0%B5%D1%83%D0%BB%D0%BE%D0%BA%20%D0%9F%D0%BE%D0%B6%D0%B0%D1%80%D0%BD%D1%8B%D0%B9%2C%203%D0%91',
            'gift_min_delivery' => '400 р',

            // Реквизиты ИП (для футера и политик). Заполняются в админке.
            'legal_name' => '',          // ИП Фамилия Имя Отчество
            'legal_unp' => '',           // УНП
            'legal_email' => '',         // e-mail оператора
            'legal_reg' => '',           // кем/когда зарегистрирован (опционально)
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
