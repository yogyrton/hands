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

    private function visit(Master $master, float $price, Carbon $when): Visit
    {
        $service = Service::create([
            'slug' => 's-'.uniqid(), 'name' => 'Массаж', 'level' => 4, 'base_price' => $price, 'lead' => 'l',
        ]);

        return Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => $price, 'service_price' => $price, 'paid_amount' => $price,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    public function test_summarize_only_masters_with_visits_and_correct_earnings(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);
        $anna = $this->master('Анна', 'anna', 2, 35);

        // Александр: 2 визита сегодня по 100 → услуг 200, доля 70.
        $this->visit($alex, 100, now());
        $this->visit($alex, 100, now());
        // Анна: визит вчера — вне сегодняшнего периода.
        $this->visit($anna, 100, now()->subDay());

        $rows = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(1, $rows);                        // только Александр
        $this->assertSame('Александр', $rows[0]->name);
        $this->assertSame(2, $rows[0]->count);
        $this->assertEqualsWithDelta(200.0, $rows[0]->services, 0.001);
        $this->assertEqualsWithDelta(70.0, $rows[0]->earned, 0.001);
    }

    public function test_new_master_appears_once_has_visit_and_uses_own_rate(): void
    {
        $alex = $this->master('Александр', 'aleksandr', 1, 35);
        $anna = $this->master('Анна', 'anna', 2, 40);

        $this->visit($alex, 100, now());   // 100 × 35% = 35
        $this->visit($anna, 200, now());   // 200 × 40% = 80

        $rows = MasterEarningsSummary::summarize(Visit::query()->whereDate('performed_at', today()));

        $this->assertCount(2, $rows);
        $this->assertSame(['Александр', 'Анна'], $rows->pluck('name')->all()); // по sort_order
        $this->assertEqualsWithDelta(35.0, $rows[0]->earned, 0.001);
        $this->assertEqualsWithDelta(80.0, $rows[1]->earned, 0.001);
    }

    public function test_page_renders_widget_with_master_earning(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $alex = $this->master('Александр Марков', 'aleksandr', 1, 35);
        $this->visit($alex, 100, now());
        $this->visit($alex, 100, now());

        Livewire::test(ListVisits::class)
            ->assertSuccessful()
            ->assertSee('Александр Марков')
            ->assertSee('70.00');   // его доля за сегодня
    }
}
