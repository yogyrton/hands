<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Filament\Widgets\MonthRevenueSummary;
use App\Models\Master;
use App\Models\Service;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthRevenueSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function master(bool $active = true): Master
    {
        return Master::create([
            'slug' => 'a-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'sort_order' => 1,
            'is_active' => $active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function visit(Master $m, float $service, float $paid, PaymentType $type, Carbon $when, array $attributes = []): Visit
    {
        $s = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Классический массаж', 'level' => 4, 'base_price' => $service, 'lead' => 'l',
        ]);

        return Visit::create(array_merge([
            'master_id' => $m->id, 'service_id' => $s->id,
            'base_price' => $service, 'service_price' => $service, 'paid_amount' => $paid,
            'payment_type' => $type, 'performed_at' => $when,
        ], $attributes));
    }

    public function test_full_price_splits_into_cash_barter_and_cert(): void
    {
        $m = $this->master();
        $now = Carbon::now()->startOfMonth()->addDays(5)->setHour(12);

        // Обычные визиты, оплачены полностью.
        $this->visit($m, 65, 65, PaymentType::Cash, $now);
        $this->visit($m, 55, 55, PaymentType::Card, $now);
        // Два бартера: по кассе меньше полной стоимости.
        $this->visit($m, 55, 23, PaymentType::Cash, $now, ['discount_reason' => 'Василий Парусов']);
        $this->visit($m, 65, 23, PaymentType::Cash, $now, ['discount_reason' => 'Парусова Оксана']);
        // Визит по сертификату — стоимость покрыта сертификатом (в кассу 0).
        $this->visit($m, 80, 0, PaymentType::Certificate, $now);
        // Прошлый месяц — вне периода.
        $this->visit($m, 100, 100, PaymentType::Cash, Carbon::now()->subMonthNoOverflow()->startOfMonth());

        $s = MonthRevenueSummary::monthSummary(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        // Полная стоимость всех визитов (база зарплаты), включая по сертификату.
        $this->assertEqualsWithDelta(320.0, $s->active->services, 0.001);  // 65+55+55+65+80
        $this->assertEqualsWithDelta(166.0, $s->active->cash, 0.001);      // 65+55+23+23+0
        $this->assertEqualsWithDelta(74.0, $s->active->barter, 0.001);     // 32 + 42
        $this->assertEqualsWithDelta(80.0, $s->active->cert, 0.001);       // визит по сертификату
        // Инвариант: касса + бартер + сертификаты = полная стоимость.
        $this->assertEqualsWithDelta($s->active->services, $s->active->cash + $s->active->barter + $s->active->cert, 0.001);
        $this->assertCount(2, $s->bartes);
        $this->assertSame('Василий Парусов', $s->bartes[0]->discount_reason);
    }

    public function test_two_counts_active_and_with_inactive(): void
    {
        // Один активный, один ушедший (неактивный) — оба отработали в месяце.
        $now = Carbon::now()->startOfMonth()->addDays(4)->setHour(11);
        $active = $this->master(true);
        $gone = $this->master(false);

        $this->visit($active, 100, 100, PaymentType::Cash, $now);
        $this->visit($gone, 80, 0, PaymentType::Certificate, $now);   // по сертификату

        $s = MonthRevenueSummary::monthSummary(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        // Основной подсчёт — только активные.
        $this->assertEqualsWithDelta(100.0, $s->active->services, 0.001);
        // Ушедшие, отработавшие в месяце, — отдельно, но не теряются.
        $this->assertEqualsWithDelta(80.0, $s->inactive->services, 0.001);
        $this->assertEqualsWithDelta(80.0, $s->inactive->cert, 0.001);
        // Итог — оба вместе.
        $this->assertEqualsWithDelta(180.0, $s->total->services, 0.001);
    }
}
