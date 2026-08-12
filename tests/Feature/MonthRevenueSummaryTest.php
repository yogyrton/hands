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

    private function master(): Master
    {
        return Master::create([
            'slug' => 'a-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'sort_order' => 1,
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

    public function test_services_cash_diff_and_bartes(): void
    {
        $m = $this->master();
        $now = Carbon::now()->startOfMonth()->addDays(5)->setHour(12);

        // Обычные визиты, оплачены полностью.
        $this->visit($m, 65, 65, PaymentType::Cash, $now);
        $this->visit($m, 55, 55, PaymentType::Card, $now);
        // Два бартера: по кассе меньше полной стоимости.
        $this->visit($m, 55, 23, PaymentType::Cash, $now, ['discount_reason' => 'Василий Парусов']);
        $this->visit($m, 65, 23, PaymentType::Cash, $now, ['discount_reason' => 'Парусова Оксана']);
        // Визит по сертификату — в выручку деньгами не входит.
        $this->visit($m, 80, 0, PaymentType::Certificate, $now);
        // Прошлый месяц — вне периода.
        $this->visit($m, 100, 100, PaymentType::Cash, Carbon::now()->subMonthNoOverflow()->startOfMonth());

        $s = MonthRevenueSummary::monthSummary(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $this->assertEqualsWithDelta(240.0, $s->services, 0.001);  // 65+55+55+65 (без сертификата)
        $this->assertEqualsWithDelta(166.0, $s->cash, 0.001);      // 65+55+23+23
        $this->assertEqualsWithDelta(74.0, $s->diff, 0.001);       // 32 + 42
        $this->assertCount(2, $s->bartes);
        $this->assertSame('Василий Парусов', $s->bartes[0]->discount_reason);
    }
}
