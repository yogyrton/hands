<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Widgets\MonthProfitSummary;
use App\Models\Certificate;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MonthProfitSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function master(float $rate): Master
    {
        return Master::create([
            'slug' => 'a-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => $rate, 'sort_order' => 1,
        ]);
    }

    private function visit(Master $m, float $service, Carbon $when): void
    {
        $s = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'base_price' => $service, 'lead' => 'l',
        ]);
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id,
            'base_price' => $service, 'service_price' => $service, 'paid_amount' => $service,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    public function test_masters_breakdown_full_service_value_per_master(): void
    {
        $now = Carbon::now()->startOfMonth()->addDays(3)->setHour(12);
        $a = $this->master(35);
        $b = $this->master(40);
        $this->visit($a, 100, $now);
        $this->visit($a, 100, $now);   // Александр: наработал 200
        $this->visit($b, 200, $now);   // второй: 200
        $this->visit($a, 100, Carbon::now()->subMonthNoOverflow());   // вне месяца

        $rows = MonthProfitSummary::mastersBreakdown(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());

        $this->assertCount(2, $rows);
        $this->assertEqualsWithDelta(200.0, $rows[0]->amount, 0.001);   // полная стоимость, не 35%
        $this->assertSame(2, $rows[0]->count);
        $this->assertEqualsWithDelta(200.0, $rows[1]->amount, 0.001);
    }

    public function test_renders_profit_certs_and_master_split(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $now = Carbon::now()->startOfMonth()->addDays(2)->setHour(10);
        $m = $this->master(35);
        $this->visit($m, 100, $now);

        Certificate::create([
            'number' => '149', 'type' => CertificateType::Money, 'status' => CertificateStatus::Active,
            'initial_amount' => 300, 'remaining_amount' => 300,
            'sold_at' => $now->copy(), 'expires_at' => $now->copy()->addMonths(3),
        ]);

        Livewire::test(MonthProfitSummary::class)
            ->assertSuccessful()
            ->assertSee('Прибыль за месяц')
            ->assertSee('Наработали мастера')
            ->assertSee('Расходы за месяц')
            ->assertSee('Продано сертификатов за месяц')
            ->assertSee('№149')
            ->assertSee('300.00');
    }
}
