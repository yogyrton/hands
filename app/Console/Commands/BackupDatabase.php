<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Сохраняет дамп базы в storage/app/backups и удаляет старые, оставляя
 * последние N (по умолчанию 7). Запускается по расписанию ночью.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=7 : Сколько последних бэкапов оставлять}';

    protected $description = 'Сохранить дамп базы на сервер и удалить старые копии';

    private const DIR = 'backups';

    public function handle(DatabaseBackup $backup): int
    {
        $keep = max(1, (int) $this->option('keep'));

        $name = 'hands-'.now(config('app.display_timezone'))->format('Y-m-d_H-i').'.sql';
        // public: файл 0644 — виден и читается с хоста (не root-only 0600).
        Storage::disk('local')->put(self::DIR.'/'.$name, $backup->dump(), 'public');

        // Ротация: имена содержат дату в ISO-порядке, поэтому обычная сортировка
        // по имени = по времени. Оставляем последние $keep.
        $files = collect(Storage::disk('local')->files(self::DIR))
            ->filter(fn (string $f): bool => str_ends_with($f, '.sql'))
            ->sort()
            ->values();

        $deleted = 0;
        foreach ($files->slice(0, max(0, $files->count() - $keep)) as $old) {
            Storage::disk('local')->delete($old);
            $deleted++;
        }

        $this->info("Бэкап сохранён: {$name}. Удалено старых: {$deleted}.");

        return self::SUCCESS;
    }
}
