<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Widgets\DashboardStats;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_sees_financial_widget(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(DashboardStats::canView());

        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(DashboardStats::canView());
    }

    public function test_widget_renders_current_month_figures(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $master = Master::create([
            'slug' => 'm-1', 'name' => 'Анна', 'name_dative' => 'Анне', 'role' => 'Массажист',
            'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
        $service = Service::create([
            'slug' => 's-1', 'name' => 'Услуга', 'level' => 4, 'base_price' => 100, 'lead' => 'lead',
        ]);
        Visit::create([
            'master_id' => $master->id, 'service_id' => $service->id,
            'base_price' => 100, 'service_price' => 100, 'paid_amount' => 100,
            'payment_type' => PaymentType::Cash, 'performed_at' => now(),
        ]);

        Livewire::test(DashboardStats::class)
            ->assertOk()
            ->assertSee('Прибыль')
            ->assertSee('Продано сертификатов')
            ->assertSee('Налог')
            ->assertSee('Истекающие сертификаты');
    }
}
