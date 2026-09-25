<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Dashboard;
use App\Models\Service;
use App\Models\User;
use App\Support\DatabaseBackup;
use App\Support\DatabaseImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_then_import_restores_data(): void
    {
        // Исходные данные и дамп.
        Service::create(['slug' => 'keep', 'name' => 'Была услуга', 'level' => 4, 'lead' => 'l']);
        $sql = app(DatabaseBackup::class)->dump();

        // Меняем базу: удаляем старую услугу, добавляем другую.
        Service::query()->delete();
        Service::create(['slug' => 'new', 'name' => 'Новая услуга', 'level' => 3, 'lead' => 'l']);

        // Импорт возвращает состояние на момент дампа.
        app(DatabaseImport::class)->import($sql);

        $this->assertDatabaseHas('services', ['slug' => 'keep', 'name' => 'Была услуга']);
        $this->assertDatabaseMissing('services', ['slug' => 'new']);
    }

    public function test_looks_like_backup_guard(): void
    {
        $import = app(DatabaseImport::class);

        $this->assertTrue($import->looksLikeBackup('-- x'."\n".'CREATE TABLE `a` (id int);'));
        $this->assertFalse($import->looksLikeBackup('просто текст, не бэкап'));
    }

    public function test_dashboard_shows_backup_and_import_buttons(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        Livewire::test(Dashboard::class)
            ->assertOk()
            ->assertSee('Бэкап БД')
            ->assertSee('Импорт БД');
    }
}
