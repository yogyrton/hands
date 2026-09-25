<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Widgets\RevenueByMonthChart;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class RevenueByMonthChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(RevenueByMonthChart::class)
            ->assertOk()
            ->assertSee('фактически выполненные массажи');
    }

    public function test_only_months_with_visits_are_shown(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Carbon::setTestNow('2026-09-15');

        $master = Master::create([
            'slug' => 'm-1', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35, 'is_active' => true,
        ]);
        $service = Service::create(['slug' => 's-1', 'name' => 'Услуга', 'level' => 4, 'lead' => 'l']);

        // Визиты в июле и сентябре; август и «до открытия» пусты — их быть не должно.
        foreach ([Carbon::create(2026, 7, 20, 12), Carbon::create(2026, 9, 10, 12)] as $when) {
            Visit::create([
                'master_id' => $master->id, 'service_id' => $service->id,
                'base_price' => 200, 'service_price' => 200, 'paid_amount' => 200,
                'payment_type' => PaymentType::Cash, 'performed_at' => $when,
            ]);
        }

        $method = new ReflectionMethod(RevenueByMonthChart::class, 'getData');
        $method->setAccessible(true);
        $data = $method->invoke(app(RevenueByMonthChart::class));

        // Только июль и сентябрь (август пуст — пропущен).
        $this->assertSame(['июл 26', 'сен 26'], $data['labels']);
        $this->assertSame(200.0, $data['datasets'][0]['data'][1]);

        Carbon::setTestNow();
    }

    public function test_empty_when_no_visits(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $method = new ReflectionMethod(RevenueByMonthChart::class, 'getData');
        $method->setAccessible(true);
        $data = $method->invoke(app(RevenueByMonthChart::class));

        $this->assertSame([], $data['labels']);
    }
}
