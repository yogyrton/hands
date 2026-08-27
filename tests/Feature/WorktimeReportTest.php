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
        return Service::create(['slug' => 's-'.uniqid(), 'name' => 'Классический массаж', 'level' => 4, 'lead' => 'l']);
    }

    private function visit(Master $m, Service $s, int $duration): void
    {
        Visit::create([
            'master_id' => $m->id, 'service_id' => $s->id, 'duration_minutes' => $duration,
            'base_price' => 60, 'service_price' => 60, 'paid_amount' => 60,
            'payment_type' => PaymentType::Cash, 'performed_at' => now(),
        ]);
    }

    public function test_masters_summary_totals_include_prep(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна');
        $service = $this->service();
        // 60 + 90 = 150 мин массажа; подготовка 15×2=30; итого 180 (3 ч).
        $this->visit($anna, $service, 60);
        $this->visit($anna, $service, 90);

        $rows = Livewire::test(WorktimeReport::class)->instance()->mastersSummary();

        $this->assertCount(1, $rows);
        $this->assertSame('Анна', $rows[0]['name']);
        $this->assertSame(2, $rows[0]['visits']);
        $this->assertSame(150, $rows[0]['massage_minutes']);
        $this->assertSame(30, $rows[0]['prep_minutes']);
        $this->assertSame(180, $rows[0]['total_minutes']);
    }

    public function test_list_page_renders_master_cards_for_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $anna = $this->master('Анна');
        $this->visit($anna, $this->service(), 60);

        Livewire::test(WorktimeReport::class)
            ->assertOk()
            ->assertSee('Учёт рабочего времени')
            ->assertSee('Анна')
            ->assertSee('Подробнее');
    }

    public function test_page_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));
        $this->assertFalse(WorktimeReport::canAccess());

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->assertTrue(WorktimeReport::canAccess());
    }
}
