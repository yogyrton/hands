<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Widgets\MonthlyFinanceOverview;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyFinanceOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_sees_widget(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(MonthlyFinanceOverview::canView());

        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(MonthlyFinanceOverview::canView());
    }

    public function test_month_navigation_shifts_selected_month(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');

        Livewire::test(MonthlyFinanceOverview::class)
            ->assertOk()
            ->assertSee('Сентябрь 2026')
            ->call('prevMonth')
            ->assertSee('Август 2026')
            ->call('prevMonth')
            ->assertSee('Июль 2026')
            ->call('currentMonth')
            ->assertSee('Сентябрь 2026')
            ->call('nextMonth')
            ->assertSee('Октябрь 2026');

        Carbon::setTestNow();
    }

    public function test_widget_shows_master_salary_half(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');

        $master = Master::create([
            'slug' => 'm-1', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'is_active' => true,
        ]);
        $service = Service::create(['slug' => 's-1', 'name' => 'Услуга', 'level' => 4, 'lead' => 'l']);
        Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 200, 'service_price' => 200, 'paid_amount' => 200,
            'payment_type' => PaymentType::Cash, 'performed_at' => Carbon::create(2026, 9, 10, 12),
        ]);

        Livewire::test(MonthlyFinanceOverview::class)
            ->assertOk()
            ->assertSee('Анна')
            ->assertSee('Зарплата мастеров')
            ->assertSee('Прибыль за месяц');

        Carbon::setTestNow();
    }
}
