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

    public function test_per_master_card_totals_split_by_payment_for_period(): void
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

        $rows = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(1, $rows);
        $this->assertSame('Александр', $rows[0]->name);
        $this->assertSame(4, $rows[0]->count);
        $this->assertEqualsWithDelta(120.0, $rows[0]->cash, 0.001);   // 100 + 20 доплата
        $this->assertEqualsWithDelta(80.0, $rows[0]->card, 0.001);
        $this->assertEqualsWithDelta(130.0, $rows[0]->cert, 0.001);   // 65 + (85 − 20)
        $this->assertEqualsWithDelta(330.0, $rows[0]->total, 0.001);  // 120 + 80 + 130
    }

    public function test_special_conditions_count_by_service_price_not_cash_paid(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);

        // Бесплатно клиенту: итоговая 65, по кассе 0 (наличные). Заработок — 65.
        $this->visit($alex, 65, now(), ['payment_type' => PaymentType::Cash, 'paid_amount' => 0]);
        // Владелец платит только долю мастера картой: итоговая 80, по кассе 28. Заработок — 80.
        $this->visit($alex, 80, now(), ['payment_type' => PaymentType::Card, 'paid_amount' => 28]);

        $rows = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(65.0, $rows[0]->cash, 0.001);   // итоговая, не 0 по кассе
        $this->assertEqualsWithDelta(80.0, $rows[0]->card, 0.001);   // итоговая, не 28 по кассе
        $this->assertEqualsWithDelta(0.0, $rows[0]->cert, 0.001);
        $this->assertEqualsWithDelta(145.0, $rows[0]->total, 0.001);
    }

    public function test_only_masters_with_visits_ordered_by_sort(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);
        $anna = $this->master('Анна', 'anna', 2, 35);
        $this->master('Пётр', 'petr', 3, 35);   // без визитов — не показываем

        $this->visit($anna, 200, now());
        $this->visit($alex, 100, now());

        $rows = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(2, $rows);
        $this->assertSame(['Александр', 'Анна'], $rows->pluck('name')->all()); // по sort_order
        $this->assertEqualsWithDelta(100.0, $rows[0]->total, 0.001);
        $this->assertEqualsWithDelta(200.0, $rows[1]->total, 0.001);
    }

    public function test_page_renders_card_with_total_and_split(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $alex = $this->master('Александр Марков', 'aleksandr', 1, 35);
        $this->visit($alex, 100, now(), ['payment_type' => PaymentType::Cash, 'paid_amount' => 100]);
        $this->visit($alex, 80, now(), ['payment_type' => PaymentType::Card, 'paid_amount' => 80]);

        Livewire::test(ListVisits::class)
            ->assertSuccessful()
            ->assertSee('Александр Марков')
            ->assertSee('180.00')            // общая сумма мастера
            ->assertSee('2 визита')
            ->assertSee('Нал');              // разбивка снизу
    }
}
