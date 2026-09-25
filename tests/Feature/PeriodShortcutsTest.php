<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Pages\WorktimeReport;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeriodShortcutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_renders_new_shortcut_set(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(Reports::class)
            ->assertOk()
            ->assertSee('Сегодня')
            ->assertSee('Пред. день')
            ->assertSee('След. день')
            ->assertSee('Этот месяц')
            ->assertSee('Пред. месяц')
            ->assertSee('След. месяц')
            // Убранные кнопки-дубликаты.
            ->assertDontSee('Вчера')
            ->assertDontSee('Эта неделя')
            ->assertDontSee('Прошлый месяц');
    }

    public function test_worktime_report_renders_new_shortcut_set(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(WorktimeReport::class)
            ->assertOk()
            ->assertSee('Сегодня')
            ->assertSee('Пред. день')
            ->assertSee('Этот месяц')
            ->assertSee('Пред. месяц')
            ->assertDontSee('Вчера');
    }

    public function test_visits_list_period_filter_has_new_shortcut_set(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(ListVisits::class)
            ->assertOk()
            ->assertSee('Сегодня')
            ->assertSee('Пред. день')
            ->assertSee('Этот месяц')
            ->assertSee('След. месяц')
            ->assertDontSee('Вчера');
    }
}
