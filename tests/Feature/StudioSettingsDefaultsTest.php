<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StudioSettingsDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_value_empty_string(): void
    {
        // Пустая строка не должна «перебивать» дефолт (иначе в расчётах шли бы нули).
        Setting::create(['key' => 'expense_rent', 'value' => '']);
        Cache::flush();

        $this->assertSame('1880', Setting::get('expense_rent', '1880'));
        $this->assertSame('600', Setting::get('other_expenses', '600'));   // ключа нет — тоже дефолт
        $this->assertSame('300', Setting::get('owner_fszn', '300'));
    }
}
