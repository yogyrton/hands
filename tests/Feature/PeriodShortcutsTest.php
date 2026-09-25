<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Pages\WorktimeReport;
use App\Filament\Resources\Visits\Pages\ListVisits;
use App\Filament\Support\PeriodShortcuts;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_prev_next_day_gives_single_day(): void
    {
        Carbon::setTestNow('2026-09-29');

        // Выбран месяц → «Пред. день» = вчера (один день, от сегодня).
        $this->assertSame('2026-09-28', PeriodShortcuts::stepDay('2026-09-01', '2026-09-30', -1));
        // Выбран один день → шагаем от него (позавчера).
        $this->assertSame('2026-09-27', PeriodShortcuts::stepDay('2026-09-28', '2026-09-28', -1));
        // След. день от сегодняшнего дня.
        $this->assertSame('2026-09-30', PeriodShortcuts::stepDay('2026-09-29', '2026-09-29', 1));
        // Пусто → от сегодня.
        $this->assertSame('2026-09-28', PeriodShortcuts::stepDay(null, null, -1));

        Carbon::setTestNow();
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
