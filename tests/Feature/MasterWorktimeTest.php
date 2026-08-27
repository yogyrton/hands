<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\MasterWorktime;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class MasterWorktimeTest extends TestCase
{
    use RefreshDatabase;

    private function master(): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Анна', 'name_dative' => 'Анне',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
    }

    private function service(): Service
    {
        $service = Service::create(['slug' => 's-'.uniqid(), 'name' => 'Классический массаж', 'level' => 4, 'lead' => 'l']);
        $service->prices()->create(['duration_minutes' => 60, 'price_master' => 60, 'price_pro' => 80]);
        $service->prices()->create(['duration_minutes' => 90, 'price_master' => 85, 'price_pro' => 110]);

        return $service;
    }

    private function visit(Master $m, Service $s, ?int $duration, Carbon $when, float $base = 60): void
    {
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id, 'duration_minutes' => $duration,
            'base_price' => $base, 'service_price' => $base, 'paid_amount' => $base,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    private function page(Master $master): MasterWorktime
    {
        $page = Livewire::test(MasterWorktime::class, ['master' => $master->id])->instance();
        $page->data = ['from' => Carbon::now()->startOfMonth()->toDateString(), 'until' => Carbon::now()->endOfMonth()->toDateString()];

        return $page;
    }

    public function test_days_table_and_totals(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master();
        $service = $this->service();

        $d1 = Carbon::now()->startOfMonth()->addDays(1)->setHour(12);
        $d2 = Carbon::now()->startOfMonth()->addDays(2)->setHour(12);
        // День 1: 60 + 90 = 150 массаж; подгот 30; всего 180.
        $this->visit($anna, $service, 60, $d1);
        $this->visit($anna, $service, 90, $d1);
        // День 2: 60; подгот 15; всего 75.
        $this->visit($anna, $service, 60, $d2);

        $page = $this->page($anna);
        $days = $page->days();
        $t = $page->totals();

        $this->assertCount(2, $days);
        $this->assertSame(180, $days[0]['total_minutes']);
        $this->assertSame(75, $days[1]['total_minutes']);
        // Итог: массаж 210, подгот 45, всего 255.
        $this->assertSame(210, $t->massage_minutes);
        $this->assertSame(45, $t->prep_minutes);
        $this->assertSame(255, $t->total_minutes);
    }

    public function test_breakdown_and_inference(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master();
        $service = $this->service();
        $now = Carbon::now()->startOfMonth()->addDays(3)->setHour(12);

        $this->visit($anna, $service, 60, $now);          // явная длительность
        $this->visit($anna, $service, null, $now, 85);    // 90 мин по цене (≈)

        $page = $this->page($anna);
        $rows = $page->breakdown();

        $this->assertTrue($page->anyInferred());
        $labels = array_column($rows, 'duration');
        $this->assertContains('60 мин', $labels);
        $this->assertContains('90 мин ≈', $labels);
    }

    public function test_detail_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master();
        $this->visit($anna, $this->service(), 60, Carbon::now()->startOfMonth()->addDay());

        Livewire::test(MasterWorktime::class, ['master' => $anna->id])
            ->assertOk()
            ->assertSee('Рабочее время')
            ->assertSee('Анна')
            ->assertSee('По дням');
    }

    public function test_detail_page_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(MasterWorktime::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(MasterWorktime::canAccess());
    }
}
