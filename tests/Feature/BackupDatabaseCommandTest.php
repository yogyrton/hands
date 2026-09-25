<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_writes_backup_with_data(): void
    {
        Storage::fake('local');
        Service::create(['slug' => 'x', 'name' => 'Тестовая услуга', 'level' => 4, 'lead' => 'l']);

        $this->artisan('db:backup')->assertSuccessful();

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('Тестовая услуга', Storage::disk('local')->get($files[0]));
    }

    public function test_rotation_keeps_only_latest(): void
    {
        Storage::fake('local');

        // Старые бэкапы (имена в ISO-порядке — сортируются по времени).
        foreach (['2020-01-01_00-00', '2020-02-01_00-00', '2020-03-01_00-00'] as $stamp) {
            Storage::disk('local')->put("backups/hands-{$stamp}.sql", '-- old');
        }

        $this->artisan('db:backup', ['--keep' => 2])->assertSuccessful();

        $files = Storage::disk('local')->files('backups');
        // Осталось ровно 2: свежий + самый новый из старых.
        $this->assertCount(2, $files);
        $this->assertFalseContains($files, 'hands-2020-01-01_00-00.sql');
        $this->assertFalseContains($files, 'hands-2020-02-01_00-00.sql');
    }

    /**
     * @param  array<int, string>  $files
     */
    private function assertFalseContains(array $files, string $needle): void
    {
        $this->assertFalse(
            collect($files)->contains(fn (string $f): bool => str_contains($f, $needle)),
            "Файл {$needle} должен был удалиться",
        );
    }
}
