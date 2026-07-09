<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Postgres не умеет DISTINCT/сравнение по типу json (нет оператора равенства),
 * из-за чего падал мультиселект услуг у мастера. jsonb такой оператор имеет.
 * На sqlite json и jsonb эквивалентны (text) — миграция для него не нужна.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE services ALTER COLUMN includes TYPE jsonb USING includes::jsonb');
        DB::statement('ALTER TABLE services ALTER COLUMN requests TYPE jsonb USING requests::jsonb');
        DB::statement('ALTER TABLE services ALTER COLUMN details TYPE jsonb USING details::jsonb');
        DB::statement('ALTER TABLE masters ALTER COLUMN principles TYPE jsonb USING principles::jsonb');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE services ALTER COLUMN includes TYPE json USING includes::json');
        DB::statement('ALTER TABLE services ALTER COLUMN requests TYPE json USING requests::json');
        DB::statement('ALTER TABLE services ALTER COLUMN details TYPE json USING details::json');
        DB::statement('ALTER TABLE masters ALTER COLUMN principles TYPE json USING principles::json');
    }
};
