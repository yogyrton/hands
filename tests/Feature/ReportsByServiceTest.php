<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Models\Master;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsByServiceTest extends TestCase
{
    use RefreshDatabase;

    private function master(): Master
    {
        return Master::create([
            'slug' => 'm-'.uniqid(), 'name' => 'Мастер', 'name_dative' => 'Мастеру',
            'role' => 'Массажист', 'yclients_url' => 'https://e.com', 'bio1' => 'a', 'bio2' => 'b', 'salary_rate' => 35,
        ]);
    }

    private function service(string $name): Service
    {
        return Service::create(['slug' => 's-'.uniqid(), 'name' => $name, 'level' => 4, 'lead' => 'l']);
    }

    private function visit(Master $m, Service $s, ?int $duration, Carbon $when): void
    {
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id, 'duration_minutes' => $duration,
            'base_price' => 60, 'service_price' => 60, 'paid_amount' => 60,
            'payment_type' => PaymentType::Cash, 'performed_at' => $when,
        ]);
    }

    public function test_demand_by_service_and_duration_counts_and_sorts(): void
    {
        $m = $this->master();
        $classic = $this->service('Классический массаж');
        $now = Carbon::now()->startOfMonth()->addDay()->setHour(12);

        // Классика 60 мин — 3, Классика 90 мин — 1.
        $this->visit($m, $classic, 60, $now);
        $this->visit($m, $classic, 60, $now);
        $this->visit($m, $classic, 60, $now);
        $this->visit($m, $classic, 90, $now);
        // Прошлый месяц — вне периода.
        $this->visit($m, $classic, 60, Carbon::now()->subMonthNoOverflow());

        $page = Livewire::test(Reports::class)->instance();
        $page->data = ['from' => Carbon::now()->startOfMonth()->toDateString(), 'until' => Carbon::now()->endOfMonth()->toDateString()];

        $rows = $page->byServiceDuration();

        $this->assertCount(2, $rows);
        $this->assertSame(3, $rows[0]['count']);          // самый ходовой сверху
        $this->assertSame('60 мин', $rows[0]['duration']);
        $this->assertSame(1, $rows[1]['count']);
        $this->assertSame('90 мин', $rows[1]['duration']);
    }

    public function test_reports_page_renders_demand_section(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $m = $this->master();
        $this->visit($m, $this->service('Классический массаж'), 60, Carbon::now()->startOfMonth()->addDay());

        Livewire::test(Reports::class)
            ->assertOk()
            ->assertSee('Спрос по услугам')
            ->assertSee('Классический массаж');
    }
}
