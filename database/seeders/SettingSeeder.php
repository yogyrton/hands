<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Значения по умолчанию. Проставляются при запуске сидера; после этого
        // редактируются в админке (повторно сидер не гоняем, чтобы не затирать правки).
        $settings = [
            'phone' => '+375256796159',
            'address' => 'переулок Пожарный, 3Б, Могилёв',
            'instagram_url' => 'https://www.instagram.com/hands.mg/',
            'yclients_main' => 'https://n1865142.yclients.com',
            'yandex_map_embed' => 'https://yandex.ru/map-widget/v1/?ll=30.334796%2C53.899016&z=17&text=%D0%9C%D0%BE%D0%B3%D0%B8%D0%BB%D1%91%D0%B2%2C%20%D0%BF%D0%B5%D1%80%D0%B5%D1%83%D0%BB%D0%BE%D0%BA%20%D0%9F%D0%BE%D0%B6%D0%B0%D1%80%D0%BD%D1%8B%D0%B9%2C%203%D0%91',
            'gift_min_delivery' => '400 р',

            // Реквизиты ИП (для футера, политик и микроразметки).
            'legal_name' => 'ИП Парусов Егор Васильевич',
            'legal_unp' => '392038435',
            'legal_email' => '',
            'legal_reg_authority' => 'Оршанский райисполком',
            'legal_reg_date' => '17.06.2026',
            'legal_address' => 'Витебская обл., г. Орша, ул. 1 Красная, д. 3, кв. 68',
            'work_hours' => 'Ежедневно с 9:00 до 21:00',
            // Образец документа об оплате (чек) — файл загружается в админке.
            'payment_receipt' => '',

            // SEO: домен и коды верификации поисковиков.
            'site_url' => 'https://hands-mogilev.by',
            'google_verification' => 'wQ7P5dcjWfVY5so5H4nZkZZMm-wHPKb7DoKyp6BQTEE',
            'yandex_verification' => '45702c9329eecbd1',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
