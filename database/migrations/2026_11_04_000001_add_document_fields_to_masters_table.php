<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Реквизиты мастера для бумажных документов: Ф.И.О. с инициалами, должность и
 * характер работы. Нужны для табеля учёта рабочего времени (Word) и шапки
 * колонок в выгрузке сумм (Excel) — «мастер Анна» там не подходит.
 */
return new class extends Migration
{
    /**
     * Ф.И.О. и характер работы действующих мастеров — ровно как в бумажных
     * табелях, которые уже заполнялись от руки.
     *
     * @var array<string, array{name: string, employment: string}>
     */
    private const SEED = [
        'anna' => ['name' => 'Петрова А.А.', 'employment' => 'main'],
        'aleksandr-markov' => ['name' => 'Марков А.В.', 'employment' => 'main'],
        'dmitriy-p' => ['name' => 'Подов Д.В.', 'employment' => 'part_time'],
        'dmitriy-e' => ['name' => 'Ерошенко Д.С.', 'employment' => 'part_time'],
    ];

    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            // Ф.И.О. с инициалами — «Петрова А.А.». В Excel из него берётся только фамилия.
            $table->string('timesheet_name')->default('')->after('name');
            // Должность для документов (не путать с tier — та для прайса).
            $table->string('job_title')->default('массажист')->after('timesheet_name');
            // Характер работы: основная / совместительство (App\Enums\EmploymentType).
            $table->string('employment_type')->default('main')->after('job_title');
        });

        // Непустое значение всем существующим мастерам: поля обязательные в форме,
        // поэтому карточка не должна открываться с пустым Ф.И.О.
        DB::table('masters')->update(['timesheet_name' => DB::raw('name')]);

        foreach (self::SEED as $slug => $data) {
            DB::table('masters')->where('slug', $slug)->update([
                'timesheet_name' => $data['name'],
                'employment_type' => $data['employment'],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            $table->dropColumn(['timesheet_name', 'job_title', 'employment_type']);
        });
    }
};
