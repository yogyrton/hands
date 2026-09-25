<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use App\Support\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_contains_schema_and_data(): void
    {
        Service::create(['slug' => 'classic-x', 'name' => 'Классический массаж', 'level' => 4, 'lead' => 'l']);

        $sql = app(DatabaseBackup::class)->dump();

        $this->assertStringContainsString('DROP TABLE IF EXISTS `services`', $sql);
        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('INSERT INTO `services`', $sql);
        $this->assertStringContainsString('Классический массаж', $sql);
    }

    public function test_dump_excludes_transient_tables(): void
    {
        $sql = app(DatabaseBackup::class)->dump();

        // Сессии/кэш в бэкап не попадают — восстановление не разлогинивает.
        $this->assertStringNotContainsString('`sessions`', $sql);
        $this->assertStringNotContainsString('`cache`', $sql);
    }

    public function test_admin_can_download_backup(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $response = $this->get(route('admin.db-backup'));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.sql', (string) $response->headers->get('content-disposition'));
    }

    public function test_master_cannot_download_backup(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Master]));

        $this->get(route('admin.db-backup'))->assertForbidden();
    }

    public function test_guest_cannot_download_backup(): void
    {
        $this->get(route('admin.db-backup'))->assertForbidden();
    }
}
