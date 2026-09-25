<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Дамп базы данных в обычный .sql (DROP + CREATE + INSERT) на чистом PHP —
 * без зависимости от бинарника mysqldump (его может не быть на хостинге).
 * Поддерживает MySQL/MariaDB (боевой сервер) и SQLite (локально/тесты).
 *
 * Небольшая база студии целиком помещается в память — файл собираем строкой
 * и отдаём на скачивание.
 */
class DatabaseBackup
{
    /**
     * Служебные таблицы не входят в бэкап: это временные данные (сессии, кэш,
     * очереди), к реальным данным студии отношения не имеют. Заодно бэкап не
     * трогает сессии — после восстановления вход в админку не слетает.
     *
     * @var array<int, string>
     */
    private const EXCLUDED = [
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function dump(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);

        $out = '-- HANDS — резервная копия базы данных'."\n";
        $out .= '-- Дата: '.now()->format('Y-m-d H:i:s')."\n";
        $out .= '-- Драйвер: '.$driver."\n\n";

        if ($isMysql) {
            $out .= "SET NAMES utf8mb4;\n";
            $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        }

        foreach ($this->tables($driver) as $table) {
            if (in_array($table, self::EXCLUDED, true)) {
                continue;
            }

            $out .= $this->dumpTable($table, $pdo, $isMysql);
        }

        if ($isMysql) {
            $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function tables(string $driver): array
    {
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return array_map(
                fn (object $row): string => (string) array_values((array) $row)[0],
                DB::select('SHOW TABLES'),
            );
        }

        // SQLite
        $rows = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        return array_map(fn (object $row): string => (string) $row->name, $rows);
    }

    private function dumpTable(string $table, \PDO $pdo, bool $isMysql): string
    {
        $q = '`'.str_replace('`', '``', $table).'`';

        $out = '-- Таблица '.$table."\n";
        $out .= "DROP TABLE IF EXISTS {$q};\n";
        $out .= $this->createStatement($table, $isMysql).";\n\n";

        $rows = DB::connection()->table($table)->get();
        if ($rows->isEmpty()) {
            return $out."\n";
        }

        $columns = array_keys((array) $rows->first());
        $columnList = implode(', ', array_map(fn (string $c): string => '`'.str_replace('`', '``', $c).'`', $columns));

        foreach ($rows as $row) {
            $values = array_map(
                fn ($value): string => $value === null ? 'NULL' : $pdo->quote((string) $value),
                array_values((array) $row),
            );
            $out .= "INSERT INTO {$q} ({$columnList}) VALUES (".implode(', ', $values).");\n";
        }

        return $out."\n";
    }

    private function createStatement(string $table, bool $isMysql): string
    {
        if ($isMysql) {
            $row = (array) DB::select('SHOW CREATE TABLE `'.str_replace('`', '``', $table).'`')[0];

            return (string) ($row['Create Table'] ?? $row['Create View'] ?? '');
        }

        $row = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);

        return (string) ($row[0]->sql ?? '');
    }
}
