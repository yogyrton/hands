<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            // Длительность сеанса из прайса (для отображения; цена уже в service_price).
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table): void {
            $table->dropColumn('duration_minutes');
        });
    }
};
