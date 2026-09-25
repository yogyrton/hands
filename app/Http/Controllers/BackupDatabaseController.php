<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DatabaseBackup;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Отдаёт дамп базы на скачивание администратору. Файл нигде не сохраняется на
 * сервере — сразу уходит в браузер с датой в имени.
 */
class BackupDatabaseController extends Controller
{
    public function __invoke(DatabaseBackup $backup): StreamedResponse
    {
        abort_unless(auth()->check() && auth()->user()?->isAdmin(), 403);

        $sql = $backup->dump();
        $filename = 'hands-'.now()->format('Y-m-d_H-i').'.sql';

        return response()->streamDownload(
            function () use ($sql): void {
                echo $sql;
            },
            $filename,
            ['Content-Type' => 'application/sql'],
        );
    }
}
