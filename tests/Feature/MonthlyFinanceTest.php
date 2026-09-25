<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Models\Certificate;
use App\Models\Master;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Visit;
use App\Support\MonthlyFinance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $name = 'Анна'): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => $name, 'name_dative' => $name, 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'is_active' => true,
        ]);
    }

    private function service(): Service
    {
        return Service::create(['slug' => 's-'.uniqid(), 'name' => 'Услуга', 'level' => 4, 'lead' => 'l']);
    }

    private function visit(Master $m, Service $s, float $price, Carbon $when): void
    {
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id,
            'base_price' => $price, 'service_price' => $price, 'paid_amount' => $price,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    private function fixedExpenses(): void
    {
        foreach (['expense_rent' => 1000, 'expense_utilities' => 0, 'expense_accountant' => 0, 'other_expenses' => 600, 'owner_fszn' => 300] as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => (string) $v]);
        }
    }

    public function test_profit_is_revenue_minus_half_salary_minus_fixed(): void
    {
        $this->fixedExpenses(); // постоянные = 1000 + 0 + 0 + 600 + 300 = 1900
        $month = Carbon::create(2026, 9, 10, 12);
        $anna = $this->master();
        $service = $this->service();

        // Наработано 5000 (нал), выручка 5000; зарплата ≈ 2500; расходы 1900.
        $this->visit($anna, $service, 3000, $month);
        $this->visit($anna, $service, 2000, $month);

        $f = MonthlyFinance::for(2026, 9);

        $this->assertSame(5000.0, $f->revenue_visits);
        $this->assertSame(5000.0, $f->revenue);
        $this->assertSame(2500.0, $f->salary_total);       // 5000 / 2
        $this->assertSame(1900.0, $f->fixed_total);
        $this->assertSame(4400.0, $f->costs_total);         // 2500 + 1900
        $this->assertSame(600.0, $f->profit);               // 5000 − 4400

        $this->assertCount(1, $f->masters);
        $this->assertSame(5000.0, $f->masters[0]->earned);
        $this->assertSame(2500.0, $f->masters[0]->cost);
    }

    public function test_certificate_sales_are_shown_but_not_counted_in_revenue(): void
    {
        $this->fixedExpenses();
        Certificate::create([
            'number' => '900', 'type' => CertificateType::Money, 'status' => CertificateStatus::Active,
            'initial_amount' => 300, 'remaining_amount' => 300,
            'sold_at' => Carbon::create(2026, 9, 5), 'expires_at' => Carbon::create(2027, 9, 5),
        ]);

        $f = MonthlyFinance::for(2026, 9);

        // Сумма продаж видна отдельно, но в выручку не входит (нет визитов → выручка 0).
        $this->assertSame(300.0, $f->revenue_certs);
        $this->assertSame(0.0, $f->revenue);
        $this->assertCount(1, $f->certs);
    }

    public function test_other_month_is_empty(): void
    {
        $this->fixedExpenses();
        $anna = $this->master();
        $this->visit($anna, $this->service(), 1000, Carbon::create(2026, 9, 10, 12));

        $f = MonthlyFinance::for(2026, 8); // другой месяц — визитов нет

        $this->assertSame(0.0, $f->revenue);
        $this->assertSame(0.0, $f->salary_total);
        $this->assertCount(0, $f->masters);
        // Прибыль отрицательная: 0 − 0 − 1900 постоянных.
        $this->assertSame(-1900.0, $f->profit);
    }
}
