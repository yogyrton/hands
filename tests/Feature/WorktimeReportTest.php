<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\WorktimeReport;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class WorktimeReportTest extends TestCase
{
    use RefreshDatabase;

    private function master(string $name = 'Анна'): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => $name, 'name_dative' => $name,
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

    public function test_worktime_totals_include_prep_per_massage(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна');
        $service = $this->service();
        $now = Carbon::now()->startOfMonth()->addDay()->setHour(12);

        // 3×60 + 1×90 + 1×40 = 310 мин массажей; 5 посещений × 15 = 75 мин подготовки; итого 385.
        foreach ([60, 60, 60, 90, 40] as $d) {
            $this->visit($anna, $service, $d, $now);
        }
        // Вне периода — не считается.
        $this->visit($anna, $service, 60, Carbon::now()->subMonthNoOverflow());

        $page = Livewire::test(WorktimeReport::class)->instance();
        $page->data = ['from' => Carbon::now()->startOfMonth()->toDateString(), 'until' => Carbon::now()->endOfMonth()->toDateString()];

        $rows = $page->byMaster();

        $this->assertCount(1, $rows);
        $this->assertSame('Анна', $rows[0]['name']);
        $this->assertSame(5, $rows[0]['visits']);
        $this->assertSame(310, $rows[0]['massage_minutes']);
        $this->assertSame(75, $rows[0]['prep_minutes']);
        $this->assertSame(385, $rows[0]['total_minutes']);
        $this->assertSame('6 ч 25 мин', $page->hm($rows[0]['total_minutes']));
    }

    public function test_duration_inferred_from_price_when_missing(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна');
        $service = $this->service();   // прайс: 60 мин=60р, 90 мин=85р (для мастера)
        $now = Carbon::now()->startOfMonth()->addDay()->setHour(12);

        // Старое посещение без длительности, но с ценой 85 → определяем 90 мин по прайсу.
        $this->visit($anna, $service, null, $now, base: 85);

        $page = Livewire::test(WorktimeReport::class)->instance();
        $page->data = ['from' => Carbon::now()->startOfMonth()->toDateString(), 'until' => Carbon::now()->endOfMonth()->toDateString()];

        $rows = $page->byMaster();

        $this->assertSame(90, $rows[0]['massage_minutes']);   // определено по цене
        $this->assertTrue($rows[0]['inferred']);
        $this->assertSame(105, $rows[0]['total_minutes']);    // 90 + 15
    }

    public function test_page_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна');
        $this->visit($anna, $this->service(), 60, Carbon::now()->startOfMonth()->addDay());

        Livewire::test(WorktimeReport::class)
            ->assertOk()
            ->assertSee('Учёт рабочего времени')
            ->assertSee('Анна');
    }

    public function test_page_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(WorktimeReport::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(WorktimeReport::canAccess());
    }
}
