<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Reports;
use App\Filament\Pages\WorktimeReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PeriodShortcutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_page_renders_period_shortcut_buttons(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(Reports::class)
            ->assertOk()
            ->assertSee('Сегодня')
            ->assertSee('Вчера')
            ->assertSee('Этот месяц')
            ->assertSee('Прошлый месяц')
            ->assertSee('Пред. день')
            ->assertSee('След. день');
    }

    public function test_worktime_report_renders_period_shortcut_buttons(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(WorktimeReport::class)
            ->assertOk()
            ->assertSee('Сегодня')
            ->assertSee('Прошлый месяц')
            ->assertSee('Пред. день');
    }
}
