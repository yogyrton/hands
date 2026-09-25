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
            ->assertSee('Выручка и прибыль по месяцам');
    }

    public function test_data_spans_12_months_and_reflects_revenue(): void
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

        $method = new ReflectionMethod(RevenueByMonthChart::class, 'getData');
        $method->setAccessible(true);
        $data = $method->invoke(app(RevenueByMonthChart::class));

        $this->assertCount(12, $data['labels']);
        $this->assertSame('сен 26', $data['labels'][11]);        // последний столбик — текущий месяц
        $this->assertSame(200.0, $data['datasets'][0]['data'][11]); // выручка сентября

        Carbon::setTestNow();
    }
}
