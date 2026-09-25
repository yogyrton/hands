<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Восстановление базы из .sql-дампа (нашего формата: DROP + CREATE + INSERT).
 * Полностью заменяет текущие данные. FK-проверки на время импорта отключаются,
 * чтобы порядок DROP/CREATE таблиц не имел значения.
 */
class DatabaseImport
{
    public function import(string $sql): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::connection()->unprepared($sql);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Похоже ли содержимое на наш дамп (чтобы случайно не выполнить чужой файл).
     */
    public function looksLikeBackup(string $sql): bool
    {
        return str_contains($sql, 'CREATE TABLE');
    }
}
