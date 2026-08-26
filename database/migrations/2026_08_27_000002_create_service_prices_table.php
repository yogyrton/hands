<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_minutes');   // длительность сеанса
            $table->decimal('price_master', 10, 2)->default(0);  // цена для мастера
            $table->decimal('price_pro', 10, 2)->default(0);     // цена для про-мастера
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Одна строка прайса на связку «услуга + длительность».
            $table->unique(['service_id', 'duration_minutes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
