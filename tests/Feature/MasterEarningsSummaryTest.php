<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Resources\Visits\Widgets\MasterEarningsSummary;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MasterEarningsSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $name, string $slug, int $sort, float $rate = 35): Master
    {
        return Master::create([
            'slug' => $slug, 'name' => $name, 'name_dative' => $name, 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b',
            'salary_rate' => $rate, 'sort_order' => $sort,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function visit(Master $master, float $servicePrice, Carbon $when, array $attributes = []): Visit
    {
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'base_price' => $servicePrice, 'lead' => 'l',
        ]);

        return Visit::create(array_merge([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => $servicePrice, 'service_price' => $servicePrice, 'paid_amount' => $servicePrice,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ], $attributes));
    }

    public function test_totals_split_cash_card_certificate_only_for_period(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);

        // Наличные 100, безнал 80.
        $this->visit($alex, 100, now(), ['payment_type' => PaymentType::Cash, 'paid_amount' => 100]);
        $this->visit($alex, 80, now(), ['payment_type' => PaymentType::Card, 'paid_amount' => 80]);
        // Сертификат целиком (услуга 65, деньгами 0).
        $this->visit($alex, 65, now(), ['payment_type' => PaymentType::Certificate, 'paid_amount' => 0]);
        // Сертификат с доплатой: услуга 85, доплата 20 наличными → серт покрыл 65.
        $this->visit($alex, 85, now(), [
            'payment_type' => PaymentType::CertificateSurcharge, 'paid_amount' => 20,
            'surcharge_payment_type' => PaymentType::Cash,
        ]);
        // Вчерашний визит — вне периода, не должен учитываться.
        $this->visit($alex, 500, now()->subDay(), ['payment_type' => PaymentType::Cash, 'paid_amount' => 500]);

        $s = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertSame(4, $s->count);
        $this->assertEqualsWithDelta(120.0, $s->cash, 0.001);   // 100 + 20 доплата
        $this->assertEqualsWithDelta(80.0, $s->card, 0.001);
        $this->assertEqualsWithDelta(130.0, $s->cert, 0.001);   // 65 + (85 − 20)
        $this->assertEqualsWithDelta(330.0, $s->total, 0.001);  // 120 + 80 + 130
    }

    public function test_master_cut_only_masters_with_visits_own_rate_and_order(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);
        $anna = $this->master('Анна', 'anna', 2, 40);

        $this->visit($alex, 100, now());   // услуг 100 → 35% = 35
        $this->visit($alex, 100, now());   // услуг 100 → ещё 35 (итого 70)
        $this->visit($anna, 200, now());   // услуг 200 → 40% = 80
        $this->visit($anna, 100, now()->subDay()); // вне периода — игнор

        $s = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(2, $s->masters);
        $this->assertSame(['Александр', 'Анна'], $s->masters->pluck('name')->all()); // по sort_order
        $this->assertEqualsWithDelta(70.0, $s->masters[0]->earned, 0.001);
        $this->assertEqualsWithDelta(80.0, $s->masters[1]->earned, 0.001);
        $this->assertEqualsWithDelta(150.0, $s->cut, 0.001);    // 70 + 80
    }

    public function test_single_master_when_only_one_has_visits(): void
    {
        $this->master('Александр', 'aleksandr', 1, 35);
        $anna = $this->master('Анна', 'anna', 2, 35);
        $this->visit($anna, 100, now());

        $s = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(1, $s->masters);
        $this->assertSame('Анна', $s->masters[0]->name);
    }

    public function test_page_renders_widget_with_totals_and_master_cut(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $alex = $this->master('Александр Марков', 'aleksandr', 1, 35);
        $this->visit($alex, 100, now());
        $this->visit($alex, 100, now());

        Livewire::test(ListVisits::class)
            ->assertSuccessful()
            ->assertSee('Заработано за период')
            ->assertSee('200.00')            // общая сумма (наличные)
            ->assertSee('Александр Марков')
            ->assertSee('70.00');            // доля мастера грязными
    }
}
