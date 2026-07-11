<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table): void {
            // Стабильный ключ для сидера (у записей из админки — null).
            $table->string('slug')->nullable()->unique()->after('id');
        });

        Schema::table('promotions', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });

        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }
};
