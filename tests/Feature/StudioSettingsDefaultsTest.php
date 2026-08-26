<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExpensePeriod;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StudioSettingsDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_value_empty_string(): void
    {
        // Раньше пустая строка «перебивала» дефолт → 0. Теперь дефолт срабатывает.
        Setting::create(['key' => 'income_tax_percent', 'value' => '']);
        Cache::flush();

        $this->assertSame('20', Setting::get('income_tax_percent', '20'));
        $this->assertSame('34', Setting::get('contrib_fszn_percent', '34'));   // ключа нет — тоже дефолт
    }

    public function test_new_expense_month_seeds_default_expenses_even_with_empty_settings(): void
    {
        // Настройки сохранены пустыми — расходы месяца должны взять дефолты, а не нули.
        foreach (['expense_rent', 'expense_utilities', 'expense_accountant', 'contrib_fszn_percent'] as $key) {
            Setting::create(['key' => $key, 'value' => '']);
        }
        Cache::flush();

        $period = ExpensePeriod::create(['year' => 2026, 'month' => 8]);
        $period->expenses()->createMany([
            ['title' => 'Аренда', 'amount' => (float) Setting::get('expense_rent', '1880')],
            ['title' => 'Квартплата', 'amount' => (float) Setting::get('expense_utilities', '200')],
            ['title' => 'Услуги бухгалтера', 'amount' => (float) Setting::get('expense_accountant', '250')],
        ]);

        $this->assertEqualsWithDelta(1880.0, (float) $period->expenses()->where('title', 'Аренда')->value('amount'), 0.001);
        $this->assertEqualsWithDelta(200.0, (float) $period->expenses()->where('title', 'Квартплата')->value('amount'), 0.001);
        $this->assertEqualsWithDelta(250.0, (float) $period->expenses()->where('title', 'Услуги бухгалтера')->value('amount'), 0.001);
    }
}
