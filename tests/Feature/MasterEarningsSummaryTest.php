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

    public function test_empty_period_gives_zeros(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);
        $this->visit($alex, 100, now()->subDay());   // только вчера

        $s = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertSame(0, $s->count);
        $this->assertEqualsWithDelta(0.0, $s->total, 0.001);
        $this->assertEqualsWithDelta(0.0, $s->cash, 0.001);
        $this->assertEqualsWithDelta(0.0, $s->card, 0.001);
        $this->assertEqualsWithDelta(0.0, $s->cert, 0.001);
    }

    public function test_page_renders_widget_with_totals(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $alex = $this->master('Александр Марков', 'aleksandr', 1, 35);
        $this->visit($alex, 100, now(), ['payment_type' => PaymentType::Cash, 'paid_amount' => 100]);
        $this->visit($alex, 80, now(), ['payment_type' => PaymentType::Card, 'paid_amount' => 80]);

        Livewire::test(ListVisits::class)
            ->assertSuccessful()
            ->assertSee('Заработано за период')
            ->assertSee('180.00')            // общая сумма
            ->assertSee('Наличными')
            ->assertSee('100.00')            // нал
            ->assertSee('Безналом')
            ->assertSee('80.00');            // безнал
    }
}
