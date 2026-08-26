<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            // Должность для прайса: master / pro. По умолчанию — master.
            $table->string('tier')->default('master')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table): void {
            $table->dropColumn('tier');
        });
    }
};
